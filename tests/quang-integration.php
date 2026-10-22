<?php
declare(strict_types=1);

// Mutating integration checks: only the explicitly disposable, isolated DB.
if (getenv('COUPONHUB_RUN_DB_TEST') !== '1') exit("SKIP: opt in to isolated DB testing.\n");
require __DIR__ . '/../app/bootstrap.php';
ob_start();
$sessionPath = __DIR__ . '/../.runtime/quang-sessions';
if (!is_dir($sessionPath)) mkdir($sessionPath, 0777, true);
ini_set('session.save_path', $sessionPath);
if (config('database.database') !== 'couponhub_merge_test' || (int)config('database.port') !== 13379 || is_file(__DIR__.'/../config/local.php')) throw new RuntimeException('Unsafe test configuration');

use App\Core\Database;
use App\Core\Session;
use App\Core\Auth;
use App\Repositories\{UserRepository, CouponRepository, AdminCouponRepository, AdminRepository, CampaignRepository, CouponEventRepository};
use App\Services\{AccountService, CouponService, AdminCouponService, AdminService, CouponEventService};

function checkQuang(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: '.$message);
    echo 'PASS: '.$message."\n";
}
function rejectsQuang(callable $action, string $message): void {
    try { $action(); } catch (RuntimeException $e) { checkQuang(true, $message); return; }
    throw new RuntimeException('FAIL: accepted '.$message);
}
$db = Database::connection();
checkQuang((int)$db->query('SELECT @@port')->fetchColumn() === 13379, 'database isolation');
$users = new UserRepository($db);
$admin = (int)$users->findByLogin('admin_demo')['id'];
$user = (int)$users->findByLogin('user_demo')['id'];
$merchant = (int)$users->findByLogin('merchant_shops')['id'];
$coupons = new CouponRepository($db);
$repo = new AdminCouponRepository($db);
$validation = new CouponService($coupons);
$service = new AdminCouponService($repo, $validation);
$categories = new AdminRepository($db);
$categoryService = new AdminService($categories, new CampaignRepository($db));
$template = (int)$db->query("SELECT id FROM coupons WHERE moderation_status='APPROVED' AND publication_status='ENABLED' AND code IS NOT NULL LIMIT 1")->fetchColumn();
function cloneQuang(Database $db, int $template, string $suffix): int {
    $stmt = $db->prepare("INSERT INTO coupons(merchant_id,category_id,submitted_by,source,title,code,description,terms,discount_type,discount_value,cashback_unit,min_order_amount,max_discount_amount,usage_channel,promotion_url,starts_at,ends_at,moderation_status,publication_status) SELECT merchant_id,category_id,submitted_by,source,?, ?,description,terms,discount_type,discount_value,cashback_unit,min_order_amount,max_discount_amount,usage_channel,promotion_url,UTC_TIMESTAMP()-INTERVAL 1 DAY,UTC_TIMESTAMP()+INTERVAL 10 DAY,'APPROVED','ENABLED' FROM coupons WHERE id=?");
    $stmt->execute(['Quang review '.$suffix, 'QUANG_'.strtoupper($suffix), $template]);
    return (int)$db->lastInsertId();
}
function inputQuang(array $row): array {
    foreach (['starts_at','ends_at'] as $key) $row[$key] = (new DateTimeImmutable($row[$key], new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))->format('Y-m-d\TH:i:s');
    return $row;
}
$id = cloneQuang($db, $template, 'edit');
$data = inputQuang($repo->find($id));
foreach ([$user,$merchant] as $actor) rejectsQuang(fn()=>$service->update($actor,$id,$data), 'non-admin cannot edit coupon at write layer');
$db->prepare("UPDATE users SET status='LOCKED' WHERE id=?")->execute([$admin]);
rejectsQuang(fn()=>$service->delete($admin,$id,'Blocked account must never delete'), 'locked admin cannot delete');
$db->prepare("UPDATE users SET status='ACTIVE' WHERE id=?")->execute([$admin]);
$db->prepare("INSERT INTO votes(coupon_id,user_id,vote_type) VALUES(?,?,'WORKING')")->execute([$id,$user]);
$voteCount = fn()=>(int)$db->query('SELECT COUNT(*) FROM votes WHERE coupon_id='.$id)->fetchColumn();
$historyCount = fn()=>(int)$db->query('SELECT COUNT(*) FROM coupon_status_history WHERE coupon_id='.$id)->fetchColumn();
$service->update($admin,$id,$data);
checkQuang($voteCount()===1 && $historyCount()===0, 'unchanged save preserves votes and avoids false audit events, including seconds');
$data['title'] .= ' corrected';
$data['merchant_id'] = 999999;
$data['moderation_status'] = 'REJECTED';
$service->update($admin,$id,$data);
checkQuang($voteCount()===1 && $repo->find($id)['merchant_id']===$repo->find($template)['merchant_id'] && $repo->find($id)['moderation_status']==='APPROVED', 'title-only edit preserves votes; merchant/state input cannot move or reject coupon');
$data['terms'] .= ' Reviewed conditions.';
$service->update($admin,$id,$data);
checkQuang($voteCount()===0 && $historyCount()===2, 'material condition edit resets votes and writes audit');
$duplicate=cloneQuang($db,$template,'duplicate');
$bad=$data; $bad['code']=$repo->find($duplicate)['code'];
rejectsQuang(fn()=>$service->update($admin,$id,$bad), 'overlapping duplicate code rejected');
checkQuang($repo->find($id)['code']==='QUANG_EDIT' && $historyCount()===2, 'failed edit rolls back content and audit');
$bad=$data; $bad['title']=['injected'];
rejectsQuang(fn()=>$service->update($admin,$id,$bad), 'array input rejected');
$service->expire($admin,$id,'Merchant ended this isolated test offer');
checkQuang($repo->find($id)['publication_status']==='EXPIRED' && $coupons->findPublic($id)===null, 'forced expiry hides coupon publicly');
rejectsQuang(fn()=>$service->update($admin,$id,$data), 'manually expired coupon cannot be reactivated by edit');
$service->delete($admin,$id,'Remove expired isolated test coupon');
checkQuang($repo->find($id)===null && $historyCount()===4, 'soft deletion keeps four audit entries and hides administrative detail');
$rejected=cloneQuang($db,$template,'rejected');
$db->prepare("UPDATE coupons SET moderation_status='REJECTED',publication_status='DISABLED' WHERE id=?")->execute([$rejected]);
checkQuang(in_array($rejected,array_column($repo->all('REJECTED',1,100),'id'),true) && !in_array($rejected,array_column($repo->all('DISABLED',1,100),'id'),true), 'rejected moderation has its own filter, separate from disabled publication');
$before=$repo->count();
for($i=0;$i<105;$i++) cloneQuang($db,$template,'page_'.$i);
$all=[];
for($page=1;$page<=(int)ceil($repo->count()/25);$page++) $all=array_merge($all,array_column($repo->all(null,$page),'id'));
checkQuang($repo->count()===$before+105 && count(array_unique($all))===$repo->count() && in_array($template,$all,true), 'pagination exposes every coupon including records older than 100');

$category=(int)$repo->find($template)['category_id'];
rejectsQuang(fn()=>$categoryService->deleteCategory($admin,$category), 'cannot delete a referenced category');
$db->query("INSERT INTO categories(name,slug) VALUES('Review empty','quang-review-empty')"); $empty=(int)$db->lastInsertId();
rejectsQuang(fn()=>$categoryService->updateCategory($user,$empty,['name'=>'Hacked']), 'user cannot rename category');
rejectsQuang(fn()=>$categoryService->deleteCategory($merchant,$empty), 'merchant cannot delete category');
rejectsQuang(fn()=>$categoryService->updateCategory($admin,$empty,['name'=>['bad']]), 'category rejects array input');
$categoryService->updateCategory($admin,$empty,['name'=>'Review renamed','sort_order'=>'20','description'=>'Verified category']);
checkQuang($db->query('SELECT name FROM categories WHERE id='.$empty)->fetchColumn()==='Review renamed', 'admin category rename persists');
$categoryService->deleteCategory($admin,$empty);
checkQuang((int)$db->query('SELECT COUNT(*) FROM categories WHERE id='.$empty)->fetchColumn()===0, 'only empty category can be deleted');

$account=new AccountService($users);
$initial=$users->findById($user);
$account->updateProfile($user,'Review Profile','+84901234567');
checkQuang($users->findById($user)['phone']==='+84901234567', 'profile update persisted');
$account->updateProfile($user,'Review Profile','+84901234567');
checkQuang($users->findById($user)['full_name']==='Review Profile', 'unchanged profile accepted');
$db->prepare("UPDATE users SET status='LOCKED' WHERE id=?")->execute([$user]);
checkQuang(!$users->updateProfile($user,'Forbidden','+84901234567'), 'locked profile does not report a successful update');
$db->prepare("UPDATE users SET status='ACTIVE' WHERE id=?")->execute([$user]);
rejectsQuang(fn()=>$account->changePassword($user,'wrong','NewDemo!2026','NewDemo!2026'), 'wrong current password rejected');
$oldHash=$users->passwordHashForUser($user);
$resetRepo = new \App\Repositories\PasswordResetRepository($db);
$resetHash = hash('sha256', 'quang-isolated-pending-reset');
$resetRepo->save($user, $resetHash, hash('sha256', 'quang-test-ip'));
Session::start();
Auth::login(['id'=>$user,'role'=>'USER','full_name'=>'Review Profile','auth_version'=>$initial['auth_version']]);
$account->changePassword($user,'UserDemo!2026','NewDemo!2026','NewDemo!2026');
checkQuang(!$resetRepo->isUsable($resetHash) && !$resetRepo->consume($resetHash,password_hash('OldReset!2026',PASSWORD_DEFAULT)), 'password change revokes previously issued recovery links');
checkQuang(password_verify('NewDemo!2026',$users->passwordHashForUser($user)) && (int)$users->findById($user)['auth_version']===(int)$initial['auth_version']+1, 'password hash changed and session version incremented');
checkQuang(!$users->changePassword($user,$oldHash,password_hash('RaceDemo!2026',PASSWORD_DEFAULT)), 'stale concurrent password change rejected');
checkQuang(Auth::user()===null, 'old authenticated session invalid after password change');

$events=new CouponEventService(new CouponEventRepository($db));
$eventId=cloneQuang($db,$template,'copy');
checkQuang($events->recordCopy(null,$eventId) && !$events->recordCopy(null,$eventId), 'guest copy recorded once per session in 15 seconds');
$_SESSION['coupon_copy_last'][$eventId]=time()-16;
checkQuang($events->recordCopy($merchant,$eventId), 'copy throttle expires');
checkQuang((int)$db->query('SELECT COUNT(*) FROM coupon_events WHERE coupon_id='.$eventId)->fetchColumn()===2, 'copy persistence matches accepted events');
foreach([$id,$rejected,999999999] as $hidden) rejectsQuang(fn()=>$events->recordCopy(null,$hidden), 'unavailable coupon copy rejected');
$db->prepare("UPDATE coupons SET code=NULL,promotion_url='https://example.test/' WHERE id=?")->execute([$eventId]);
$_SESSION['coupon_copy_last']=[];
rejectsQuang(fn()=>$events->recordCopy(null,$eventId), 'code-less promotion cannot record COPY');
checkQuang($_SESSION['coupon_copy_last']===[], 'failed copy does not consume throttle');
echo "PASS: Quang database workflow review complete. Reset this disposable DB before other fixture-dependent tests.\n";

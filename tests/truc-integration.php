<?php
declare(strict_types=1);
// Các assertion có ghi chỉ được chạy trên MariaDB thử nghiệm cô lập.
if (getenv('COUPONHUB_DB') !== 'couponhub_merge_test' || getenv('COUPONHUB_PORT') !== '13379') exit("SKIP: isolated test DB required.\n");
require dirname(__DIR__) . '/app/bootstrap.php';
use App\Core\Database;
use App\Repositories\{CategoryRepository,CampaignRepository,SavedCouponRepository,CouponRepository,UserRepository};
use App\Services\{CouponService,InteractionService};

function expectTruc(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$db=Database::connection();
$categories=new CategoryRepository($db); $campaigns=new CampaignRepository($db); $saved=new SavedCouponRepository($db);
$users=new UserRepository($db); $coupons=new CouponRepository($db);
$user=(int)$users->findByLogin('user_demo')['id']; $other=(int)$users->findByLogin('user_community')['id'];
$admin=(int)$users->findByLogin('admin_demo')['id'];
$shop=(int)$db->query("SELECT id FROM merchants WHERE slug='demo-shopee'")->fetchColumn();
$counts=$categories->publicCounts();
expectTruc(count($counts)===8 && array_sum(array_column($counts,'coupon_count'))===26,'Category counts use active coupons only');
expectTruc($campaigns->publicListing('ALL',999)['page']===1,'Clamp campaign page');
$db->beginTransaction();
try {
    $db->prepare("INSERT INTO campaigns(created_by,name,slug,starts_at,ends_at,status) VALUES(?, 'Future test','truc-future',UTC_TIMESTAMP()+INTERVAL 2 DAY,UTC_TIMESTAMP()+INTERVAL 10 DAY,'PUBLISHED')")->execute([$admin]);
    $future=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO campaigns(created_by,name,slug,starts_at,ends_at,status) VALUES(?, 'Private test','truc-private',UTC_TIMESTAMP()-INTERVAL 1 DAY,UTC_TIMESTAMP()+INTERVAL 10 DAY,'DRAFT')")->execute([$admin]);
    $private=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO campaigns(created_by,name,slug,starts_at,ends_at,status) VALUES(?, 'Expired test','truc-expired',UTC_TIMESTAMP()-INTERVAL 10 DAY,UTC_TIMESTAMP()-INTERVAL 1 DAY,'PUBLISHED')")->execute([$admin]);
    $expired=(int)$db->lastInsertId();
    expectTruc($campaigns->publicListing('ALL',1)['total']===3,'Only published unexpired campaigns');
    expectTruc($campaigns->publicListing('ACTIVE',1)['total']===2,'Active phase');
    expectTruc($campaigns->publicListing('UPCOMING',1)['items'][0]['id']===$future,'Upcoming phase');
    expectTruc($campaigns->findPublic($private)===null && $campaigns->findPublic($expired)===null,'No private/expired fallback');
    $db->prepare("UPDATE merchants SET status='LOCKED' WHERE id=?")->execute([$shop]);
    expectTruc($campaigns->publicListing('ACTIVE',1)['total']===1,'Locked shop campaign excluded from list');
    expectTruc(count($campaigns->publicCurrent())===1,'Locked shop excluded from existing portal');
    $shopCampaign=(int)$db->query('SELECT id FROM campaigns WHERE owner_merchant_id IS NOT NULL LIMIT 1')->fetchColumn();
    expectTruc($campaigns->findPublic($shopCampaign)===null,'Locked shop excluded from detail');
    $cid=(int)$db->query('SELECT id FROM coupons ORDER BY id LIMIT 1')->fetchColumn();
    $db->prepare('DELETE FROM favorites WHERE user_id=? AND coupon_id=?')->execute([$user,$cid]);
    expectTruc($saved->savedIds($user,[$cid])===[] && $saved->savedIds($other,[$cid])===[$cid],'Favorites scoped to actor');
    expectTruc($saved->savedIds($user,[])===[],'Empty grid');
} finally { $db->rollBack(); }
$service=new CouponService($coupons);
$data=['merchant_id'=>$shop,'category_id'=>1,'title'=>'Integration workflow coupon','code'=>'TRUC_WORKFLOW',
    'description'=>'Isolated integration verification','terms'=>'Only for integration test',
    'discount_type'=>'PERCENTAGE','discount_value'=>10,'min_order_amount'=>100000,'max_discount_amount'=>30000,
    'usage_channel'=>'ONLINE','promotion_url'=>'https://example.test/offer',
    'starts_at'=>date('Y-m-d\TH:i',time()-86400),'ends_at'=>date('Y-m-d\TH:i',time()+864000)];
$id=$service->submitCommunity($user,$data);
expectTruc($coupons->findPublic($id)===null,'Submission remains private before review');
try { $service->moderate($admin,$id,'REJECT',''); throw new LogicException('Empty rejection reason accepted'); }
catch (RuntimeException $error) { expectTruc(str_contains($error->getMessage(),'10'),'Meaningful rejection reason required'); }
try { $service->moderate($user,$id,'APPROVE',null); throw new RuntimeException('User could moderate'); }
catch (mysqli_sql_exception $error) { expectTruc($error->getCode()===1644,'Admin guard at routine'); }
$service->moderate($admin,$id,'REJECT','Conditions need clarification in this isolated test.');
$mine=array_values(array_filter($coupons->findBySubmitter($user),static fn($c)=>$c['id']===$id))[0];
expectTruc($mine['moderation_status']==='REJECTED' && $mine['effective_status']==='DISABLED' && $mine['review_note']!=='','Rejection distinct from availability');
expectTruc($coupons->findPublic($id)===null,'Rejected coupon stays private');
expectTruc(safeHttpUrl('https://example.test/offer')!==null && safeHttpUrl('javascript:alert(1)')===null
    && safeHttpUrl('file:///etc/passwd')===null && safeHttpUrl('https://user:pass@example.test')===null,'Safe real source URLs');
try { $service->validateSubmission(array_replace($data,['promotion_url'=>'ftp://example.test/file'])); throw new RuntimeException('FTP accepted'); }
catch (App\Core\ValidationException $error) { expectTruc(isset($error->errors()['promotion_url']),'Server rejects unsafe source URL'); }
echo "PASS: category counts, campaign eligibility/filters/page bounds, actor favorites, submission/review/privacy and URL validation.\n";

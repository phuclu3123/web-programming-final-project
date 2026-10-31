<?php
declare(strict_types=1);

use App\Contracts\RoutineGateway;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Request;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\OAuthPreparation;
use App\Repositories\CatalogRepository;
use App\Services\AuthService;


// ---- TEST MYSQLI THẬT: CHỈ database thử cô lập, không dùng database của bạn ----
if (getenv('COUPONHUB_RUN_DB_TEST') !== '1' || getenv('COUPONHUB_DB') !== 'couponhub_merge_test'
    || getenv('COUPONHUB_PORT') !== '13379') { echo "SKIP: isolated test DB required.\n"; exit; }
require __DIR__ . '/../app/bootstrap.php';
if (config('database.database') !== 'couponhub_merge_test' || (int)config('database.port') !== 13379
    || is_file(__DIR__.'/../config/local.php')) throw new RuntimeException('Unsafe test database configuration');
session_save_path(sys_get_temp_dir());
function verify(bool $condition, string $label): void { if (!$condition) throw new RuntimeException('FAIL: ' . $label); }
$db = Database::connection();
verify((int)$db->query('SELECT @@port')->fetchColumn() === 13379, 'isolated database port');
verify((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0, 'this test requires schema only, without demo data');
$gateway = Database::connection();
verify(Database::connection() === App\Core\Database::connection(), 'one mysqli connection');
// Tham số routine phải thống nhất với bảng và kết nối theo vietnamese_ci.
$wrongCollations = $db->query("SELECT COUNT(*) FROM information_schema.PARAMETERS WHERE SPECIFIC_SCHEMA=DATABASE() AND COLLATION_NAME IS NOT NULL AND COLLATION_NAME<>'utf8mb4_vietnamese_ci' AND (SPECIFIC_NAME LIKE 'ch_%' OR SPECIFIC_NAME LIKE 'sp_%')")->fetchColumn();
verify((int) $wrongCollations === 0, 'routine string parameters use explicit Vietnamese collation');
verify($db->query('SELECT @@collation_connection')->fetchColumn() === 'utf8mb4_vietnamese_ci', 'mysqli connection uses Vietnamese collation');
$wrongColumns = $db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLLATION_NAME IS NOT NULL AND COLLATION_NAME<>'utf8mb4_vietnamese_ci'")->fetchColumn();
verify((int) $wrongColumns === 0, 'all text columns and views use Vietnamese collation');
verify(class_exists('App\\Core\\Session') && is_a('App\\Core\\Session', Session::class, true), 'one session class');
$probe = $db->prepare('SELECT ? AS first_value, ? AS optional_value, ? AS count_value');
$probe->bindValue(1, 'Tiếng Việt', 's'); $probe->bindValue(2, null); $probe->bindValue(3, 7, 'i');
$probe->execute(); $row = $probe->fetch();
verify($row['first_value'] === 'Tiếng Việt' && $row['optional_value'] === null && (int) $row['count_value'] === 7, 'bind unicode/null/int');

$users = new App\Repositories\UserRepository($db);
$controller = new AuthService($users);
$password = 'TestPass!123';
$base = ['full_name'=>'Test User', 'phone'=>'0901234567', 'password'=>$password, 'password_confirmation'=>$password];
$ids = [];
foreach (['user','admin','manager','other'] as $name) {
    $result = $controller->register($base + ['username'=>'test_'.$name, 'email'=>'test_'.$name.'@example.invalid']);
    verify($result['success'], 'register ' . $name);
    $ids[$name] = (int) $users->findByLogin('test_'.$name)['id'];
}
// ID provider là chuỗi định danh chính xác: khác hoa/thường vẫn là hai ID.
$oauthInsert = $db->prepare("INSERT INTO oauth_accounts(user_id,provider,provider_user_id) VALUES(?,'google',?)");
$oauthInsert->execute([$ids['user'],'CaseSensitiveIdentity']);
$oauthInsert->execute([$ids['other'],'casesensitiveidentity']);
$oauthCount = $db->prepare('SELECT COUNT(*) FROM oauth_accounts WHERE provider_user_id=?');
$oauthCount->execute(['CaseSensitiveIdentity']);
verify((int) $oauthCount->fetchColumn() === 1, 'binary provider identity retains exact case');
$duplicate = $controller->register($base + ['username'=>'test_user', 'email'=>'another@example.invalid']);
verify(!$duplicate['success'], 'UNIQUE race protected');
verify($users->findByLogin('test_user')['password_hash'] !== $password, 'hash stored');
verify($controller->login('test_user@example.invalid', $password)['success'], 'email login');
verify(!$controller->login('test_user', 'wrong')['success'], 'wrong password');
verify($controller->login('test_user', $password)['success'], 'username login');
verify(App\Core\Auth::id() === $ids['user'], 'module sees existing auth');
$db->prepare("UPDATE users SET role='ADMIN' WHERE id=?")->execute([$ids['admin']]);
$db->prepare("INSERT INTO categories(name,slug) VALUES('Test category','test-category')")->execute();
$categoryId = (int) $db->lastInsertId();
$db->prepare("INSERT INTO levels(name,min_points) VALUES('Newbie',0)")->execute();
$db->prepare("INSERT INTO point_rules(code,description,points,threshold_count) VALUES('COUPON_APPROVED','Test rule',5,1)")->execute();

// Cấp quyền shop qua repository transaction của Quang; không giả role ở client.
$merchants = new App\Repositories\MerchantRepository($db);
$access = new App\Repositories\MerchantAccessRepository($db);
$merchantId = $merchants->requestNew($ids['manager'], 'Test shop', 'test-shop', null, null, 'Test evidence');
$request = (int) $db->query("SELECT id FROM merchant_requests WHERE request_type='CREATE' LIMIT 1")->fetchColumn();
$access->review($ids['admin'], $request, 'APPROVE', 'Test verification');
$merchants->requestClaim($ids['other'], $merchantId, 'Other evidence');
$request = (int) $db->query("SELECT id FROM merchant_requests WHERE request_type='JOIN' LIMIT 1")->fetchColumn();
$access->review($ids['admin'], $request, 'APPROVE', 'Test shared management');
verify($controller->login('test_other', $password)['success'], 'second manager login');
$actor = Auth::requireRole(['MERCHANT'], $gateway);
Auth::requireMerchantOwner($gateway, $actor, $merchantId);
verify((int) $actor['id'] !== $ids['manager'], 'membership independent of primary owner');

// Form shop cũ -> wrapper -> sp_submit_coupon; UTC, duyệt và catalog dùng chung dữ liệu.
$coupon = new CatalogRepository($gateway);
$input = ['merchant_id'=>$merchantId,'category_id'=>$categoryId,'title'=>'Test offer','code'=>'TEST20',
    'description'=>'Test description','terms'=>'Test conditions','discount_type'=>'PERCENTAGE','discount_value'=>'20',
    'min_order_amount'=>'0','max_discount_amount'=>'','usage_channel'=>'BOTH','starts_on'=>date('Y-m-d'),
    'expires_on'=>date('Y-m-d', strtotime('+10 days'))];
$couponId = $coupon->create($ids['other'], $input);
verify($couponId > 0, 'legacy official submission');
$stored = $db->query('SELECT starts_at FROM coupons WHERE id=' . $couponId)->fetchColumn();
verify($stored === (new DateTimeImmutable($input['starts_on'].' 00:00:00', new DateTimeZone('Asia/Ho_Chi_Minh')))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'), 'legacy dates converted to UTC');
$coupon->approve($ids['admin'], $couponId);
$sets = $coupon->search('', 0, 0, '', '', false, 'newest');
verify(count($sets) === 3 && count($sets[2]) === 1, 'multi resultset catalog');
verify((int) $db->query('SELECT 1')->fetchColumn() === 1, 'CALL drained before next query');
[$shops, $categories, $owned, $stats] = $coupon->shop($ids['other']);
verify(count($shops) === 1 && count($owned) === 1 && (int) $stats[0]['approved'] === 1, 'legacy shop membership');
$nativeCoupons = new App\Repositories\CouponRepository($db);
$filters = (new App\Services\CouponService($nativeCoupons))->normalizeSearch([]);
verify(count($nativeCoupons->search($filters)) === 1, 'Quang search sees same coupon');
verify(count($nativeCoupons->hotDeals()) === 1, 'hotDeals derived is_official');
$interactions = new App\Services\InteractionService(new App\Repositories\InteractionRepository($db), $nativeCoupons);
verify($interactions->setFavorite($ids['user'], $couponId, true)['saved'], 'favorite via mysqli routine');
verify($interactions->setVote($ids['user'], $couponId, 'WORKING')['vote'] === 'WORKING', 'vote via mysqli routine');
$comment = $interactions->addComment($ids['user'], $couponId, 'Test comment');
verify((int) $comment['user_id'] === $ids['user'], 'comment transaction');
verify($interactions->createReport($ids['user'], $couponId, 'OTHER', 'Test report') > 0, 'report repository');
$saved = new App\Repositories\SavedCouponRepository($db);
$page = $saved->paginated($ids['user'], ['keyword'=>'','category_id'=>0,'status'=>'ALL','sort'=>'RECENT'], 1);
verify(count($page['items']) === 1 && $page['total'] === 1, 'saved pagination binds LIMIT/OFFSET with mysqli');
try { $interactions->setVote($ids['manager'], $couponId, 'WORKING'); throw new LogicException('Expected manager vote rejection'); }
catch (RuntimeException $error) { verify(!$error instanceof LogicException, 'active manager cannot vote own shop'); }
try { $coupon->create($ids['other'], $input); throw new RuntimeException('Expected duplicate'); }
catch (mysqli_sql_exception $error) { verify($error->getCode() === 1644, 'routine duplicate error'); }
verify((int) $db->query('SELECT 1')->fetchColumn() === 1, 'connection reusable after CALL error');

// Transaction state không rò giữa các repository, rollback không để lại hàng.
$db->beginTransaction();
$db->prepare("INSERT INTO categories(name,slug) VALUES('Rollback','rollback-test')")->execute();
$db->rollBack();
verify(!$db->inTransaction() && (int) $db->query("SELECT COUNT(*) FROM categories WHERE slug='rollback-test'")->fetchColumn() === 0, 'rollback');
$access->revoke($ids['admin'], $ids['other'], $merchantId, 'Test revocation');
verify(Auth::user($gateway)['role'] === 'USER', 'revocation reflected in same session');

// Token reset một lần + auth_version làm mất hiệu lực session đăng nhập cũ.
$tokens = new App\Repositories\PasswordResetRepository($db);
$controller->login('test_user', $password);
$rawToken = bin2hex(random_bytes(32));
$tokens->save($ids['user'], hash('sha256',$rawToken), hash('sha256','test-ip'));
verify($tokens->isUsable(hash('sha256',$rawToken)), 'reset token usable');
verify($tokens->consume(hash('sha256',$rawToken), password_hash('NewTestPass!123', PASSWORD_BCRYPT)), 'reset token consumed');
verify(!$tokens->consume(hash('sha256',$rawToken), 'unused'), 'reset token single use');
verify(Auth::user($gateway) === null, 'password reset invalidates old session');
verify($controller->login('test_user','NewTestPass!123')['success'], 'login with reset password');

// Các trang Admin từ Quang render bằng cùng bootstrap/Auth/DB; không chỉ reflect tên hàm.
$controller->login('test_admin', $password);
foreach ([App\Controllers\Admin\DashboardController::class, App\Controllers\Admin\UserController::class,
    App\Controllers\Admin\MerchantController::class, App\Controllers\Admin\MerchantAccessController::class,
    App\Controllers\Admin\CategoryController::class, App\Controllers\Admin\CouponController::class,
    App\Controllers\Admin\CampaignController::class, App\Controllers\Admin\ReportController::class,
    App\Controllers\Admin\ArticleController::class] as $class) {
    ob_start(); (new $class())->index(new Request([], [], ['REQUEST_METHOD'=>'GET', 'REQUEST_URI'=>'/admin'])); $html = ob_get_clean();
    verify(str_contains($html, 'test_admin') || str_contains($html, 'Test User'), 'Admin HTML ' . $class);
}
// Discovery Phase 5: verify SQL against real schema (only isolated DB, never demo/production).
$secondInput = $input;
$secondInput['code'] = 'TESTOTHER25';
$secondInput['title'] = 'Another test offer';
$secondInput['discount_value'] = '25';
$secondId = $coupon->create($ids['manager'], $secondInput);
$coupon->approve($ids['admin'], $secondId);
$pendingInput = $input;
$pendingInput['code'] = 'TESTPENDING';
$pendingInput['title'] = 'Unpublished test offer';
$pendingId = $coupon->create($ids['manager'], $pendingInput);
$recommendations = $nativeCoupons->relatedPublic($couponId, $merchantId, $categoryId, 'PERCENTAGE');
verify(in_array($secondId, array_map('intval', array_column($recommendations, 'id')), true), 'related active coupon included');
verify(!in_array($couponId, array_map('intval', array_column($recommendations, 'id')), true), 'related excludes current coupon');
verify(!in_array($pendingId, array_map('intval', array_column($recommendations, 'id')), true), 'related excludes pending coupon');
foreach (['VIEW', 'COPY', 'SHARE'] as $event) {
    $statement = $db->prepare('CALL sp_record_coupon_event(?, ?, ?, ?)');
    $statement->execute([$ids['user'], $couponId, $event, bin2hex(random_bytes(32))]);
    $statement->closeCursor();
}
$trends = $nativeCoupons->trendingPublic();
verify(count($trends) > 0 && (int) $trends[0]['id'] === $couponId, 'trending returns recent active coupon');
verify((int) $trends[0]['views_7d'] === 1 && (int) $trends[0]['copies_7d'] === 1
    && (int) $trends[0]['shares_7d'] === 1 && (int) $trends[0]['trend_score'] === 6,
    'trending weights VIEW 1 COPY 3 SHARE 2');
Session::logout();
echo "PASS: real mysqli binding/CALL/transactions, shared registration/login, membership, coupon moderation/search, reset/session and Admin render.\n";

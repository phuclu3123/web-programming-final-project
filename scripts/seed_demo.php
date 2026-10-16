<?php
declare(strict_types=1);

use App\Core\Database;
use App\Repositories\{UserRepository, MerchantRepository, MerchantAccessRepository, CouponRepository, InteractionRepository, CampaignRepository};
use App\Services\{AuthService, CouponService, InteractionService};

// Chỉ chạy có chủ đích trên máy local; không mở đường tạo Admin qua HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
if (!in_array('--confirm-demo', $argv, true)) {
    exit("Usage: php scripts/seed_demo.php --confirm-demo\nSee docs/DEMO_DATA.md.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';
$config = require dirname(__DIR__) . '/config/database.php';
if (!in_array($config['host'], ['127.0.0.1', 'localhost'], true)
    || !in_array($config['database'], ['dbcouponshubweb_v2', 'couponhub_merge_test'], true)) {
    throw new RuntimeException('Seed chỉ dành cho database demo local đã chỉ định.');
}
$fixture = require __DIR__ . '/demo_fixture.php';
$db = Database::connection();
$row = static function (string $sql, array $params = []) use ($db): array|false {
    $stmt = $db->prepare($sql); $stmt->execute($params); return $stmt->fetch();
};
$write = static function (string $sql, array $params = []) use ($db): void {
    $db->prepare($sql)->execute($params);
};
$users = new UserRepository($db);
$auth = new AuthService($users);
$merchants = new MerchantRepository($db);
$access = new MerchantAccessRepository($db);
$couponRepository = new CouponRepository($db);
$coupons = new CouponService($couponRepository);
$interactions = new InteractionService(new InteractionRepository($db), $couponRepository);
$campaigns = new CampaignRepository($db);
$created = ['accounts'=>0, 'merchants'=>0, 'coupons'=>0];
$ids = $shopIds = $categoryIds = $couponIds = [];

// Không chiếm tài khoản cùng username/email của người khác, không reset mật khẩu.
foreach ($fixture['accounts'] as $username => $account) {
    $existing = $row('SELECT * FROM users WHERE username=? OR email=?', [$username, $account['email']]);
    if ($existing && ($existing['username'] !== $username || $existing['email'] !== $account['email'])) {
        throw new RuntimeException('Username/email đã thuộc tài khoản khác: ' . $username);
    }
}
foreach ($fixture['accounts'] as $username => $account) {
    $existing = $users->findByLogin($username);
    if (!$existing) {
        $result = $auth->register(['username'=>$username, 'email'=>$account['email'],
            'full_name'=>$account['name'], 'phone'=>$account['phone'],
            'password'=>$account['password'], 'password_confirmation'=>$account['password']]);
        if (!$result['success']) throw new RuntimeException($result['message']);
        $existing = $users->findByLogin($username);
        // Chỉ nâng quyền Admin cho tài khoản vừa tạo bởi công cụ CLI này.
        if ($account['role'] === 'ADMIN') $write("UPDATE users SET role='ADMIN' WHERE id=?", [(int)$existing['id']]);
        ++$created['accounts'];
    }
    $ids[$username] = (int)$existing['id'];
}
$admin = $ids['admin_demo'];
if (($users->findByLogin('admin_demo')['role'] ?? '') !== 'ADMIN') {
    throw new RuntimeException('Admin demo hiện không có quyền Admin; seed không tự cấp lại quyền đã đổi.');
}

foreach (['Thành viên mới'=>0, 'Tích cực'=>100, 'Cộng tác viên'=>300, 'Chuyên gia'=>1000] as $name => $points) {
    if (!$row('SELECT id FROM levels WHERE min_points=? OR name=?', [$points, $name])) {
        $write('INSERT INTO levels(name,min_points) VALUES(?,?)', [$name, $points]);
    }
}
foreach ([['COUPON_APPROVED','Coupon cộng đồng được duyệt',10,1],
    ['WORKING_10','Coupon đạt mười xác nhận dùng được',5,10],
    ['FAVORITES_100','Coupon đạt một trăm lượt lưu hợp lệ',20,100]] as $rule) {
    if (!$row('SELECT code FROM point_rules WHERE code=?', [$rule[0]])) {
        $write('INSERT INTO point_rules(code,description,points,threshold_count) VALUES(?,?,?,?)', $rule);
    }
}
$sort = 0;
foreach ($fixture['categories'] as $slug => $name) {
    $category = $row('SELECT id FROM categories WHERE slug=?', [$slug]);
    if (!$category) {
        $write('INSERT INTO categories(name,slug,description,sort_order) VALUES(?,?,?,?)',
            [$name, $slug, 'Danh mục ưu đãi ' . mb_strtolower($name), ++$sort]);
        $category = ['id'=>$db->lastInsertId()];
    }
    $categoryIds[$slug] = (int)$category['id'];
}
foreach ($fixture['merchants'] as $platform => $shop) {
    $slug = 'demo-' . $platform;
    $merchant = $row('SELECT id FROM merchants WHERE slug=?', [$slug]);
    if (!$merchant) {
        $id = $merchants->requestNew($ids[$shop['owner']], $shop['name'], $slug,
            '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu ' . $shop['name'] . '.',
            null, 'DEMO-LOCAL-' . strtoupper($platform));
        $request = $row("SELECT id FROM merchant_requests WHERE merchant_id=? AND status='PENDING'", [$id]);
        $access->review($admin, (int)$request['id'], 'APPROVE', '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.');
        $merchant = ['id'=>$id]; ++$created['merchants'];
    }
    $shopIds[$platform] = (int)$merchant['id'];
}

$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
$utc = new DateTimeZone('UTC');
$addCoupon = static function (string $platform, array $offer, string $state = 'ACTIVE', ?string $author = null)
    use ($row, $write, $db, $fixture, $ids, $shopIds, $categoryIds, $coupons, $couponRepository, $admin, $now, $utc, &$created, &$couponIds): int {
    $merchant = $fixture['merchants'][$platform];
    $existing = $row('SELECT id FROM coupons WHERE merchant_id=? AND code_normalized=?', [$shopIds[$platform], $offer['code']]);
    if ($existing) return $couponIds[$offer['code']] = (int)$existing['id'];
    $input = $offer + ['min_order_amount'=>100000, 'max_discount_amount'=>30000, 'usage_channel'=>'ONLINE'];
    $input['merchant_id'] = $shopIds[$platform];
    $input['category_id'] = $categoryIds[$merchant['category']];
    $input['title'] = '[DEMO] ' . $offer['title'];
    $input['description'] = 'Dữ liệu thử nghiệm CouponHub theo nền tảng ' . $merchant['name'] . '. Mã minh họa, không dùng để thanh toán thật.';
    $input['terms'] = '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. '
        . 'Đơn tối thiểu ' . number_format($input['min_order_amount'], 0, ',', '.') . 'đ. '
        . ($input['max_discount_amount'] ? 'Ưu đãi tối đa ' . number_format($input['max_discount_amount'], 0, ',', '.') . 'đ. ' : '')
        . 'Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.';
    $input['cashback_unit'] = $offer['discount_type'] === 'CASHBACK' ? 'PERCENTAGE' : '';
    $input['starts_at'] = $now->modify($state === 'SCHEDULED' ? '+7 days' : '-1 day')->format('Y-m-d\TH:i');
    $input['ends_at'] = $now->modify('+' . ($offer['days'] ?? 90) . ' days')->setTime(23,59)->format('Y-m-d\TH:i');
    $id = $author === null ? $coupons->submitOfficial($ids[$merchant['owner']], $input)
        : $coupons->submitCommunity($ids[$author], $input);
    if ($state !== 'PENDING') {
        $coupons->moderate($admin, $id, $state === 'REJECTED' ? 'REJECT' : 'APPROVE',
            $state === 'REJECTED' ? '[DEMO] Điều kiện chưa rõ ràng, cần bổ sung trước khi duyệt.' : '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.');
    }
    if ($state === 'DISABLED') $couponRepository->moderate($admin, $id, 'APPROVED', 'DISABLED', '[DEMO] Shop tạm ngưng phát hành mã.');
    if ($state === 'EXPIRED') {
        // Riêng fixture lịch sử: form thật cố ý không cho gửi mã đã hết hạn.
        // Backdate đúng coupon vừa tạo và lịch sử của nó, không sửa coupon có sẵn.
        $start = $now->modify('-30 days')->setTimezone($utc)->format('Y-m-d H:i:s');
        $end = $now->modify('-3 days')->setTimezone($utc)->format('Y-m-d H:i:s');
        $createdAt = $now->modify('-35 days')->setTimezone($utc)->format('Y-m-d H:i:s');
        $reviewedAt = $now->modify('-34 days')->setTimezone($utc)->format('Y-m-d H:i:s');
        $write('UPDATE coupons SET starts_at=?,ends_at=?,created_at=?,reviewed_at=? WHERE id=?', [$start,$end,$createdAt,$reviewedAt,$id]);
        $write('UPDATE coupon_status_history SET created_at=? WHERE coupon_id=?', [$reviewedAt,$id]);
        $write("UPDATE activity_logs SET created_at=IF(action='COUPON_SUBMIT',?,?) WHERE coupon_id=?", [$createdAt,$reviewedAt,$id]);
    }
    ++$created['coupons']; return $couponIds[$offer['code']] = $id;
};
foreach ($fixture['merchants'] as $platform => $merchant) {
    foreach ($merchant['offers'] as $offer) $addCoupon($platform, $offer);
}
foreach ($fixture['scenarios'] as $scenario) {
    $addCoupon($scenario['platform'], $scenario + ['discount_type'=>'PERCENTAGE','discount_value'=>10],
        $scenario['state'], $scenario['author'] ?? null);
}

// Mỗi tương tác là dữ liệu thật qua service/repository, không bơm số đếm vào view.
foreach (['user_demo', 'user_community'] as $username) {
    foreach (['DEMO_SHOPEE_20', 'DEMO_LAZADA_50K', 'DEMO_GRAB_25'] as $code) {
        $uid = $ids[$username]; $cid = $couponIds[$code];
        if (!$row('SELECT 1 FROM favorites WHERE user_id=? AND coupon_id=?', [$uid,$cid])) $interactions->setFavorite($uid,$cid,true);
        if (!$row('SELECT 1 FROM votes WHERE user_id=? AND coupon_id=?', [$uid,$cid])) $interactions->setVote($uid,$cid,'WORKING');
    }
    foreach (['DEMO_SHOPEE_20', 'DEMO_LAZADA_50K'] as $code) {
        $cid = $couponIds[$code];
        $body = '[DEMO] Bình luận mẫu: đã kiểm tra đơn tối thiểu và mức giảm tối đa trong bản chạy thử.';
        if (!$row('SELECT id FROM comments WHERE user_id=? AND coupon_id=? AND body=?', [$uid,$cid,$body])) $interactions->addComment($uid,$cid,$body);
    }
    $cid = $couponIds[$username === 'user_demo' ? 'DEMO_BE_15K' : 'DEMO_FOOD_30'];
    if (!$row('SELECT id FROM reports WHERE reported_by=? AND coupon_id=?', [$uid,$cid])) {
        $interactions->createReport($uid,$cid,'WRONG_TERMS','[DEMO] Báo cáo mẫu để Admin kiểm tra quy trình phản hồi điều kiện coupon.');
    }
}
foreach ([['demo-couponhub-week','Tuần ưu đãi đa nền tảng [DEMO]',null,$admin,array_slice(array_keys($couponIds),0,8)],
    ['demo-shopee-official','Ưu đãi Shopee chính thức [DEMO]',$shopIds['shopee'],$ids['merchant_shops'],['DEMO_SHOPEE_20','DEMO_SHOPEE_30K']]] as [$slug,$name,$owner,$actor,$codes]) {
    if ($row('SELECT id FROM campaigns WHERE slug=?', [$slug])) continue;
    $id = $campaigns->create($actor,$owner,$name,$slug,'[DEMO] Chiến dịch mẫu, các ưu đãi không áp dụng ngoài hệ thống thử nghiệm.',
        $now->modify('-1 day')->setTimezone($utc)->format('Y-m-d H:i:s'),
        $now->modify('+30 days')->setTimezone($utc)->format('Y-m-d H:i:s'),'PUBLISHED');
    foreach ($codes as $code) $campaigns->attach($actor,$id,$couponIds[$code]);
}
echo 'Database: ' . $config['database'] . PHP_EOL;
echo 'Created: ' . json_encode($created, JSON_UNESCAPED_UNICODE) . PHP_EOL;
foreach (['users','merchants','merchant_memberships','categories','coupons','campaigns','favorites','votes','comments','reports','point_transactions'] as $table) {
    echo $table . ': ' . $db->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() . PHP_EOL;
}
echo "Done. Accounts and usage: docs/DEMO_DATA.md\n";

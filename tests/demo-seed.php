<?php
declare(strict_types=1);

// Read-only assertions for a freshly seeded isolated test DB.
if (getenv('COUPONHUB_DB') !== 'couponhub_merge_test' || getenv('COUPONHUB_PORT') !== '13379') {
    exit("SKIP: isolated demo DB required.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';
$db = App\Core\Database::connection();
$fixture = require dirname(__DIR__) . '/scripts/demo_fixture.php';
$users = new App\Repositories\UserRepository($db);
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
foreach (['users'=>6,'merchants'=>12,'merchant_memberships'=>12,'categories'=>8,'coupons'=>32,
    'campaigns'=>2,'campaign_coupons'=>10,'favorites'=>6,'votes'=>6,'comments'=>4,'reports'=>2,'point_transactions'=>2] as $table=>$count) {
    $check((int)$db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn()===$count, 'Count: '.$table);
}
foreach ($fixture['accounts'] as $username=>$account) {
    $user = $users->findByLogin($username);
    $check($user !== null && $user['role']===$account['role'] && password_verify($account['password'],$user['password_hash']), 'Identity/password/role: '.$username);
    $check($users->findByLogin($account['email'])['id']===$user['id'], 'Email login: '.$username);
}
$check((int)$db->query("SELECT COUNT(*) FROM v_coupon_status WHERE effective_status='ACTIVE'")->fetchColumn()===26, 'Public active coupons');
$check((int)$db->query("SELECT COUNT(*) FROM coupons WHERE moderation_status='PENDING'")->fetchColumn()===2, 'Admin pending queue');
$check((int)$db->query("SELECT COUNT(*) FROM coupons WHERE moderation_status='REJECTED'")->fetchColumn()===1, 'Admin rejected queue');
$check((int)$db->query("SELECT COUNT(*) FROM v_coupon_status WHERE effective_status='SCHEDULED'")->fetchColumn()===1, 'Scheduled coupon');
$check((int)$db->query("SELECT COUNT(*) FROM v_coupon_status WHERE effective_status='EXPIRED'")->fetchColumn()===1, 'Expired history');
$check((int)$db->query("SELECT COUNT(*) FROM coupons WHERE title NOT LIKE '[DEMO]%' OR code NOT LIKE 'DEMO!_%' ESCAPE '!'")->fetchColumn()===0, 'Clear demo labels');
$check((int)$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND COLLATION_NAME IS NOT NULL AND COLLATION_NAME<>'utf8mb4_vietnamese_ci'")->fetchColumn()===0, 'Vietnamese column/view collation');
$check((int)$db->query("SELECT COUNT(*) FROM information_schema.PARAMETERS WHERE SPECIFIC_SCHEMA=DATABASE() AND COLLATION_NAME IS NOT NULL AND COLLATION_NAME<>'utf8mb4_vietnamese_ci'")->fetchColumn()===0, 'Vietnamese routine collation');
foreach ($fixture['merchants'] as $platform=>$merchant) {
    $stmt=$db->prepare("SELECT u.username FROM merchants m JOIN merchant_memberships mm ON mm.merchant_id=m.id JOIN users u ON u.id=mm.user_id WHERE m.slug=? AND m.status='APPROVED' AND mm.status='ACTIVE'");
    $stmt->execute(['demo-'.$platform]); $check($stmt->fetchColumn()===$merchant['owner'], 'Membership: '.$platform);
}
// Check actual business rights: one Merchant cannot update a different shop.
$repo = new App\Repositories\MerchantRepository($db);
$actor=(int)$users->findByLogin('merchant_shops')['id'];
$grab=(int)$db->query("SELECT id FROM merchants WHERE slug='demo-grab'")->fetchColumn();
$check($repo->findOwnedApproved($grab,$actor)===null, 'Cross-shop ownership denied');
echo "PASS: demo identities, password hashes, roles, ownership, workflow states, labels and collations.\n";

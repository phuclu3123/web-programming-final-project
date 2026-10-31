<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

function personalizedCheck(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
}

$contract = new ReflectionClass(App\Contracts\CouponRepositoryInterface::class);
$repo = new ReflectionClass(App\Repositories\CouponRepository::class);
personalizedCheck($contract->hasMethod('personalizedForUser'), 'repository contract');
personalizedCheck($repo->hasMethod('personalizedForUser'), 'repository method');

// Static security/schema checks: data is filtered by the current authenticated
// user's ID, the public status view, and absence from that user's favorites.
$sql = (string) file_get_contents(__DIR__ . '/../app/repositories/CouponRepository.php');
foreach ([
    'WHERE f.user_id=?',
    'AND NOT EXISTS (SELECT 1 FROM favorites own WHERE own.user_id=? AND own.coupon_id=c.id)',
    "c.effective_status COLLATE utf8mb4_vietnamese_ci = _utf8mb4'ACTIVE'",
    'JOIN coupons fc ON fc.id=f.coupon_id',
    'LEAST(COALESCE(mp.saved_count,0),3)*4',
    'LEAST(COALESCE(cp.saved_count,0),3)*2',
    'ORDER BY match_score DESC',
] as $token) personalizedCheck(str_contains($sql, $token), 'query contains ' . $token);
$controller = (string) file_get_contents(__DIR__ . '/../app/controllers/PortalController.php');
personalizedCheck(str_contains($controller, "(\$viewer['role'] ?? '') === 'USER'"), 'personalization limited to authenticated USER');
personalizedCheck(str_contains($controller, 'Auth::user($db)'), 'viewer comes from authoritative auth');

// Existing homepage rendering with a malicious coupon name. Guests, users
// without favorites, and users with no matching ACTIVE candidates see no section.
$currentCampaigns = []; $upcomingCampaigns = []; $hotDeals = [];
$trendingDeals = []; $popularMerchants = []; $recentArticles = [];
$personalizedDeals = [[
    'id' => 88,
    'title' => '<img src=x onerror=alert(1)>',
    'merchant_name' => '<script>alert(2)</script>', 'category_name' => 'Giải trí',
    'discount_type' => 'FIXED_AMOUNT', 'discount_value' => 30000,
    'cashback_unit' => null, 'usage_channel' => 'ONLINE',
    'match_reason' => 'MERCHANT_CATEGORY',
]];
ob_start();
try { require __DIR__ . '/../app/views/home/index.php'; $html = (string) ob_get_contents(); }
finally { ob_end_clean(); }
personalizedCheck(str_contains($html, 'Gợi ý dành cho bạn'), 'personalized section shown');
personalizedCheck(str_contains($html, '/coupons/88'), 'public coupon link shown');
personalizedCheck(str_contains($html, 'Cùng cửa hàng và danh mục bạn quan tâm'), 'reason shown');
personalizedCheck(str_contains($html, '&lt;script&gt;alert(2)&lt;/script&gt;'), 'merchant name escaped');
personalizedCheck(!str_contains($html, '<img src=x onerror=alert(1)>') && !str_contains($html, '<script>alert(2)</script>'), 'HTML injection escaped');
$personalizedDeals = [];
ob_start();
try { require __DIR__ . '/../app/views/home/index.php'; $empty = (string) ob_get_contents(); }
finally { ob_end_clean(); }
personalizedCheck(!str_contains($empty, 'Gợi ý dành cho bạn'), 'no-favorites section hidden');
echo "PASS: Phase 7 personalized feed contract, scoping, eligibility, escaped HTML and empty state.\n";

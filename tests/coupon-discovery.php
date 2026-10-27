<?php
declare(strict_types=1);

/** Lightweight no-database regression tests for brief.md sections 13 and 14. */
require __DIR__ . '/../app/bootstrap.php';

function discoveryCheck(bool $ok, string $description): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $description);
}

$contract = new ReflectionClass(App\Contracts\CouponRepositoryInterface::class);
$repository = new ReflectionClass(App\Repositories\CouponRepository::class);
foreach (['trendingPublic', 'relatedPublic'] as $method) {
    discoveryCheck($contract->hasMethod($method), 'repository contract: ' . $method);
    discoveryCheck($repository->hasMethod($method), 'repository implements: ' . $method);
}

// SQL and schema compatibility checks are static; live mysqli coverage remains required.
$source = file_get_contents(__DIR__ . '/../app/repositories/CouponRepository.php');
$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
discoveryCheck(is_string($source) && is_string($schema), 'source/schema loaded');
foreach (['coupon_events', 'occurred_at', "'VIEW'", "'COPY'", "'SHARE'", 'INTERVAL 7 DAY', 'GROUP BY coupon_id', 'trend_score DESC', 'idx_events_time'] as $token) {
    $text = $token === 'idx_events_time' ? $schema : $source;
    discoveryCheck(str_contains($text, $token), 'trend source/schema includes ' . $token);
}
discoveryCheck(substr_count($source, "c.effective_status COLLATE utf8mb4_vietnamese_ci = _utf8mb4'ACTIVE'") >= 4,
    'trending/related must filter ACTIVE coupons');
discoveryCheck(str_contains($source, 'c.id<>?') && str_contains($source, 'relevance_score DESC'),
    'related coupons must exclude the current coupon and rank matches');

// Render homepage content with a malicious coupon title and verify escaping.
$currentCampaigns = []; $upcomingCampaigns = []; $hotDeals = [];
$popularMerchants = []; $recentArticles = [];
$trendingDeals = [[
    'id' => 34, 'title' => '<img src=x onerror=alert(1)>',
    'merchant_name' => 'CGV', 'category_name' => 'Giải trí',
    'discount_type' => 'FIXED_AMOUNT', 'discount_value' => 30000,
    'cashback_unit' => null, 'views_7d' => 3, 'copies_7d' => 2, 'shares_7d' => 1,
]];
ob_start();
try { require __DIR__ . '/../app/views/home/index.php'; $homepage = (string) ob_get_contents(); }
finally { ob_end_clean(); }
discoveryCheck(str_contains($homepage, 'Coupon thịnh hành trong 7 ngày'), 'trending section appears');
discoveryCheck(str_contains($homepage, '/coupons/34'), 'trending card links to actual coupon');
discoveryCheck(str_contains($homepage, '3 xem · 2 copy · 1 chia sẻ'), 'trending counts visible');
discoveryCheck(!str_contains($homepage, '<img src=x onerror=alert(1)>'), 'malicious title escaped');
$trendingDeals = [];
ob_start();
try { require __DIR__ . '/../app/views/home/index.php'; $homepageEmpty = (string) ob_get_contents(); }
finally { ob_end_clean(); }
discoveryCheck(!str_contains($homepageEmpty, 'Coupon thịnh hành trong 7 ngày'), 'empty trending section hidden');

// Coupon detail related-card content must be escaped and remain distinct from current coupon.
$coupon = [
    'id' => 34, 'submitted_by' => 1, 'merchant_id' => 8, 'merchant_name' => 'CGV',
    'category_name' => 'Giải trí', 'is_official' => 1, 'title' => 'CGV giảm 30.000đ',
    'discount_type' => 'FIXED_AMOUNT', 'discount_value' => 30000,
    'min_order_amount' => 150000, 'max_discount_amount' => null,
    'usage_channel' => 'ONLINE', 'ends_at' => '2026-11-15 05:00:00',
    'starts_at' => '2026-10-27 00:00:00', 'code' => 'CGVTEST30K26',
    'description' => '', 'terms' => 'Điều kiện thử nghiệm',
    'promotion_url' => null, 'submitter_name' => 'Demo Merchant',
];
$interactionState = [ 'saved' => false, 'comments' => [], 'stats' => [], 'voteRestriction' => null ];
$relatedCoupons = [[
    'id' => 35, 'title' => '<script>alert(1)</script>', 'merchant_name' => 'CGV',
    'discount_type' => 'PERCENTAGE', 'discount_value' => 10,
    'cashback_unit' => null, 'ends_at' => '2026-11-10 05:00:00',
]];
if (function_exists('mb_strtolower')) {
ob_start();
try { require __DIR__ . '/../app/views/coupons/show.php'; $detail = (string) ob_get_contents(); }
finally { ob_end_clean(); }
discoveryCheck(str_contains($detail, 'Coupon liên quan'), 'related section appears');
discoveryCheck(str_contains($detail, '/coupons/35'), 'related card links to matching coupon');
discoveryCheck(!str_contains($detail, '<script>alert(1)</script>'), 'related title escaped');
$relatedCoupons = [];
ob_start();
try { require __DIR__ . '/../app/views/coupons/show.php'; $detailEmpty = (string) ob_get_contents(); }
finally { ob_end_clean(); }
discoveryCheck(!str_contains($detailEmpty, 'Coupon liên quan'), 'empty related section hidden');
 } else {
    // The existing logo helper needs mbstring. CI runners without mbstring can
    // still exercise the homepage; full detail rendering requires mbstring.
    discoveryCheck(str_contains(file_get_contents(__DIR__ . '/../app/views/coupons/show.php'), 'Coupon liên quan'),
        'related template present (detail render skipped: missing mbstring)');
    echo "SKIP: related-detail rendering requires PHP mbstring extension.\n";
}

echo "PASS: discovery contracts, schema references, public-visibility predicates, and escaped HTML (no database).\n";

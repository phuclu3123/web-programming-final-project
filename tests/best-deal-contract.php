<?php

declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

function toolCheck(bool $ok, string $description): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $description);
}

$contract = new ReflectionClass(App\Contracts\CouponRepositoryInterface::class);
$repo = new ReflectionClass(App\Repositories\CouponRepository::class);
toolCheck($contract->hasMethod('publicDiscountsForMerchant'), 'interface includes calculator query');
toolCheck($repo->hasMethod('publicDiscountsForMerchant'), 'repository includes calculator query');
$routes = (string)file_get_contents(__DIR__.'/../config/routes.php');
toolCheck(str_contains($routes, "'/tools/best-deal'"), 'public tool route registered');
$sql = (string)file_get_contents(__DIR__.'/../app/repositories/CouponRepository.php');
foreach (['c.merchant_id=?', "c.currency='VND'", "'ACTIVE'", "'FREE_SHIPPING'", "c.usage_channel=?"] as $needle) {
    toolCheck(str_contains($sql, $needle), 'repository filters ' . $needle);
}

// The standalone view renders from already-computed data; verify HTML escaping.
$merchants = [(object)['id' => 8, 'name' => '<img src=x onerror=alert(1)>']];
$merchantInput = '8';
$merchantName = '<script>alert(2)</script>';
$merchantId = 8;
$orderInput = '650000';
$shippingInput = '30000';
$channel = 'ONLINE';
$errors = [];
$submitted = true;
$order = 650000;
$shipping = 30000;
$comparison = [
    'eligible' => [[
        'id' => 34, 'title' => '<script>alert(1)</script>',
        'discount_type' => 'FIXED_AMOUNT', 'estimated_saving' => 30000,
        'estimated_payable' => 650000,
    ]],
    'excluded' => 0,
];
ob_start();
try { require __DIR__.'/../app/views/coupons/best-deal.php'; $html = (string)ob_get_contents(); }
finally { ob_end_clean(); }
toolCheck(str_contains($html, 'Tìm coupon tiết kiệm nhất'), 'tool visible');
toolCheck(str_contains($html, '/coupons/34'), 'public coupon link');
toolCheck(!str_contains($html, '<script>alert(') && !str_contains($html, '<img src=x onerror='), 'all titles escaped');
toolCheck(str_contains($html, '&lt;script&gt;alert(1)&lt;/script&gt;'), 'encoded coupon title shown');

$comparison = ['eligible' => [], 'excluded' => 1];
ob_start();
try { require __DIR__.'/../app/views/coupons/best-deal.php'; $empty = (string)ob_get_contents(); }
finally { ob_end_clean(); }
toolCheck(str_contains($empty, 'Không có coupon đang hoạt động'), 'empty-state message present');
echo "PASS: calculator public route, repository predicates, escaped view and empty state.\n";

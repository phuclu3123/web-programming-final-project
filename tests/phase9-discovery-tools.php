<?php

declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use App\Services\BestDealCalculator;
use App\Services\CouponStackChecker;
use App\Services\SmartCouponMatcher;

function p9Check(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
}

$matcher = new SmartCouponMatcher();
p9Check($matcher->budgetFromText('Tôi muốn mua giày Nike khoảng 2 triệu.') === 2000000, '2 triệu budget');
p9Check($matcher->budgetFromText('Tìm quần giá 500k') === 500000, '500k budget');
p9Check($matcher->budgetFromText('Tìm deal') === null, 'no budget');
p9Check(in_array('nike', $matcher->keywords('Tôi muốn mua giày Nike khoảng 2 triệu'), true), 'brand keyword');
p9Check(in_array('giay', $matcher->keywords('Tôi muốn mua giày Nike khoảng 2 triệu'), true), 'accent-insensitive keyword');

$base = [
    'id'=>1, 'title'=>'Nike giày chạy giảm 20%', 'description'=>'Mua giày thể thao',
    'merchant_name'=>'Nike', 'category_name'=>'Thời trang', 'merchant_id'=>1,
    'discount_type'=>'PERCENTAGE', 'discount_value'=>20, 'min_order_amount'=>500000,
    'max_discount_amount'=>100000, 'currency'=>'VND', 'usage_channel'=>'BOTH',
    'ends_at'=>'2026-11-12 00:00:00', 'effective_status'=>'ACTIVE',
];
$irrelevant = $base;
$irrelevant['id']=2; $irrelevant['title']='CGV phim'; $irrelevant['merchant_name']='CGV';
$irrelevant['description']='Vé xem phim'; $irrelevant['category_name']='Giải trí';
$inactive = $base; $inactive['id']=3; $inactive['effective_status']='PENDING';
$tooExpensive = $base; $tooExpensive['id']=4; $tooExpensive['min_order_amount']=2500000;
$otherCurrency = $base; $otherCurrency['id']=5; $otherCurrency['currency']='USD';
$matches = $matcher->recommend([$irrelevant, $inactive, $tooExpensive, $otherCurrency, $base], 'Tôi muốn mua giày Nike khoảng 2 triệu', 2000000, 'ONLINE');
p9Check(count($matches)===1 && $matches[0]['id']===1, 'rank lexical and reject ineligible coupons');
p9Check($matches[0]['estimated_saving']===100000, 'respect percent cap in matching');
p9Check($matcher->recommend([$base], 'CGV phim', 600000, 'ONLINE')===[], 'do not invent relevance');
p9Check($matcher->recommend([$base], 'Nike', 100000, 'ONLINE')===[], 'minimum spend enforced');

$shop = $base;
$shop['discount_type']='FIXED_AMOUNT'; $shop['discount_value']='80000';
$shop['max_discount_amount']=null;
$shipping = $base;
$shipping['id']=2; $shipping['title']='Miễn phí ship'; $shipping['discount_type']='FREE_SHIPPING';
$shipping['discount_value']=null; $shipping['usage_channel']='ONLINE';
$shipping['min_order_amount']=0; $shipping['max_discount_amount']=20000;
$payment = $base;
$payment['id']=3; $payment['title']='Ưu đãi thẻ ngân hàng';
$payment['discount_type']='PERCENTAGE'; $payment['discount_value']=10;
$checker = new CouponStackChecker();
$res = $checker->check(['shop'=>1,'shipping'=>2,'payment'=>3], [$shop,$shipping,$payment], 650000, 30000, 'ONLINE');
p9Check($res['status']==='UNVERIFIED', 'must not guarantee stacked coupons');
p9Check($res['maximum_saving']===165000 && $res['minimum_payable']===515000, 'theoretical ceiling with independent per-coupon quotes');
$res = $checker->check(['shop'=>1,'shipping'=>2,'payment'=>0], [$shop,$shipping], 650000, 30000, 'ONLINE');
p9Check($res['status']==='UNVERIFIED' && $res['maximum_saving']===100000, 'shop + freeship provisional');
$res = $checker->check(['shop'=>1,'shipping'=>0,'payment'=>0], [$shop], 650000, 30000, 'ONLINE');
p9Check($res['status']==='SINGLE', 'single code not a stack');
$res = $checker->check(['shop'=>1,'shipping'=>1,'payment'=>0], [$shop], 650000, 30000, 'ONLINE');
p9Check($res['status']==='INCOMPATIBLE', 'duplicate coupon blocked');
$res = $checker->check(['shop'=>2,'shipping'=>0,'payment'=>0], [$shipping], 650000, 30000, 'ONLINE');
p9Check($res['status']==='INCOMPATIBLE', 'shipping code cannot be a shop discount');
$res = $checker->check(['shop'=>999,'shipping'=>0,'payment'=>0], [$shop], 650000, 30000, 'ONLINE');
p9Check($res['status']==='INCOMPATIBLE', 'off-merchant or inactive ID blocked');
$res = $checker->check(['shop'=>0,'shipping'=>2,'payment'=>0], [$shipping], 650000, 0, 'ONLINE');
p9Check($res['status']==='INCOMPATIBLE', 'zero shipping cannot be discounted');
$res = $checker->check(['shop'=>0,'shipping'=>2,'payment'=>0], [$shipping], 650000, 30000, 'OFFLINE');
p9Check($res['status']==='INCOMPATIBLE', 'freeship not valid offline');
$over = $payment; $over['discount_value']=100; $over['max_discount_amount']=null;
$res = $checker->check(['shop'=>1,'shipping'=>0,'payment'=>3], [$shop,$over], 650000, 0, 'ONLINE');
p9Check($res['maximum_saving']===650000 && $res['minimum_payable']===0, 'stacking simulation cannot produce negative payable');

$repo = new ReflectionClass(App\Repositories\CouponRepository::class);
$contract = new ReflectionClass(App\Contracts\CouponRepositoryInterface::class);
p9Check($repo->hasMethod('publicMatchingCandidates') && $contract->hasMethod('publicMatchingCandidates'), 'repository and interface matching method');
$source = file_get_contents(__DIR__.'/../app/repositories/CouponRepository.php');
p9Check(str_contains($source, "'ACTIVE'") && str_contains($source, 'LIMIT 500'), 'active filtered bounded pool');
$routes = file_get_contents(__DIR__.'/../config/routes.php');
p9Check(str_contains($routes, "'/tools/match'") && str_contains($routes, "'/tools/stack-checker'"), 'public routes registered');

// HTML encoding on user supplied input and database coupon title.
$merchants = [(object)['id'=>1,'name'=>'<img src=x onerror=alert(1)>']];
$categories = [(object)['id'=>2,'name'=>'Thời trang']];
$input = ['q'=>'<script>alert(1)</script>', 'merchant_id'=>'1', 'category_id'=>'2', 'order_amount'=>'650000', 'channel'=>'ONLINE'];
$errors=[]; $submitted=true; $budget=650000;
$matches = [$base]; $matches[0]['title'] = '<script>alert(1)</script>'; $matches[0]['estimated_saving']=100000;
ob_start();
try { require __DIR__.'/../app/views/coupons/match.php'; $html=(string)ob_get_contents(); }
finally { ob_end_clean(); }
p9Check(str_contains($html, 'Tìm coupon phù hợp với nhu cầu') && str_contains($html, '/coupons/1'), 'matching page usable');
p9Check(!str_contains($html, '<script>alert(1)</script>') && !str_contains($html, '<img src=x onerror='), 'matching escaped');

$input = ['merchant_id'=>'1','order_amount'=>'650000','shipping_fee'=>'30000','channel'=>'ONLINE','shop'=>'1','shipping'=>'2','payment'=>''];
$options = [$shop,$shipping];
$outcome = $checker->check(['shop'=>1,'shipping'=>2,'payment'=>0], $options, 650000, 30000, 'ONLINE');
$options[0]['title']='<script>alert(2)</script>';
$outcome['details'][0]['title']='<script>alert(3)</script>';
ob_start();
try { require __DIR__.'/../app/views/coupons/stack-checker.php'; $html=(string)ob_get_contents(); }
finally { ob_end_clean(); }
p9Check(str_contains($html,'Trần tiết kiệm lý thuyết') && str_contains($html, 'Chưa thể xác minh khả năng cộng dồn'), 'stack results visibly provisional');
p9Check(!str_contains($html, '<script>alert(') && !str_contains($html, '<img src=x onerror='), 'stack results escaped');

echo "PASS: Phase 9 Smart Matching, conservative Stack Checker, bounds, routes and XSS controls.\n";

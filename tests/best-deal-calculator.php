<?php

declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';

use App\Services\BestDealCalculator;

function quoteCheck(bool $ok, string $description): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $description);
}

$calculator = new BestDealCalculator();
foreach (['-1', '1.5', '1e6', '1,000', '1000000001', '<script>', '', '000000000000'] as $bad) {
    quoteCheck($calculator->parseAmount($bad, BestDealCalculator::MAX_ORDER) === null, 'reject invalid money ' . $bad);
}
quoteCheck($calculator->parseAmount('000650000', BestDealCalculator::MAX_ORDER) === 650000, 'accept leading-zero integers');
quoteCheck($calculator->parseAmount('0', BestDealCalculator::MAX_SHIPPING) === 0, 'allow zero shipping');
quoteCheck($calculator->parseAmount('1000000000', BestDealCalculator::MAX_ORDER) === 1000000000, 'max order included');

$base = [
    'id' => 1, 'title' => 'Demo', 'discount_type' => 'PERCENTAGE', 'discount_value' => '15.00',
    'min_order_amount' => '500000.00', 'max_discount_amount' => '100000.00',
    'currency' => 'VND', 'usage_channel' => 'BOTH', 'ends_at' => '2026-11-10',
];
$q = $calculator->estimate($base, 650000, 30000, 'ONLINE');
quoteCheck($q['eligible'] && $q['saving'] === 97500 && $q['payable'] === 582500, '15 percent on 650k + 30k shipping');
quoteCheck(!$calculator->estimate($base, 499999, 30000, 'ONLINE')['eligible'], 'minimum order cannot include shipping');
$q = $calculator->estimate($base, 1000000, 20000, 'OFFLINE');
quoteCheck($q['saving'] === 100000 && $q['payable'] === 920000, 'maximum cap applies');

$fixed = $base;
$fixed['discount_type'] = 'FIXED_AMOUNT';
$fixed['discount_value'] = '80000.00';
$fixed['max_discount_amount'] = null;
quoteCheck($calculator->estimate($fixed, 650000, 30000, 'ONLINE')['saving'] === 80000, 'fixed amount 80k');
$fixed['min_order_amount'] = '0';
$fixed['discount_value'] = '800000';
quoteCheck($calculator->estimate($fixed, 50000, 0, 'ONLINE')['saving'] === 50000, 'discount cannot exceed order subtotal');
$fixed['discount_value'] = '80000';
$fixed['max_discount_amount'] = '50000';
quoteCheck($calculator->estimate($fixed, 650000, 0, 'ONLINE')['saving'] === 50000, 'fixed cap applies');

$ship = $base;
$ship['discount_type'] = 'FREE_SHIPPING';
$ship['discount_value'] = null;
$ship['min_order_amount'] = '0';
$ship['max_discount_amount'] = '20000';
$ship['usage_channel'] = 'ONLINE';
quoteCheck($calculator->estimate($ship, 650000, 30000, 'ONLINE')['saving'] === 20000, 'free shipping respects shipping cap');
quoteCheck(!$calculator->estimate($ship, 650000, 30000, 'OFFLINE')['eligible'], 'online shipping not for offline channel');
quoteCheck(!$calculator->estimate($ship, 650000, 0, 'ONLINE')['eligible'], 'zero shipping does not earn savings');

$cashback = $base;
$cashback['discount_type'] = 'CASHBACK';
quoteCheck(!$calculator->estimate($cashback, 650000, 0, 'ONLINE')['eligible'], 'cashback never treated as immediate payment reduction');
$invalid = $base;
$invalid['discount_value'] = '101';
quoteCheck(!$calculator->estimate($invalid, 650000, 0, 'ONLINE')['eligible'], 'invalid percentage rejected');
$otherCurrency = $base;
$otherCurrency['currency'] = 'USD';
quoteCheck(!$calculator->estimate($otherCurrency, 650000, 0, 'ONLINE')['eligible'], 'other currency rejected');

$fixed['id'] = 3;
$fixed['discount_value'] = '80000';
$fixed['max_discount_amount'] = null;
$fixed['ends_at'] = '2026-11-15';
$base['id'] = 2;
$quote = $calculator->compare([$fixed, $base, $cashback], 650000, 30000, 'ONLINE');
quoteCheck(count($quote['eligible']) === 2 && $quote['excluded'] === 1, 'eligible and excluded counts');
quoteCheck($quote['eligible'][0]['id'] === 2 && $quote['eligible'][0]['estimated_saving'] === 97500,
    'highest immediate estimated saving wins');
$equalA = $fixed;
$equalB = $fixed;
$equalA['id'] = 20;
$equalA['ends_at'] = '2026-11-12';
$equalB['id'] = 10;
$equalB['ends_at'] = '2026-11-11';
$quote = $calculator->compare([$equalA, $equalB], 650000, 0, 'ONLINE');
quoteCheck($quote['eligible'][0]['id'] === 10, 'earlier expiration tie-break');

echo "PASS: amount validation, PERCENTAGE, FIXED_AMOUNT, FREE_SHIPPING, min order, caps, stacking prevention, sorting.\n";

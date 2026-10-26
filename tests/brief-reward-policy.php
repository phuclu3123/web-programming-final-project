<?php

declare(strict_types=1);

/** Ensure demo seed remains aligned with CouponHub brief.md, sections 5/Level. */
$sql = file_get_contents(__DIR__ . '/../database/demo.sql');
if (!is_string($sql)) throw new RuntimeException('Cannot read demo seed');

$levels = [
    1 => ['Newbie', 0],
    2 => ['Deal Hunter', 100],
    3 => ['Deal Expert', 500],
    4 => ['Coupon Master', 1500],
];
foreach ($levels as $id => [$name, $min]) {
    $pattern = '/\(' . $id . ',\s*\x27' . preg_quote($name, '/') . '\x27,\s*' . $min . '\s*\)/';
    if (preg_match($pattern, $sql) !== 1) {
        throw new RuntimeException("Level {$id} ({$name} / {$min}) differs from brief.md");
    }
}

$rules = [
    'COUPON_APPROVED' => [5, 1],
    'WORKING_10' => [10, 10],
    'FAVORITES_100' => [20, 100],
];
foreach ($rules as $code => [$points, $threshold]) {
    $pattern = '/\(\x27' . $code . '\x27\s*,\s*\x27[^\x27]*\x27\s*,\s*' . $points . '\s*,\s*' . $threshold . '\s*,\s*1\s*\)/u';
    if (preg_match($pattern, $sql) !== 1) {
        throw new RuntimeException("Point rule {$code} differs from brief.md");
    }
}
// Seeded ledger must already reflect corrected policy, not the old +10 approval.
if (preg_match('/\x27COUPON_APPROVED\x27,\s*10,/', $sql) === 1) {
    throw new RuntimeException('Demo ledger retains old +10 approval amount');
}

echo "PASS: demo seed matches brief.md levels and point rules (no database).\n";

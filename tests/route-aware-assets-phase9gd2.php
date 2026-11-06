<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/core/PageAssetPolicy.php';

use App\Core\PageAssetPolicy as Assets;

$expect = [
    ['home', 'discovery.js', true],
    ['home', 'hero-carousel.js', true],
    ['home', 'campaign-clock.js', true],
    ['coupons/index', 'editorial.css', true],
    ['coupons/index', 'discovery.css', true],
    ['coupons/index', 'category-picker.js', true],
    ['coupons/index', 'discovery.js', false],
    ['coupons/index', 'editorial.js', false],
    ['coupons/index', 'campaign-clock.js', false],
    ['categories/index', 'editorial.js', true],
    ['categories/index', 'category-picker.js', true],
    ['categories/index', 'discovery.js', false],
    ['info/page', 'editorial.css', false],
    ['info/page', 'category-navigation.css', false],
    ['shop', 'editorial.css', false],
    ['admin', 'category-picker.js', false],
    ['unmapped/future', 'editorial.css', true],
    ['unmapped/future', 'category-picker.js', true],
    ['coupons/index', 'release.css', true],
];

foreach ($expect as [$view, $asset, $want]) {
    if (Assets::enabled($view, $asset) !== $want) {
        fwrite(STDERR, "FAIL: {$view} / {$asset}\n");
        exit(1);
    }
}

echo 'PASS: ' . count($expect) . " route-aware asset policy contracts.\n";

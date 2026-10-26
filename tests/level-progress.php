<?php

declare(strict_types=1);

require __DIR__ . '/../app/services/LevelProgressService.php';

use App\Services\LevelProgressService;

$cases = [
    'Newbie starts at zero' => [0, 0, 100, 0],
    'Newbie halfway' => [50, 0, 100, 50],
    'Deal Hunter starts at 100' => [100, 100, 500, 0],
    'Deal Hunter 120 points = 5 percent' => [120, 100, 500, 5],
    'Deal Hunter halfway' => [300, 100, 500, 50],
    'Deal Expert starts at 500' => [500, 500, 1500, 0],
    'Deal Expert at 1000' => [1000, 500, 1500, 50],
    'Coupon Master has no next level' => [1500, 1500, null, 100],
    'Over-threshold cannot exceed 100' => [9999, 100, 500, 100],
    'Negative earned points clamp to zero' => [-5, 0, 100, 0],
    'Invalid thresholds do not divide by zero' => [120, 100, 100, 100],
];

foreach ($cases as $name => [$earned, $current, $next, $expected]) {
    $actual = LevelProgressService::percentage($earned, $current, $next);
    if ($actual !== $expected) {
        throw new RuntimeException("FAIL: {$name} ({$actual}, expected {$expected})");
    }
}
echo 'PASS: level progress between DB thresholds (' . count($cases) . " cases, no database).\n";

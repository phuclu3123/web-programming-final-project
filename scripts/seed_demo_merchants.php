<?php

declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

$merchants = [
    ['Shopee', 'shopee'],
    ['Lazada', 'lazada'],
    ['Highlands Coffee', 'highlands-coffee'],
    ['KFC', 'kfc'],
    ['Phúc Long', 'phuc-long'],
    ['CGV', 'cgv'],
];

$db = Database::connection();
$stmt = $db->prepare(
    "INSERT INTO merchants(name, slug, status) " .
        "SELECT ?, ?, 'UNCLAIMED' WHERE NOT EXISTS (SELECT 1 FROM merchants WHERE slug = ?)"
);
$count = 0;
foreach ($merchants as [$name, $slug]) {
    $stmt->execute([$name, $slug, $slug]);
    $count += $stmt->rowCount();
}

echo "Seeded {$count} merchant(s). Existing slugs were skipped." . PHP_EOL;

<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) return;
    $parts = explode('\\', substr($class, strlen($prefix)));
    for ($i = 0; $i < count($parts) - 1; $i++) $parts[$i] = strtolower($parts[$i]);
    $file = $root . '/app/' . implode('/', $parts) . '.php';
    if (is_file($file)) require_once $file;
});
require_once $root . '/app/helpers.php';

App::init([
    'app' => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
]);

$merchants = [
    ['Shopee', 'shopee'],
    ['Lazada', 'lazada'],
    ['Highlands Coffee', 'highlands-coffee'],
    ['KFC', 'kfc'],
    ['Phúc Long', 'phuc-long'],
    ['CGV', 'cgv'],
];

$pdo = Database::connection();
$stmt = $pdo->prepare(
    "INSERT INTO merchants(name, slug, status) " .
        "SELECT ?, ?, 'UNCLAIMED' WHERE NOT EXISTS (SELECT 1 FROM merchants WHERE slug = ?)"
);
$count = 0;
foreach ($merchants as [$name, $slug]) {
    $stmt->execute([$name, $slug, $slug]);
    $count += $stmt->rowCount();
}

echo "Seeded {$count} merchant(s). Existing slugs were skipped." . PHP_EOL;

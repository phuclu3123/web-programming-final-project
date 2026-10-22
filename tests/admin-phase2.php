<?php

declare(strict_types=1);

// Structural regression test only. DB transactions must still be tested on MySQL/XAMPP.
require __DIR__ . '/../app/bootstrap.php';

function adminCheck(bool $condition, string $description): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $description);
}

$router = new App\Core\Router();
require __DIR__ . '/../config/routes.php';
$routes = [];
foreach ($router->routes() as $route) {
    $routes[$route['method'] . ' ' . $route['path']] = $route['handler'];
}
$expected = [
    'POST /admin/categories/{id}/edit', 'POST /admin/categories/{id}/delete',
    'GET /admin/coupons/{id}/edit', 'POST /admin/coupons/{id}/edit',
    'POST /admin/coupons/{id}/expire', 'POST /admin/coupons/{id}/delete',
];
foreach ($expected as $route) {
    adminCheck(isset($routes[$route]), 'registered route ' . $route);
    [$class, $method] = $routes[$route];
    adminCheck(class_exists($class) && method_exists($class, $method), 'handler ' . $route);
}
foreach (['expire', 'delete'] as $action) {
    adminCheck(!isset($routes['GET /admin/coupons/{id}/' . $action]), 'destructive coupon action is POST-only');
}
foreach (['edit', 'delete'] as $action) {
    adminCheck(!isset($routes['GET /admin/categories/{id}/' . $action]), 'destructive category action is POST-only');
}
$root = dirname(__DIR__);
foreach (['app/views/admin/coupons/index.php', 'app/views/admin/coupons/edit.php', 'app/views/admin/categories/index.php'] as $name) {
    $html = file_get_contents($root . '/' . $name);
    adminCheck($html !== false && str_contains($html, 'Session::csrfToken()'), $name . ' emits CSRF tokens');
    adminCheck($html !== false && !str_contains($html, 'data-toggle='), $name . ' uses Bootstrap 5 markup');
}
$schema = file_get_contents($root . '/database/schema.sql');
adminCheck($schema !== false && str_contains($schema, "'EXPIRED'") && str_contains($schema, "'ARCHIVED'"), 'existing DB schema supports status changes');
adminCheck(is_file($root . '/app/repositories/AdminCouponRepository.php'), 'AdminCouponRepository exists');
adminCheck(is_file($root . '/app/services/AdminCouponService.php'), 'AdminCouponService exists');
echo "PASS: admin coupon/category route & form contracts (no database).\n";

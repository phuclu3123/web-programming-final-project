<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once dirname(__DIR__) . '/app/bootstrap.php';
$router = new App\Core\Router();
require dirname(__DIR__) . '/config/routes.php';
foreach ($router->routes() as $route) {
    [$class, $action] = $route['handler'];
    if (!class_exists($class) || !method_exists($class, $action) || !is_subclass_of($class, App\Core\Controller::class)) {
        throw new RuntimeException('Invalid route ' . $route['path']);
    }
}
echo 'PASS: ' . count($router->routes()) . " route handlers and common abstract controller.\n";

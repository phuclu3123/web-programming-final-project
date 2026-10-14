<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Request;
use App\Core\Router;

$root = dirname(__DIR__);

spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $parts = explode('\\', $relative);
    for ($i = 0; $i < count($parts) - 1; $i++) {
        $parts[$i] = strtolower($parts[$i]);
    }
    $file = $root . '/app/' . implode('/', $parts) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

require_once $root . '/app/helpers.php';

$config = [
    'app' => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
    'mail' => require $root . '/config/mail.php',
];

App::init($config);

$router = new Router();
require $root . '/config/routes.php';
$router->dispatch(new Request());

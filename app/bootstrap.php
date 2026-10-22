<?php
declare(strict_types=1);

// ---- KHỞI ĐỘNG CHUNG: autoload App, helpers và cấu hình ----
ini_set('display_errors', '0');
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $parts = explode('\\', substr($class, 4));
    for ($i = 0; $i < count($parts) - 1; ++$i) $parts[$i] = strtolower($parts[$i]);
    $file = __DIR__ . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) require_once $file;
});

App\Core\Env::load(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/helpers.php';
App\Core\App::init([
    'app' => require __DIR__ . '/../config/app.php',
    'database' => require __DIR__ . '/../config/database.php',
    'mail' => require __DIR__ . '/../config/mail.php',
    'oauth' => require __DIR__ . '/../config/oauth.php',
    'auth' => require __DIR__ . '/../config/auth.php',
]);

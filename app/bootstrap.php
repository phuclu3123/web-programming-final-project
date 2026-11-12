<?php
declare(strict_types=1);

// KHỞI ĐỘNG CHUNG: autoload App, helpers và cấu hình
ini_set('display_errors', '0');
spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\\')) return;
    $parts = explode('\\', substr($class, 4));
    for ($i = 0; $i < count($parts) - 1; ++$i) $parts[$i] = strtolower($parts[$i]);
    $file = __DIR__ . '/' . implode('/', $parts) . '.php';
    if (is_file($file)) require_once $file;
});

App\Core\Env::load(dirname(__DIR__) . '/.env');
// CLI migrations remain available while HTTP writers are paused for a DB upgrade.
$maintenanceFile = getenv('COUPONHUB_MAINTENANCE_FILE') ?: dirname(__DIR__) . '/.runtime/maintenance.lock';
if (PHP_SAPI !== 'cli' && is_file($maintenanceFile)) {
    http_response_code(503);
    header('Retry-After: 60');
    header('Cache-Control: no-store');
    $message = 'Hệ thống đang bảo trì. Vui lòng thử lại sau ít phút.';
    $json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || preg_match('~(?:^|/)api(?:/|$)~', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
    header('Content-Type: ' . ($json ? 'application/json' : 'text/plain') . '; charset=utf-8');
    echo $json ? json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE) : $message;
    exit;
}
require_once __DIR__ . '/helpers.php';
App\Core\App::init([
    'app' => require __DIR__ . '/../config/app.php',
    'database' => require __DIR__ . '/../config/database.php',
    'mail' => require __DIR__ . '/../config/mail.php',
    'oauth' => require __DIR__ . '/../config/oauth.php',
    'auth' => require __DIR__ . '/../config/auth.php',
    'integrations' => require __DIR__ . '/../config/integrations.php',
]);

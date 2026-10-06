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

App::init([
    'app' => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
]);

$email = trim($argv[1] ?? '');
$password = (string) ($argv[2] ?? '');
$fullName = trim($argv[3] ?? 'CouponHub Admin');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php admin@example.com StrongPassword123 \"Admin Name\"\n");
    exit(1);
}

$pdo = Database::connection();
$check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetchColumn()) {
    fwrite(STDERR, "Email already exists.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users(email,password_hash,full_name,role,status) VALUES(?,?,?,'ADMIN','ACTIVE')");
$stmt->execute([$email, $hash, $fullName]);

echo 'Created ADMIN user #' . $pdo->lastInsertId() . PHP_EOL;

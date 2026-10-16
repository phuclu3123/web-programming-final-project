<?php

declare(strict_types=1);

use App\Core\Database;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

$email = trim($argv[1] ?? '');
$password = (string) ($argv[2] ?? '');
$fullName = trim($argv[3] ?? 'CouponHub Admin');

if ($email === '' || strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || preg_match('//u', $password) !== 1 || preg_match_all('/./us', $password) < 8 || strlen($password) > 72
    || str_contains($password, "\0") || !preg_match('/\p{Lu}/u', $password) || !preg_match('/\p{Ll}/u', $password)
    || !preg_match('/[0-9]/', $password) || !preg_match('/[^\p{L}\p{N}\s]/u', $password)
    || $fullName === '' || textLength($fullName) > 120) {
    fwrite(STDERR, "Usage: php scripts/create_admin.php admin@example.com StrongPassword123! \"Admin Name\"\n");
    exit(1);
}

$db = Database::connection();
$check = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetchColumn()) {
    fwrite(STDERR, "Email already exists.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $db->prepare("INSERT INTO users(email,password_hash,full_name,role,status) VALUES(?,?,?,'ADMIN','ACTIVE')");
$stmt->execute([$email, $hash, $fullName]);

echo 'Created ADMIN user #' . $db->lastInsertId() . PHP_EOL;

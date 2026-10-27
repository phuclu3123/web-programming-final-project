<?php

declare(strict_types=1);

// ---- PHP BUILT-IN SERVER: php -S localhost:8000 router.php (tại CouponHub_UI) ----
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
if (
    preg_match('~(?:^|/)(?:\.|app|config|database|docs|tests|scripts|storage)(?:/|$)|(?:^|/)\.[^/]*~i', $path)
    || str_contains($path, '\\') || str_contains($path, "\0")
) {
    http_response_code(403);
    exit('Forbidden');
}
$file = realpath(__DIR__ . $path);
$inside = $file !== false && str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', __DIR__) . '/');
if ($inside && is_file($file) && preg_match('~\A/(?:assets|public)/(?!.*\.php(?:/|$)).+\.(?:css|js|svg|png|jpe?g|gif|ico|webp|woff2?|ttf)\z~i', $path)) return false;
if ($inside && is_file($file) && (in_array($path, ['/login.php', '/logout.php', '/oauth-setup.php'], true)
    || preg_match('~\A/api/(?:login|register|oauth/(?:prepare|google-callback|github-callback))\.php\z~', $path))) {
    $_SERVER['SCRIPT_NAME'] = $path;
    require $file;
    return true;
}
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';

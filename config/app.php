<?php
declare(strict_types=1);

// ---- URL CHUNG: không hardcode đường dẫn máy Quang; có thể ghi đè bằng env ----
$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
$basePath = preg_replace('~/(?:api/.*|public/index\.php|(?:index|admin|shop|login|logout|oauth-setup)\.php)$~', '', $script);
if (PHP_SAPI === 'cli') $basePath = '';
return [
    'name' => 'CouponHub',
    'base_url' => rtrim(getenv('COUPONHUB_APP_URL') ?: $basePath, '/'),
    'timezone' => 'Asia/Ho_Chi_Minh',
    'debug' => getenv('COUPONHUB_DEBUG') === '1',
    'session_name' => 'COUPONHUBSESSID',
];

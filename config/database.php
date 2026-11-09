<?php
// Env xác định database từng môi trường; local.php nếu có sẽ ghi đè cấu hình bên dưới.

declare(strict_types=1);

// Giá trị mặc định phục vụ local; môi trường thật phải khai báo tên DB và tài khoản riêng.
$config = [
    'host' => getenv('COUPONHUB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('COUPONHUB_PORT') ?: 3306),
    'database' => getenv('COUPONHUB_DB') ?: 'dbcouponshubweb_v2',
    'username' => getenv('COUPONHUB_USER') ?: 'root',
    'password' => getenv('COUPONHUB_PASSWORD') ?: '',
];
// Charset utf8mb4, collation utf8mb4_vietnamese_ci và UTC đặt trong app/core/Database.php.
$local = __DIR__ . '/local.php';
return is_file($local) ? array_replace($config, require $local) : $config;

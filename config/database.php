<?php

declare(strict_types=1);

// ---- KẾT NỐI MYSQL LOCAL (XAMPP) ----
// Database mới đã cài bộ SQL chuẩn: dbcouponshubweb_v2.
// Database cũ dbcouponshubweb đã xóa theo yêu cầu; dùng duy nhất bản _v2 này.
// Có thể ghi đè bằng biến môi trường COUPONHUB_* hoặc config/local.php.
$config = [
    'host' => getenv('COUPONHUB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('COUPONHUB_PORT') ?: 3306),
    'database' => getenv('COUPONHUB_DB') ?: 'dbcouponshubweb_v2',
    'username' => getenv('COUPONHUB_USER') ?: 'root',
    // MySQL trên máy hiện tại dùng root, không đặt mật khẩu.
    'password' => getenv('COUPONHUB_PASSWORD') ?: '',
];
// Charset utf8mb4, collation utf8mb4_vietnamese_ci và UTC đặt trong app/core/Database.php.
$local = __DIR__ . '/local.php';
return is_file($local) ? array_replace($config, require $local) : $config;

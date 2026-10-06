<?php
declare(strict_types=1);

// XAMPP local defaults. Override with environment variables or config/local.php.
$config = [
    'host' => getenv('COUPONHUB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('COUPONHUB_PORT') ?: 3306),
    'database' => getenv('COUPONHUB_DB') ?: 'dbcouponshubweb',
    'username' => getenv('COUPONHUB_USER') ?: 'root',
    'password' => getenv('COUPONHUB_PASSWORD') ?: '',
    // Only honored for a direct localhost request. Full login is a later task.
    'local_demo' => true,
];
$local = __DIR__ . '/local.php';
return is_file($local) ? array_replace($config, require $local) : $config;

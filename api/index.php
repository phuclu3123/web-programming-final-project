<?php
declare(strict_types=1);
// PHP built-in server không có router tìm index.php trong thư mục api.
// Giữ nguyên URI/method để đi qua đúng Router, quyền và CSRF của ứng dụng.
require dirname(__DIR__) . '/index.php';

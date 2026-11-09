<?php
// Thư mục public chứa asset; điểm vào ứng dụng nằm ở index.php tại gốc dự án.

declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
App\Core\Response::redirect(url('/'), 303);

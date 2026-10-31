<?php
$errorTitle=match($status) { 404=>'Không tìm thấy trang.', 403=>'Bạn chưa có quyền truy cập.', 401=>'Đăng nhập để tiếp tục.', 422=>'Kiểm tra lại thông tin.', 405=>'Thao tác chưa được hỗ trợ.', default=>'Trang đang tạm gián đoạn.' };
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= (int)$status ?> · CouponHub</title>
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/release.css')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/css/release.css') ?>">
</head>

<body class="error-page">
    <main class="error-scene" id="main"><a class="error-brand" href="<?= e(url('/')) ?>"><span>C</span>CouponHub.</a>
        <div class="error-number" aria-hidden="true"><?= (int)$status ?></div>
        <h1><?= e($errorTitle) ?></h1>
        <p><?= e($status===503 ? 'Vui lòng thử lại sau ít phút.' : $message) ?></p>
        <div class="error-actions"><a href="<?= e(url('/')) ?>">Về trang Khám phá →</a><a
                href="<?= e(url('/coupons')) ?>">Tìm coupon</a></div>
    </main>
</body>

</html>
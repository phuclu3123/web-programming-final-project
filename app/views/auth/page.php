<?php
use App\Core\Session;
$authInitialView = ($authInitialView ?? '') === 'register' ? 'register' : 'login';
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <base href="<?= e(rtrim((string) config('app.base_url', ''), '/') . '/') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $authInitialView === 'register' ? 'Đăng ký' : 'Đăng nhập' ?> · CouponHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= e(url('/assets/css/design-system.css')) ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/member.css')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/css/member.css') ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/auth/auth.css')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/css/auth/auth.css') ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/release.css')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/css/release.css') ?>">
    <script src="<?= e(url('/assets/js/member.js')) ?>" defer></script>
    <script
        src="<?= e(url('/assets/js/auth/auth.js')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/js/auth/auth.js') ?>"
        defer></script>
</head>

<body class="auth-page">
    <a class="skip-link" href="<?= e(url((new \App\Core\Request())->path())) ?>#main">Đến nội dung chính</a>
    <?php require __DIR__.'/../partials/public-header.php'; ?>
    <main id="main" class="auth-stage container-wide">
        <aside class="auth-story" aria-label="Tài khoản CouponHub">
            <img class="auth-story-photo" src="<?= e(url('/assets/images/hero/category-fashion.webp')) ?>" alt=""
                width="2560" height="1707" decoding="async">
            <span class="auth-story-kicker"><?= icon('spark') ?> THÊM GIÁ TRỊ MỖI NGÀY</span>
            <h2>Mỗi mã hay.<br><span>Một khoản tiết kiệm.</span></h2>
            <p>Lưu những ưu đãi bạn thích, chia sẻ mã hữu ích và cùng cộng đồng kiểm chứng.</p>
            <div class="auth-story-foot"><?= icon('users') ?><span>Chia sẻ có trách nhiệm.<br>Tiết kiệm cùng cộng
                    đồng.</span></div>
        </aside>
        <section id="loginModal" class="auth-inline" data-auth-page="<?= e($authInitialView) ?>"
            aria-labelledby="<?= $authInitialView === 'register' ? 'registerTitle' : 'loginTitle' ?>">
            <?php foreach (['success','error'] as $authFlash): if ($authMessage = Session::flash($authFlash)): ?>
            <p class="auth-message" data-state="<?= $authFlash === 'error' ? 'error' : 'success' ?>" role="status">
                <?= e($authMessage) ?></p>
            <?php endif; endforeach; ?>
            <div data-auth-view="login" <?= $authInitialView !== 'login' ? 'hidden' : '' ?>>
                <?php require __DIR__.'/login-form.php'; ?></div>
            <div data-auth-view="register" <?= $authInitialView !== 'register' ? 'hidden' : '' ?>>
                <?php require __DIR__.'/register-form.php'; ?></div>
            <p class="auth-legal">Tiếp tục khám phá với <a href="<?= e(url('/coupons')) ?>">tư cách khách
                    <?= icon('arrow') ?></a></p>
        </section>
    </main>
</body>

</html>
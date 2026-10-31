<?php

use App\Core\Auth;
use App\Core\Session;

$success = Session::flash('success');
$error = Session::flash('error');
$warning = Session::flash('warning');
$info = Session::flash('info');
$currentUser = Auth::user();
$currentPath = (new \App\Core\Request())->path();
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="couponhub-base-url" content="<?= e(rtrim((string) config('app.base_url', ''), '/')) ?>">
    <meta name="csrf-token" content="<?= e(Session::csrfToken()) ?>">
    <title><?= e($pageTitle ?? 'CouponHub') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset('/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/design-system.css')) ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/discovery.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/discovery.css') ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/member.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/member.css') ?>">
    <script src="<?= e(url('/assets/js/member.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/member.js') ?>"
        defer></script>
    <script
        src="<?= e(url('/assets/js/discovery.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/discovery.js') ?>"
        defer></script>
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/editorial.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/editorial.css') ?>">
    <script
        src="<?= e(url('/assets/js/editorial.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/editorial.js') ?>"
        defer></script>
    <script
        src="<?= e(url('/assets/js/campaign-clock.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/campaign-clock.js') ?>"
        defer></script>
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/product.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/product.css') ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/release.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/release.css') ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/integration.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/integration.css') ?>">
</head>

<body class="unified-page">
    <a class="skip-link" href="#main">Đến nội dung chính</a>
    <?php require __DIR__ . '/../partials/public-header.php'; ?>
    <main id="main" class="member-main py-4">
        <div class="container-wide">
            <?php if (
                $currentUser && in_array($currentUser['role'], ['USER', 'MERCHANT'], true)
                && in_array($currentPath, ['/account', '/saved-coupons', '/my-coupons', '/coupons/create', '/merchant/apply'], true)
            ) {
                require __DIR__ . '/../partials/personal-nav.php';
            } ?>
            <?php foreach ([['success', $success, 12000], ['info', $info, 12000], ['warning', $warning, 15000], ['danger', $error, 20000]] as [$type, $message, $timeout]): if ($message): ?>
                    <div class="alert alert-<?= e($type) ?> alert-dismissible fade show auto-dismiss-alert"
                        data-timeout="<?= (int)$timeout ?>" role="alert"><?= e($message) ?><button type="button"
                            class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div><?php endif;
                                                                                                endforeach; ?><?= $content ?>
        </div>
    </main>
    <?php require __DIR__ . '/../partials/public-footer.php'; ?>
    <button id="back-to-top" class="btn btn-primary back-to-top" type="button" aria-label="Về đầu trang"
        title="Về đầu trang"><i class="fa-solid fa-arrow-up"></i></button>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(url('/assets/js/coupon-events.js')) ?>"></script>
    <script src="<?= e(asset('/js/app.js')) ?>"></script><?php foreach (($pageScripts ?? []) as $pageScript): ?><script
            src="<?= e(asset((string)$pageScript)) ?>"></script><?php endforeach; ?>
    <?php if (!$currentUser) require __DIR__ . '/../auth/auth-modal.php'; ?>
</body>

</html>
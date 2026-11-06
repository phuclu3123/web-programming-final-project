<?php

use App\Core\Auth;
use App\Core\Session;

$success = Session::flash('success');
$error = Session::flash('error');
$warning = Session::flash('warning');
$info = Session::flash('info');
$currentUser = $currentUser ?? Auth::user();
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
    <link rel="stylesheet" href="<?= e(url('/assets/vendor/fontawesome/css/all.min.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset('/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('/assets/css/design-system.css')) ?>">
    <?php if (\App\Core\PageAssetPolicy::enabled((string)($view ?? ''), 'discovery.css')): ?>
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/discovery.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/discovery.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/member.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/member.css') ?>">
    <script src="<?= e(url('/assets/js/member.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/member.js') ?>"
        defer></script>
    <?php if (\App\Core\PageAssetPolicy::enabled((string)($view ?? ''), 'discovery.js')): ?>
    <script
        src="<?= e(url('/assets/js/discovery.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/discovery.js') ?>"
        defer></script>
    <?php endif; ?>
    <?php if (\App\Core\PageAssetPolicy::enabled((string)($view ?? ''), 'editorial.css')): ?>
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/editorial.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/editorial.css') ?>">
    <?php endif; ?>
    <?php if (\App\Core\PageAssetPolicy::enabled((string)($view ?? ''), 'editorial.js')): ?>
    <script
        src="<?= e(url('/assets/js/editorial.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/editorial.js') ?>"
        defer></script>
    <?php endif; ?>
    <?php if (\App\Core\PageAssetPolicy::enabled((string)($view ?? ''), 'campaign-clock.js')): ?>
    <script
        src="<?= e(url('/assets/js/campaign-clock.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/campaign-clock.js') ?>"
        defer></script>
    <?php endif; ?>
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/product.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/product.css') ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/release.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/release.css') ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/integration.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/integration.css') ?>">
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/floating-actions.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/floating-actions.css') ?>">
    <?php if (\App\Core\PageAssetPolicy::enabled((string)($view ?? ''), 'category-navigation.css')): ?>
    <link rel="stylesheet"
        href="<?= e(url('/assets/css/category-navigation.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/category-navigation.css') ?>">
    <?php endif; ?>
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
                    class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div>
            <?php endif;
            endforeach; ?><?= $content ?>
        </div>
    </main>
    <?php require __DIR__ . '/../partials/public-footer.php'; ?>
    <?php require __DIR__ . '/../partials/floating-support.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(url('/assets/js/coupon-events.js')) ?>"></script>
    <script src="<?= e(asset('/js/app.js')) ?>"></script>
    <script
        src="<?= e(url('/assets/js/floating-support.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/floating-support.js') ?>">
    </script>
    <?php if (\App\Core\PageAssetPolicy::enabled((string)($view ?? ''), 'category-picker.js')): ?>
    <script
        src="<?= e(url('/assets/js/category-picker.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/category-picker.js') ?>">
    </script>
    <?php endif; ?>
    <?php foreach (($pageScripts ?? []) as $pageScript): ?>
    <script src="<?= e(asset((string)$pageScript)) ?>"></script>
    <?php endforeach; ?>
    <?php if (!$currentUser) require __DIR__ . '/../auth/auth-modal.php'; ?>
</body>

</html>
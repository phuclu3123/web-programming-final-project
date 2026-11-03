<?php $role = $role ?? 'user';
$title = $title ?? 'CouponHub';
$demo = $demo ?? false; ?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="utf-8">
  <base href="<?= e(rtrim((string) config('app.base_url', ''), '/') . '/') ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="couponhub-base-url" content="<?= e(rtrim((string) config('app.base_url', ''), '/')) ?>">
  <meta name="csrf-token" content="<?= e(\App\Core\Session::token()) ?>">
  <title><?= e($title) ?> · CouponHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= e(url('/assets/vendor/fontawesome/css/all.min.css')) ?>">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/design-system.css">
  <link rel="stylesheet"
    href="<?= e(url('/assets/css/discovery.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/discovery.css') ?>">
  <script
    src="<?= e(url('/assets/js/discovery.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/discovery.js') ?>"
    defer></script>
  <link rel="stylesheet"
    href="<?= e(url('/assets/css/member.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/member.css') ?>">
  <script src="<?= e(url('/assets/js/member.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/member.js') ?>"
    defer></script>
  <link rel="stylesheet"
    href="<?= e(url('/assets/css/editorial.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/editorial.css') ?>">
  <script
    src="<?= e(url('/assets/js/editorial.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/editorial.js') ?>"
    defer></script>
  <script
    src="<?= e(url('/assets/js/hero-carousel.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/hero-carousel.js') ?>"
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
  <link rel="stylesheet" href="<?= e(url('/assets/css/floating-actions.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/floating-actions.css') ?>">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" defer></script>
  <script src="<?= e(url('/assets/js/coupon-events.js')) ?>" defer></script>
  <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/../../../assets/js/app.js') ?>" defer></script>
  <script src="<?= e(url('/assets/js/floating-support.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/floating-support.js') ?>" defer></script>
</head>

<body data-page="<?= e($role) ?>" data-demo="<?= $demo ? 'true' : 'false' ?>">
  <a href="#main" class="skip-link">Đến nội dung chính</a>
  <?php if ($demo): ?>
    <div class="demo-switcher">
      <span class="demo-switcher-label">XEM NHANH</span>
      <nav class="demo-switcher-links" aria-label="Chuyển khu vực xem trước">
        <?php foreach (['user' => ['index.php', 'Khám phá'], 'shop' => ['shop.php', 'Đối tác'], 'admin' => ['admin.php', 'Quản trị']] as $key => [$url, $label]): ?>
          <a href="<?= e($url) ?>" <?= $key === $role ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  <?php endif; ?>
  <?php require __DIR__ . '/../partials/floating-support.php'; ?>
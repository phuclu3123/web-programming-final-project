<?php $role = $role ?? 'user'; $title = $title ?? 'CouponHub'; $demo = $demo ?? false; ?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?> · CouponHub</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/styles.css">
  <link rel="stylesheet" href="assets/css/design-system.css">
  <script src="assets/js/app.js" defer></script>
</head>
<body data-page="<?= e($role) ?>" data-demo="<?= $demo ? 'true' : 'false' ?>">
<a href="#main" class="skip-link">Đến nội dung chính</a>
<?php if ($demo): ?>
<div class="demo-switcher">
    <span class="demo-switcher-label">XEM NHANH</span>
    <nav class="demo-switcher-links" aria-label="Chuyển khu vực xem trước">
      <?php foreach (['user' => ['index.php','Khám phá'], 'shop' => ['shop.php','Đối tác'], 'admin' => ['admin.php','Quản trị']] as $key => [$url, $label]): ?>
      <a href="<?= e($url) ?>" <?= $key === $role ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
</div>
<?php endif; ?>
<aside class="floating-support" data-floating-support aria-label="Liên hệ và điều hướng nhanh">
  <section class="contact-popover" id="contact-popover" role="dialog" aria-modal="false" aria-labelledby="contact-title" aria-hidden="true">
    <div class="contact-popover-head">
      <div>
        <span class="contact-availability"><i aria-hidden="true"></i> Hỗ trợ trực tuyến</span>
        <strong id="contact-title">Bạn cần hỗ trợ?</strong>
      </div>
      <button class="contact-close" type="button" data-contact-close aria-label="Đóng bảng liên hệ">×</button>
    </div>
    <div class="contact-options">
      <a class="contact-option" href="https://zalo.me/" target="_blank" rel="noopener noreferrer">
        <span class="contact-channel-icon contact-channel-zalo" aria-hidden="true">Z</span>
        <span><strong>Zalo</strong><small>Trò chuyện nhanh</small></span>
        <?= icon('arrow') ?>
      </a>
      <a class="contact-option" href="https://www.messenger.com/" target="_blank" rel="noopener noreferrer">
        <span class="contact-channel-icon contact-channel-messenger" aria-hidden="true">M</span>
        <span><strong>Messenger</strong><small>Nhắn tin qua Facebook</small></span>
        <?= icon('arrow') ?>
      </a>
      <a class="contact-option" href="mailto:hello@couponhub.vn?subject=Hỗ trợ từ CouponHub">
        <span class="contact-channel-icon contact-channel-mail" aria-hidden="true"><?= icon('mail') ?></span>
        <span><strong>Email</strong><small>hello@couponhub.vn</small></span>
        <?= icon('arrow') ?>
      </a>
    </div>
    <p class="contact-note">Phản hồi trong giờ làm việc · 08:00–18:00</p>
  </section>
  <div class="floating-support-actions">
    <button class="glass-action contact-trigger" type="button" data-contact-trigger aria-expanded="false" aria-controls="contact-popover">
      <?= icon('message') ?><span>Liên hệ</span>
    </button>
    <button class="glass-action back-to-top" type="button" data-back-to-top aria-label="Trở về đầu trang" title="Trở về đầu trang">
      <?= icon('arrow-up') ?>
    </button>
  </div>
</aside>

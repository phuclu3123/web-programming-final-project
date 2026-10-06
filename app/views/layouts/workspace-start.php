<div class="app-layout">
  <aside class="sidebar">
    <div>
      <a class="brand" href="index.php"><span class="brand-mark"><span>C</span></span><span class="brand-word">Coupon<em>Hub</em><span class="brand-period">.</span></span></a>
      <div class="workspace-name"><?= $role === 'shop' ? 'Không gian đối tác' : 'Trung tâm quản trị' ?></div>
    </div>
    <div>
      <div class="side-label"><?= $role === 'shop' ? 'Cửa hàng của bạn' : 'Quản lý nền tảng' ?></div>
      <nav class="side-nav" aria-label="Điều hướng quản lý">
        <a class="side-link active" href="#overview" aria-current="location"><?= icon('grid') ?>Tổng quan</a>
        <?php if ($role === 'shop'): ?>
        <a class="side-link" href="#create-coupon"><?= icon('ticket') ?>Tạo mã mới</a>
        <a class="side-link" href="#my-coupons"><?= icon('flag') ?>Mã đã đăng<span class="nav-count"><?= (int) $stats['total'] ?></span></a>
        <?php else: ?>
        <a class="side-link" href="#queue"><?= icon('ticket') ?>Hàng chờ<span class="nav-count"><?= (int) $stats['pending'] ?></span></a>
        <a class="side-link" href="#detail"><?= icon('check') ?>Chi tiết duyệt</a>
        <?php endif; ?>
      </nav>
    </div>
    <div class="side-footer"><span class="avatar"><?= $role === 'shop' ? 'ĐT' : 'QT' ?></span><div><strong><?= $role === 'shop' ? 'Không gian đối tác' : 'Trung tâm quản trị' ?></strong><small><?= $role === 'shop' ? 'Quản lý ưu đãi' : 'Kiểm duyệt hệ thống' ?></small></div></div>
  </aside>
  <div class="app-main">
    <header class="app-header">
      <div class="app-breadcrumb"><span><?= $role === 'shop' ? 'Không gian đối tác' : 'Quản trị' ?></span><span>/</span><span><?= e($title) ?></span></div>
      <span class="mobile-role">CouponHub / <?= $role === 'shop' ? 'Đối tác' : 'Quản trị' ?></span>
      <div class="app-account"><span class="badge-soft"><?= $role === 'shop' ? 'Đối tác' : 'Quản trị viên' ?></span><span><?= $role === 'shop' ? 'Không gian đối tác' : 'Trung tâm quản trị' ?></span></div>
    </header>
    <nav class="mobile-workspace-nav" aria-label="Điều hướng trang quản lý">
      <a href="#overview">Tổng quan</a>
      <?php if ($role === 'shop'): ?>
      <a href="#create-coupon">Tạo mã</a><a href="#my-coupons">Mã đã đăng</a>
      <?php else: ?>
      <a href="#queue">Hàng chờ</a><a href="#detail">Chi tiết</a>
      <?php endif; ?>
    </nav>
    <main id="main" class="app-content">

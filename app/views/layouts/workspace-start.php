<div class="app-layout">
    <aside class="sidebar">
        <div>
            <a class="brand" href="index.php"><span class="brand-mark"><span>C</span></span><span
                    class="brand-word">Coupon<em>Hub</em><span class="brand-period">.</span></span></a>
            <div class="workspace-name"><?= $role === 'shop' ? 'Không gian đối tác' : 'Trung tâm quản trị' ?></div>
        </div>
        <div>
            <div class="side-label"><?= $role === 'shop' ? 'Cửa hàng của bạn' : 'Quản lý nền tảng' ?></div>
            <nav class="side-nav" aria-label="Điều hướng quản lý">
                <?php if ($role === 'shop'): ?>
                <a class="side-link active" href="#overview" aria-current="location"><?= icon('grid') ?>Tổng quan</a>
                <a class="side-link" href="#create-coupon"><?= icon('ticket') ?>Tạo mã mới</a>
                <a class="side-link" href="#my-coupons"><?= icon('flag') ?>Mã đã đăng<span
                        class="nav-count"><?= (int) $stats['total'] ?></span></a>
                <a class="side-link" href="<?= e(url('/merchant')) ?>"><?= icon('grid') ?>Quản lý nhiều shop</a>
                <?php else: ?>
                <!-- ---- KHUNG ADMIN: đường dẫn thật cho từng mục, guard ở controller ---- -->
                <?php foreach (['dashboard' => 'Tổng quan', 'coupons' => 'Coupon', 'merchants' => 'Cửa hàng',
            'categories' => 'Danh mục', 'campaigns' => 'Chiến dịch', 'reports' => 'Báo cáo'] as $key => $label): ?>
                <a class="side-link <?= ($section ?? 'coupons') === $key ? 'active' : '' ?>"
                    href="admin.php?section=<?= e($key) ?>"
                    <?= ($section ?? 'coupons') === $key ? 'aria-current="page"' : '' ?>><?= icon($key === 'dashboard' ? 'grid' : 'ticket') ?><?= e($label) ?></a>
                <?php endforeach; ?>
                <?php endif; ?>
            </nav>
        </div>
        <div class="side-footer"><span class="avatar"><?= $role === 'shop' ? 'ĐT' : 'QT' ?></span>
            <div>
                <strong><?= $role === 'shop' ? 'Không gian đối tác' : 'Trung tâm quản trị' ?></strong><small><?= $role === 'shop' ? 'Quản lý ưu đãi' : 'Kiểm duyệt hệ thống' ?></small>
            </div>
        </div>
    </aside>
    <div class="app-main">
        <header class="app-header">
            <div class="app-breadcrumb">
                <span><?= $role === 'shop' ? 'Không gian đối tác' : 'Quản trị' ?></span><span>/</span><span><?= e($title) ?></span>
            </div>
            <span class="mobile-role">CouponHub / <?= $role === 'shop' ? 'Đối tác' : 'Quản trị' ?></span>
            <div class="app-account"><span
                    class="badge-soft"><?= $role === 'shop' ? 'Đối tác' : 'Quản trị viên' ?></span>
                <?php $currentUser = $actor; require __DIR__ . '/../partials/account-menu.php'; ?>
            </div>
        </header>
        <nav class="mobile-workspace-nav" aria-label="Điều hướng trang quản lý">
            <?php if ($role === 'shop'): ?>
            <a href="#overview">Tổng quan</a>
            <a href="#create-coupon">Tạo mã</a><a href="#my-coupons">Mã đã đăng</a>
            <?php else: ?>
            <?php foreach (['dashboard'=>'Tổng quan', 'coupons'=>'Coupon', 'merchants'=>'Cửa hàng', 'categories'=>'Danh mục', 'campaigns'=>'Chiến dịch', 'reports'=>'Báo cáo'] as $key=>$label): ?>
            <a href="admin.php?section=<?= e($key) ?>"
                <?= ($section ?? 'coupons') === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
            <?php endif; ?>
        </nav>
        <main id="main" class="app-content">
<nav class="personal-nav" aria-label="Không gian cá nhân">
    <?php foreach ([['/dashboard', 'Tổng quan'], ['/account', 'Hồ sơ'], ['/saved-coupons', 'Coupon đã lưu'], ['/my-coupons', 'Đóng góp của tôi']] as [$path, $label]): ?>
        <a href="<?= e(url($path)) ?>" <?= $currentPath === $path ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

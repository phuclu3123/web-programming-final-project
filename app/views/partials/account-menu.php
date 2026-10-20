<?php
use App\Core\Session;
$currentUser = $currentUser ?? null;
?>
<?php if ($currentUser): ?>
<details class="account-dropdown" data-account-dropdown>
    <summary class="account-trigger" aria-label="Menu tài khoản: <?= e($currentUser['full_name']) ?>">
        <span class="account-avatar" aria-hidden="true"><?= e(accountInitials($currentUser['full_name'])) ?></span>
        <span class="account-trigger-name" title="<?= e($currentUser['full_name']) ?>"><?= e($currentUser['full_name']) ?></span>
        <svg class="account-chevron" viewBox="0 0 20 20" aria-hidden="true"><path d="m5 7.5 5 5 5-5"/></svg>
    </summary>
    <div class="account-popover">
        <div class="account-popover-identity"><strong><?= e($currentUser['full_name']) ?></strong><small><?= e($currentUser['email'] ?? '') ?></small></div>
        <nav aria-label="Tài khoản cá nhân">
            <?php if (in_array($currentUser['role'], ['ADMIN', 'MERCHANT'], true)): ?>
            <a href="<?= e(url('/')) ?>"><?= icon('search') ?>Xem trang người dùng</a>
            <?php endif; ?>
            <a href="<?= e(url('/dashboard')) ?>"><?= icon('grid') ?>Tổng quan tài khoản</a>
            <a href="<?= e(url('/account')) ?>"><?= icon('users') ?>Chỉnh sửa hồ sơ</a>
            <?php if (in_array($currentUser['role'], ['USER', 'MERCHANT'], true)): ?>
            <a href="<?= e(url('/saved-coupons')) ?>"><?= icon('ticket') ?>Coupon đã lưu</a>
            <a href="<?= e(url('/my-coupons')) ?>"><?= icon('flag') ?>Đóng góp của tôi</a>
            <?php endif; ?>
            <?php if ($currentUser['role'] === 'USER'): ?>
            <a href="<?= e(url('/merchant/apply')) ?>"><?= icon('store') ?>Đăng ký quản lý shop</a>
            <?php elseif ($currentUser['role'] === 'MERCHANT'): ?>
            <a href="<?= e(url('/merchant')) ?>"><?= icon('store') ?>Không gian đối tác</a>
            <?php else: ?>
            <a href="<?= e(url('/admin')) ?>"><?= icon('grid') ?>Trung tâm quản trị</a>
            <?php endif; ?>
        </nav>
        <form method="post" action="<?= e(url('/logout.php')) ?>" class="account-signout">
            <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
            <button type="submit"><?= icon('arrow') ?>Đăng xuất</button>
        </form>
    </div>
</details>
<?php else: ?>
    <?php if ($hasAuthModal ?? false): ?>
    <button type="button" class="btn btn-outline-brand" data-bs-toggle="modal" data-bs-target="#loginModal">Đăng nhập</button>
    <?php else: ?>
    <a class="btn btn-outline-brand" href="<?= e(url('/login.php')) ?>">Đăng nhập</a>
    <?php endif; ?>
<?php endif; ?>

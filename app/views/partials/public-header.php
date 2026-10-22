<?php
$currentUser = $currentUser ?? \App\Core\Auth::user();
$headerPath = (new \App\Core\Request())->path();
$headerLinks = [
    ['/', 'Khám phá', in_array($headerPath, ['/', '/index.php'], true)],
    ['/categories', 'Danh mục', $headerPath === '/categories'],
    ['/coupons', 'Coupon cộng đồng', str_starts_with($headerPath, '/coupons')],
    ['/articles', 'Bài viết', str_starts_with($headerPath, '/articles') || str_starts_with($headerPath, '/guides/')],
    ['/campaigns', 'Chiến dịch', $headerPath === '/campaign-calendar' || str_starts_with($headerPath, '/campaigns')],
];
?>
<header class="site-header public-header">
    <div class="container-wide header-inner">
        <a class="brand" href="<?= e(url('/')) ?>" aria-label="CouponHub - Trang chủ"><span class="brand-mark"><span>C</span></span><span class="brand-word">Coupon<em>Hub</em><span class="brand-period">.</span></span></a>
        <nav class="site-nav" aria-label="Điều hướng chính">
            <?php foreach ($headerLinks as [$href, $label, $active]): ?>
            <a href="<?= e(url($href)) ?>" <?= $active ? 'class="active" aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-account">
            <!-- Khung Nút Chuông & Popover Thông Báo -->
            <details class="header-notification-wrapper">
                <summary class="header-search header-notification" aria-label="Thông báo" title="Thông báo">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                    </svg>
                    <span class="notification-dot"></span>
                </summary>

                <!-- Bảng danh sách thông báo xổ xuống -->
                <div class="notification-dropdown">
                    <div class="notif-header">
                        <strong>Thông báo</strong>
                        <a href="javascript:void(0)" class="mark-as-read">Đã đọc tất cả</a>
                    </div>
                    <div class="notif-body">
                        <!-- Thông báo 1 (Mới) -->
                        <a href="<?= e(url('/coupons')) ?>" class="notif-item unread">
                            <p class="notif-title">Voucher Shopee 50K vừa được cập nhật!</p>
                            <span class="notif-time">5 phút trước</span>
                        </a>
                        <!-- Thông báo 2 -->
                        <a href="<?= e(url('/campaigns')) ?>" class="notif-item">
                            <p class="notif-title">Chiến dịch Sale giữa tháng đã bắt đầu.</p>
                            <span class="notif-time">2 giờ trước</span>
                        </a>
                        <!-- Thông báo 3 -->
                        <a href="<?= e(url('/articles')) ?>" class="notif-item">
                            <p class="notif-title">Bài viết mới: Mẹo săn mã giảm giá GrabFood.</p>
                            <span class="notif-time">1 ngày trước</span>
                        </a>
                    </div>
                    <div class="notif-footer">
                        <a href="<?= e(url('/notifications')) ?>">Xem tất cả thông báo</a>
                    </div>
                </div>
            </details>
            <script>
document.addEventListener('DOMContentLoaded', function() {
    const markReadBtn = document.querySelector('.notif-header .mark-as-read');
    const notifDot = document.querySelector('.header-notification .notification-dot');
    const unreadItems = document.querySelectorAll('.notif-item.unread');

    if (markReadBtn) {
        markReadBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // 1. Ẩn chấm đỏ trên nút Chuông
            if (notifDot) {
                notifDot.style.display = 'none';
            }

            // 2. Chuyển giao diện các thông báo chưa đọc thành đã đọc (bỏ nền xanh nhạt)
            unreadItems.forEach(function(item) {
                item.classList.remove('unread');
            });
        });
    }
});
</script>

            <a class="header-search" href="<?= e(url('/coupons')) ?>#keyword" aria-label="Tìm kiếm ưu đãi"><?= icon('search') ?></a>
            <?php require __DIR__ . '/account-menu.php'; ?>
        </div>
        <details class="mobile-site-menu" data-account-dropdown>
            <summary aria-label="Mở điều hướng chính"><span></span><span></span><span></span></summary>
            <nav aria-label="Điều hướng trên điện thoại">
                <?php foreach ($headerLinks as [$href, $label, $active]): ?>
                <a href="<?= e(url($href)) ?>" <?= $active ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </details>
    </div>
</header>

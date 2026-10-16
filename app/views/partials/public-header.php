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
        <div class="header-account"><a class="header-search" href="<?= e(url('/coupons')) ?>#keyword" aria-label="Tìm kiếm ưu đãi"><?= icon('search') ?></a><?php require __DIR__ . '/account-menu.php'; ?><?php if (!$currentUser): ?><a class="btn btn-brand header-register" href="<?= e(url('/register')) ?>">Đăng ký <?= icon('arrow') ?></a><?php endif; ?></div>
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

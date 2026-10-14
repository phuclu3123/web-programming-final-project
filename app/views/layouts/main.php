<?php

use App\Core\Auth;
use App\Core\Session;

$success = Session::flash('success');
$error = Session::flash('error');
$warning = Session::flash('warning');
$info = Session::flash('info');
$role = Auth::role();
$currentPath = (new \App\Core\Request())->path();
$navSection = match (true) {
    $currentPath === '/' => 'home',
    $currentPath === '/dashboard',
    $currentPath === '/my-coupons',
    $currentPath === '/saved-coupons',
    $currentPath === '/merchant/apply' => 'account',
    str_starts_with($currentPath, '/admin') => 'admin',
    str_starts_with($currentPath, '/merchant') => 'merchant',
    str_starts_with($currentPath, '/coupons') => 'coupons',
    str_starts_with($currentPath, '/articles') => 'articles',
    $currentPath === '/campaign-calendar', str_starts_with($currentPath, '/campaigns') => 'campaigns',
    default => '',
};
$navActive = static fn(string $section): string => $navSection === $section ? ' active' : '';
$navCurrent = static fn(string $section): string => $navSection === $section ? ' aria-current="page"' : '';

?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="<?= e(Session::csrfToken()) ?>">
    <title><?= e($pageTitle ?? 'CouponHub') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(asset('/css/style.css')) ?>">
</head>

<body>
    <nav class="navbar navbar-expand-xl navbar-dark bg-primary shadow-sm sticky-top site-navbar"
        aria-label="Thanh điều hướng chính">
        <div class="container">
            <a class="navbar-brand font-weight-bold" href="<?= e(url('/')) ?>">CouponHub</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Mở menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item<?= $navActive('home') ?>"><a class="nav-link" href="<?= e(url('/')) ?>"
                            <?= $navCurrent('home') ?>>Trang chủ</a></li>
                    <li class="nav-item<?= $navActive('coupons') ?>"><a class="nav-link"
                            href="<?= e(url('/coupons')) ?>" <?= $navCurrent('coupons') ?>>Coupon</a></li>
                    <li class="nav-item<?= $navActive('articles') ?>"><a class="nav-link"
                            href="<?= e(url('/articles')) ?>" <?= $navCurrent('articles') ?>>Bài viết</a></li>
                    <li class="nav-item<?= $navActive('campaigns') ?>"><a class="nav-link"
                            href="<?= e(url('/campaign-calendar')) ?>" <?= $navCurrent('campaigns') ?>>Lịch Campaign</a>
                    </li>
                    <?php if (Auth::check()): ?>
                        <li class="nav-item dropdown<?= $navActive('account') ?>">
                            <a class="nav-link dropdown-toggle" href="#" id="accountNav" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Tài khoản</a>
                            <div class="dropdown-menu" aria-labelledby="accountNav">
                                <a class="dropdown-item" href="<?= e(url('/dashboard')) ?>">Tổng quan tài khoản</a>
                                <?php if (in_array($role, ['USER', 'MERCHANT'], true)): ?>
                                    <a class="dropdown-item" href="<?= e(url('/my-coupons')) ?>">Coupon của tôi</a>
                                    <a class="dropdown-item" href="<?= e(url('/saved-coupons')) ?>"><i
                                            class="fa-regular fa-bookmark mr-2" aria-hidden="true"></i>Coupon đã lưu</a>
                                    <a class="dropdown-item" href="<?= e(url('/merchant/apply')) ?>">Đăng ký quản lý Shop</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endif; ?>
                    <?php if ($role === 'MERCHANT'): ?>
                        <li class="nav-item dropdown<?= $navActive('merchant') ?>">
                            <a class="nav-link dropdown-toggle" href="#" id="merchantNav" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Merchant Center</a>
                            <div class="dropdown-menu" aria-labelledby="merchantNav">
                                <a class="dropdown-item" href="<?= e(url('/merchant')) ?>">Dashboard</a>
                                <a class="dropdown-item" href="<?= e(url('/merchant/coupons')) ?>">Official Coupons</a>
                                <a class="dropdown-item" href="<?= e(url('/merchant/campaigns')) ?>">Campaigns</a>
                            </div>
                        </li>
                    <?php endif; ?>
                    <?php if ($role === 'ADMIN'): ?>
                        <li class="nav-item dropdown<?= $navActive('admin') ?>">
                            <a class="nav-link dropdown-toggle" href="#" id="adminNav" role="button" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">Admin</a>
                            <div class="dropdown-menu" aria-labelledby="adminNav">
                                <a class="dropdown-item" href="<?= e(url('/admin')) ?>">Dashboard</a>
                                <a class="dropdown-item" href="<?= e(url('/admin/users')) ?>">Users</a>
                                <a class="dropdown-item" href="<?= e(url('/admin/merchants')) ?>">Merchants</a>
                                <a class="dropdown-item" href="<?= e(url('/admin/merchant-requests')) ?>">Duyệt quyền
                                    Shop</a>
                                <a class="dropdown-item" href="<?= e(url('/admin/articles')) ?>">Bài viết</a>
                                <a class="dropdown-item" href="<?= e(url('/admin/categories')) ?>">Categories</a>
                                <a class="dropdown-item" href="<?= e(url('/admin/coupons')) ?>">Duyệt coupon</a>
                                <a class="dropdown-item" href="<?= e(url('/admin/campaigns')) ?>">Campaigns</a>
                                <a class="dropdown-item" href="<?= e(url('/admin/reports')) ?>">Reports</a>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ml-auto align-items-xl-center">
                    <?php if (Auth::check()): ?>
                        <li class="nav-item mr-xl-3"><span
                                class="navbar-text text-white-50 small"><?= e((string) (Auth::user()['full_name'] ?? '')) ?></span>
                        </li>
                        <li class="nav-item">
                            <form method="post" action="<?= e(url('/logout')) ?>" class="mb-0"><input type="hidden"
                                    name="_token" value="<?= e(Session::csrfToken()) ?>"><button
                                    class="btn btn-outline-light btn-sm">Đăng xuất</button></form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= e(url('/login')) ?>">Đăng nhập</a></li>
                        <li class="nav-item"><a class="btn btn-light btn-sm ml-xl-2 mt-1 mt-xl-0"
                                href="<?= e(url('/register')) ?>">Đăng ký</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="py-4">
        <div class="container">
            <?php foreach ([['success', $success, 12000], ['info', $info, 12000], ['warning', $warning, 15000], ['danger', $error, 20000]] as [$type, $message, $timeout]): if ($message): ?>
                    <div class="alert alert-<?= e($type) ?> alert-dismissible fade show auto-dismiss-alert"
                        data-timeout="<?= (int)$timeout ?>" role="alert"><?= e($message) ?><button type="button" class="close"
                            data-dismiss="alert"><span>&times;</span></button></div><?php endif;
                                                                            endforeach; ?><?= $content ?>
        </div>
    </main>
    <footer class="site-footer" role="contentinfo">
        <div class="container py-5">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <h2 class="h5 footer-brand mb-3">CouponHub</h2>
                    <p class="footer-description">Chia sẻ, khám phá và kiểm chứng ưu đãi từ cộng đồng. Tìm mã giảm giá
                        phù hợp cho nhu cầu của bạn.</p>
                    <span class="footer-subtle small">Nền tảng demo · PHP OOP + MVC</span>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h3 class="h6 footer-title">Khám phá</h3>
                    <ul class="footer-links list-unstyled mb-0">
                        <li><a href="<?= e(url('/coupons')) ?>">Tìm coupon</a></li>
                        <li><a href="<?= e(url('/campaign-calendar')) ?>">Lịch Campaign</a></li>
                        <li><a href="<?= e(url('/articles')) ?>">Bài viết</a></li>
                        <li><a href="<?= e(url('/about')) ?>">Giới thiệu</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h3 class="h6 footer-title">Chính sách & cộng đồng</h3>
                    <ul class="footer-links list-unstyled mb-0">
                        <li><a href="<?= e(url('/terms')) ?>">Điều khoản sử dụng</a></li>
                        <li><a href="<?= e(url('/privacy')) ?>">Chính sách bảo mật</a></li>
                        <li><a href="<?= e(url('/community-guidelines')) ?>">Quy tắc cộng đồng</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h3 class="h6 footer-title">Hỗ trợ & liên hệ</h3>
                    <p class="footer-description small mb-2">Phát hiện coupon không hợp lệ? Hãy sử dụng chức năng Báo
                        cáo ở trang chi tiết.</p>
                    <a class="footer-contact" href="<?= e(url('/contact')) ?>"><i class="fa-regular fa-envelope mr-2"
                            aria-hidden="true"></i>Thông tin liên hệ</a>
                </div>
            </div>
            <div
                class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-4 pt-3">
                <span>© <?= date('Y') ?> CouponHub. Dự án học tập.</span>
                <span>Thông tin ưu đãi cần được kiểm tra với Merchant.</span>
            </div>
        </div>
    </footer>
    <button id="back-to-top" class="btn btn-primary back-to-top" type="button" aria-label="Về đầu trang"
        title="Về đầu trang"><i class="fa-solid fa-arrow-up"></i></button>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= e(asset('/js/app.js')) ?>"></script><?php foreach (($pageScripts ?? []) as $pageScript): ?><script
            src="<?= e(asset((string)$pageScript)) ?>"></script><?php endforeach; ?>
</body>

</html>
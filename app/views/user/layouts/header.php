<?php
if (!isset($pageTitle)) {
    $pageTitle = 'CouponHub - Săn mã giảm giá';
}

if (!isset($activePage)) {
    $activePage = '';
}

require_once __DIR__ . '/../data/coupons.php';
$publicCouponsList = array_values(publicCoupons());
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/app/assets/css/user.css">
    <script>
        // Nhúng dữ liệu mẫu công khai cho ô tìm kiếm header
        window.ALL_COUPONS = <?= json_encode($publicCouponsList, JSON_UNESCAPED_UNICODE) ?>;
    </script>
</head>
<body>

<header class="site-header">
    <div class="header-container">

        <a href="/app/views/user/home.php" class="logo">
            Coupon<span>Hub</span>
        </a>

        <!-- Nút Mobile Menu -->
        <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Mở Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="main-nav" id="mainNav">
            <a href="/app/views/user/home.php" class="<?= $activePage === 'home' ? 'active' : '' ?>">
                Trang chủ
            </a>
            <a href="/app/views/user/categories.php" class="<?= $activePage === 'categories' ? 'active' : '' ?>">
                Danh mục
            </a>
            <a href="/app/views/user/campaigns.php" class="<?= $activePage === 'campaigns' ? 'active' : '' ?>">
                Chiến dịch
            </a>
            <a href="/app/views/user/submit_coupon.php" class="<?= $activePage === 'submit' ? 'active' : '' ?>">
                + Đóng góp mã
            </a>
            <a href="/app/views/user/my_coupons.php" class="<?= $activePage === 'my_coupons' ? 'active' : '' ?>">
                Mã của tôi
            </a>
            <a href="/app/views/user/favorites.php" class="<?= $activePage === 'favorites' ? 'active' : '' ?>">
                Yêu thích
            </a>
        </nav>

        <div class="header-right-actions">
            <!-- Form Tìm Kiếm Trục Tiếp Trên Header -->
            <form class="header-search" action="/app/views/user/search.php" method="GET" id="headerSearchForm">
                <div class="search-input-wrapper">
                    <input
                        type="search"
                        id="headerSearchInput"
                        name="q"
                        placeholder="Tìm cửa hàng, mã giảm giá..."
                        value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        autocomplete="off"
                    >
                    <!-- Khung Dropdown Gợi Ý Tìm Kiếm -->
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>

                <button type="submit" aria-label="Tìm kiếm">
                    ⌕
                </button>
            </form>

            <button type="button" class="auth-btn open-auth-modal">
                Đăng nhập
            </button>
        </div>

    </div>
</header>

<main class="main-content">
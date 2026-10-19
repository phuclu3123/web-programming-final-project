<?php
$pageTitle = 'Mã giảm giá yêu thích - CouponHub';
$activePage = 'favorites';

require_once __DIR__ . '/data/coupons.php';
require_once __DIR__ . '/layouts/header.php';
?>

<main class="category-page">
    <section class="category-heading">
        <div class="heading-text-group">
            <span class="category-eyebrow">TƯƠNG TÁC / LƯU TRỮ</span>
            <h1>Mã đã yêu thích</h1>
            <p>Các coupon bạn đã lưu để nhanh chóng áp dụng khi thanh toán.</p>
        </div>
    </section>

    <!-- Khung danh sách mã được render động hoàn toàn bằng JavaScript -->
    <div class="category-coupon-list" id="favoriteCouponList"></div>

    <!-- Khối hiển thị khi chưa thả tim mã nào -->
    <div class="category-empty" id="emptyFavoriteNotice" style="display: none;">
        <h3>Chưa có mã yêu thích nào</h3>
        <p>Khám phá và bấm thả tim ❤️ các mã giảm giá hấp dẫn để lưu lại nhé!</p>

        <a href="/app/views/user/categories.php" class="category-view-button" style="margin-top: 12px; display: inline-block;">
            Khám phá ưu đãi ngay ↗
        </a>
    </div>
</main>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
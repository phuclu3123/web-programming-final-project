<?php
$pageTitle = 'Mã của tôi - CouponHub';
$activePage = 'my_coupons';

require_once __DIR__ . '/layouts/header.php';
?>

<main class="category-page">
    <section class="category-heading">
        <div class="heading-text-group">
            <span class="category-eyebrow">COMMUNITY / QUẢN LÝ</span>
            <h1>Mã đóng góp của tôi</h1>
            <p>Xem danh sách mã bạn đã gửi và tiến độ kiểm duyệt từ quản trị viên.</p>
        </div>
    </section>

    <!-- Khung danh sách bài gửi hiển thị động qua JavaScript -->
    <div class="my-coupons-list" id="myCouponsList"></div>
</main>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
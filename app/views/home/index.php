<?php use App\Core\Auth; ?>
<section class="jumbotron bg-white shadow-sm border">
    <div class="row align-items-center">
        <div class="col-lg-8">
            <span class="badge badge-primary mb-3">Web Programming Final Project</span>
            <h1 class="display-4 font-weight-bold">Tìm coupon tốt hơn. Chia sẻ coupon đáng tin hơn.</h1>
            <p class="lead text-muted">CouponHub kết nối cộng đồng người dùng và Merchant để tìm kiếm, chia sẻ và xác minh mã giảm giá.</p>
            <div class="mt-4">
                <a href="<?= e(url('/coupons')) ?>" class="btn btn-primary btn-lg mr-2">Tìm coupon</a>
                <?php if (in_array(Auth::role(), ['USER', 'MERCHANT'], true)): ?>
                    <a href="<?= e(url('/coupons/create')) ?>" class="btn btn-outline-primary btn-lg">Chia sẻ coupon</a>
                <?php elseif (!Auth::check()): ?>
                    <a href="<?= e(url('/register')) ?>" class="btn btn-outline-primary btn-lg">Đăng ký</a>
                <?php elseif (Auth::role() === 'ADMIN'): ?>
                    <a href="<?= e(url('/admin')) ?>" class="btn btn-outline-primary btn-lg">Admin Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card border-0 bg-light">
                <div class="card-body">
                    <h5 class="card-title">Phase 3 · Community Interaction</h5>
                    <ul class="mb-0 pl-3 text-muted">
                        <li>Favorite bằng AJAX/JSON</li>
                        <li>Vote + Trust Score realtime</li>
                        <li>Comment bằng AJAX</li>
                        <li>Report + Admin queue</li>
                        <li>CouponPoint + Level</li>
                        <li>Role-aware navigation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="row">
    <div class="col-md-4 mb-3"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Coupon cộng đồng</h5><p class="text-muted mb-0">User/Merchant gửi coupon cộng đồng, hệ thống kiểm tra dữ liệu và chuyển sang PENDING để Admin duyệt.</p></div></div></div>
    <div class="col-md-4 mb-3"><div class="card h-100 shadow-sm"><div class="card-body"><h5>Community Verification</h5><p class="text-muted mb-0">Vote WORKING / NOT_WORKING / CONDITION_CHANGED cập nhật Trust Score mà không tải lại trang.</p></div></div></div>
    <div class="col-md-4 mb-3"><div class="card h-100 shadow-sm"><div class="card-body"><h5>CouponPoint</h5><p class="text-muted mb-0">Người đóng góp nhận điểm khi coupon được duyệt và khi đạt các mốc xác minh/lưu theo rule của database.</p></div></div></div>
</div>

<?php use App\Core\Session; ?>
<div class="container py-4 py-lg-5">
    <div class="row justify-content-center">
        <div class="col-xl-8">
            <nav aria-label="Đường dẫn" class="small mb-3"><a href="<?= e(url('/deal-requests')) ?>">Cộng đồng tìm
                    mã</a> / Đăng yêu cầu</nav>
            <h1 class="h2 fw-bold mb-2">Bạn đang cần mã giảm giá nào?</h1>
            <p class="text-muted">Viết rõ cửa hàng, sản phẩm và điều kiện mong muốn để thành viên khác tìm coupon phù
                hợp. Không cung cấp dữ liệu cá nhân, mật khẩu hay thông tin thanh toán.</p>
            <form action="<?= e(url('/deal-requests')) ?>" method="post" class="card shadow-sm"
                aria-label="Đăng yêu cầu tìm coupon">
                <div class="card-body p-4">
                    <input type="hidden" name="csrf" value="<?= e(Session::csrfToken()) ?>">
                    <div class="mb-3"><label for="dr-title" class="form-label fw-semibold">Tiêu đề yêu cầu</label>
                        <input id="dr-title" name="title" type="text" class="form-control" maxlength="160"
                            minlength="10" required placeholder="Ví dụ: Ai có mã Highlands hôm nay không?">
                        <div class="form-text">10–160 ký tự.</div>
                    </div>
                    <div class="mb-4"><label for="dr-body" class="form-label fw-semibold">Mô tả nhu cầu</label>
                        <textarea id="dr-body" name="body" class="form-control" minlength="10" maxlength="1500" rows="5"
                            required
                            placeholder="Ví dụ: Mình cần coupon còn dùng được cho đồ uống mua trực tiếp tại cửa hàng, ưu tiên đơn dưới 200.000đ."></textarea>
                        <div class="form-text">10–1.500 ký tự. Tối đa 5 yêu cầu đang mở cho mỗi tài khoản.</div>
                    </div>
                    <div class="d-flex flex-wrap gap-2"><button type="submit" class="btn btn-primary">Đăng yêu
                            cầu</button>
                        <a href="<?= e(url('/deal-requests')) ?>" class="btn btn-outline-secondary">Quay lại</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
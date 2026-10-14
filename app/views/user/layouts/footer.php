</main>

<footer class="site-footer">
    <div class="footer-container">

        <div class="footer-brand">
            <a href="/app/views/user/home.php" class="logo">
                Coupon<span>Hub</span>
            </a>
            <p>
                Khám phá ưu đãi, tiết kiệm chi tiêu
                và mua sắm thông minh hơn.
            </p>
        </div>

        <div class="footer-links">
            <h3>Khám phá</h3>
            <a href="/app/views/user/home.php">Trang chủ</a>
            <a href="/app/views/user/categories.php">Danh mục</a>
            <a href="/app/views/user/campaigns.php">Chiến dịch</a>
        </div>

        <div class="footer-links">
            <h3>Cộng đồng</h3>
            <a href="/app/views/user/submit_coupon.php">Đóng góp mã giảm giá</a>
            <a href="/app/views/user/my_coupons.php">Mã của tôi</a>
            <a href="/app/views/admin/pending_coupons.php" style="color: #a99bf8;">[Demo Admin Duyệt Mã]</a>
        </div>

    </div>

    <div class="footer-bottom">
        &copy; <?= date('Y') ?> CouponHub. All rights reserved.
    </div>
</footer>

<!-- Modal Đăng nhập / Đăng ký dùng chung -->
<div id="authModal" class="modal-overlay" style="display: none;">
    <div class="modal-content auth-modal-content">
        <button type="button" class="close-modal-btn" id="closeAuthModal">&times;</button>
        
        <div class="auth-tabs">
            <button type="button" class="auth-tab-btn active" data-target="loginFormContent">Đăng nhập</button>
            <button type="button" class="auth-tab-btn" data-target="registerFormContent">Đăng ký</button>
        </div>

        <!-- Form Đăng nhập -->
        <div id="loginFormContent" class="auth-form-content">
            <form onsubmit="event.preventDefault(); alert('Đăng nhập Demo thành công!'); document.getElementById('authModal').style.display='none';">
                <div class="form-group">
                    <label>Email / Tên đăng nhập</label>
                    <input type="text" placeholder="nhapemail@gmail.com" required>
                </div>
                <div class="form-group" style="margin-top: 12px;">
                    <label>Mật khẩu</label>
                    <input type="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="detail-primary-button" style="width: 100%; margin-top: 20px;">Đăng nhập</button>
            </form>
        </div>

        <!-- Form Đăng ký -->
        <div id="registerFormContent" class="auth-form-content" style="display: none;">
            <form onsubmit="event.preventDefault(); alert('Đăng ký tài khoản Demo thành công!'); document.getElementById('authModal').style.display='none';">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" placeholder="Nguyễn Văn A" required>
                </div>
                <div class="form-group" style="margin-top: 12px;">
                    <label>Email</label>
                    <input type="email" placeholder="email@gmail.com" required>
                </div>
                <div class="form-group" style="margin-top: 12px;">
                    <label>Mật khẩu</label>
                    <input type="password" placeholder="Tạo mật khẩu" required>
                </div>
                <button type="submit" class="detail-primary-button" style="width: 100%; margin-top: 20px;">Tạo tài khoản</button>
            </form>
        </div>
    </div>
</div>

<script src="/app/assets/js/user.js"></script>
</body>
</html>
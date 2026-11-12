<?php /* Tách đồng ý cookie tùy chọn khỏi cookie phiên cần thiết cho đăng nhập. */ ?><link rel="stylesheet" href="<?= e(url('/assets/css/cookie-consent.css')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/css/cookie-consent.css') ?>">
<aside class="cookie-notice" data-cookie-notice aria-labelledby="cookie-notice-title" hidden>
    <p class="cookie-notice-eyebrow">QUYỀN RIÊNG TƯ CỦA BẠN</p>
    <h2 id="cookie-notice-title">Lựa chọn cookie</h2>
    <p>CouponHub sử dụng cookie để bảo mật tài khoản và hỗ trợ trải nghiệm của bạn. Bạn có thể chấp nhận tất cả hoặc chỉ sử dụng cookie cần thiết.</p>
    <details class="cookie-notice-details">
        <summary>Quản lý cookie</summary>
        <p><strong>Cần thiết · Luôn bật</strong><br>Phiên đăng nhập, bảo mật biểu mẫu và lưu lựa chọn này.</p>
        <p><strong>Tùy chọn</strong><br>Cho phép dịch vụ bên thứ ba hỗ trợ trải nghiệm đăng nhập. Bạn có thể thay đổi lựa chọn bất cứ lúc nào.</p>
        <a href="<?= e(url('/privacy')) ?>">Chính sách bảo mật</a>
    </details>
    <div class="cookie-notice-actions">
        <button type="button" data-cookie-choice="necessary">Chỉ cần thiết</button>
        <button type="button" class="cookie-notice-accept" data-cookie-choice="all">Chấp nhận tất cả</button>
    </div>
    <p class="cookie-notice-feedback" data-cookie-feedback role="status" aria-live="polite"></p>
</aside>
<script src="<?= e(url('/assets/js/cookie-consent.js')) ?>?v=<?= filemtime(__DIR__ . '/../../../assets/js/cookie-consent.js') ?>" defer></script>

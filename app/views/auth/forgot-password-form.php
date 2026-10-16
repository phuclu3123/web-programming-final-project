<!-- Nội dung tạm để kiểm tra chuyển sang quên mật khẩu. -->
<div class="modal-header ms-3 mt-3 me-3 pe-5">
    <div>
        <p class="text-success mb-1">Khôi phục tài khoản</p>
        <h1 class="modal-title fs-1 mb-1" id="forgotPasswordTitle">Quên mật khẩu thử</h1>
        <p class="text-secondary mb-0">Nhập email đã đăng ký để nhận mã OTP gồm 6 chữ số.</p>
    </div>
</div>
<div class="modal-body ms-3 me-3">
    <form class="mb-4" id="forgotPasswordForm" novalidate>
        <div id="forgotPasswordMessage" class="auth-message mb-3" role="status" aria-live="polite" hidden></div>
        <!-- Email -->
        <label for="forgotPasswordEmail" class="form-label">Email đã đăng ký</label>

        <div class="login-input d-flex align-items-center gap-3 mb-3">
            <i class="bi bi-envelope fs-4 text-secondary ms-3"></i>

            <input id="forgotPasswordEmail" type="email" class="form-control border-0 shadow-none bg-transparent"
                placeholder="Nhập email đã đăng ký" name="email" autocomplete="email" required>
        </div>
    </form>
    <button type="submit" form="forgotPasswordForm" class="btn w-100 rounded-4 fw-bold text-white login-btn mb-3">
        Gửi mã OTP
    </button>
    <div class="d-flex justify-content-center">
        <button type="button" class="btn btn-link p-0 text-decoration-none" data-auth-target="login">
            Quay lại đăng nhập
        </button>
    </div>
</div>

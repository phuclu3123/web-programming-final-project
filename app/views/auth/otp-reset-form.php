<?php
// Mỗi trường mật khẩu có nút hiện/ẩn riêng; OTP chỉ được xác nhận ở server.

use App\Core\Session; ?>
<div class="modal-header mx-3 mt-3 pe-5">
    <div>
        <p class="text-success mb-1">Khôi phục tài khoản</p>
        <h2 class="modal-title fs-2 mb-2" id="resetTitle">Đặt mật khẩu mới</h2>
        <p class="text-secondary mb-0">Nhập mã 6 số từ email. Mã chỉ dùng một lần và cho phép tối đa 5 lần thử.</p>
    </div>
</div>
<div class="modal-body mx-3">
    <form id="resetOtpForm" method="post" action="<?= e(url('/auth/reset-otp')) ?>">
        <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
        <div class="auth-message mb-3" role="status" aria-live="polite" hidden></div>
        <label class="form-label" for="resetOtp">Mã xác minh</label>
        <input class="form-control mb-3" id="resetOtp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}"
            minlength="6" maxlength="6" autocomplete="one-time-code" required>
        <label class="form-label" for="resetNewPassword">Mật khẩu mới</label>
        <div class="login-input d-flex align-items-center gap-3 mb-3">
            <i class="fa-solid fa-lock auth-field-icon text-secondary ms-3" aria-hidden="true"></i>
            <input class="form-control border-0 shadow-none bg-transparent" id="resetNewPassword" name="new_password" type="password"
                autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="resetPasswordHelp" required>
            <button type="button" class="btn border-0 p-0 me-3 flex-shrink-0 text-secondary"
                data-password-toggle="resetNewPassword" aria-controls="resetNewPassword" aria-label="Hiện mật khẩu mới"
                aria-pressed="false">
                <i class="fa-solid fa-eye auth-password-eye auth-password-eye--show" aria-hidden="true"></i><i class="fa-solid fa-eye-slash auth-password-eye auth-password-eye--hide" aria-hidden="true"></i>
            </button>
        </div>
        <p class="small text-secondary" id="resetPasswordHelp">Ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt. Tối đa 72 byte.
        </p>
        <label class="form-label" for="resetConfirmation">Nhập lại mật khẩu mới</label>
        <div class="login-input d-flex align-items-center gap-3 mb-4">
            <i class="fa-solid fa-lock auth-field-icon text-secondary ms-3" aria-hidden="true"></i>
            <input class="form-control border-0 shadow-none bg-transparent" id="resetConfirmation" name="password_confirmation" type="password"
                autocomplete="new-password" minlength="8" maxlength="72" required>
            <button type="button" class="btn border-0 p-0 me-3 flex-shrink-0 text-secondary"
                data-password-toggle="resetConfirmation" aria-controls="resetConfirmation" aria-label="Hiện mật khẩu mới nhập lại"
                aria-pressed="false">
                <i class="fa-solid fa-eye auth-password-eye auth-password-eye--show" aria-hidden="true"></i><i class="fa-solid fa-eye-slash auth-password-eye auth-password-eye--hide" aria-hidden="true"></i>
            </button>
        </div>
        <button type="submit" class="btn login-btn w-100 text-white fw-bold rounded-4">Đổi mật khẩu</button>
    </form>
    <button type="button" data-auth-target="forgot" class="btn btn-link mt-3 w-100">Yêu cầu mã mới</button>
</div>

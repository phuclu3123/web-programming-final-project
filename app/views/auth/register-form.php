<?php

use App\Core\Session;
?>
<!-- PHẦN ĐĂNG KÝ: giữ giao diện, bổ sung giới hạn thuộc tính theo database. -->
<div class="modal-header ms-3 mt-3 me-3 pe-5">
    <div>
        <p class="text-success mb-1">Đăng ký tài khoản</p>
        <h1 class="modal-title fs-1 mb-1" id="registerTitle">Đăng ký tài khoản</h1>
        <p class="text-secondary mb-0">Chia sẻ Coupon công khai miễn phí.</p>
    </div>
</div>
<div class="modal-body ms-3 me-3">
    <form method="post" action="<?= e(url('/api/register.php')) ?>" class="mb-4" id="registerForm" novalidate>
        <!-- ---- CSRF: token phiên dùng chung với endpoint đăng ký ---- -->
        <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
        <div id="registerMessage" class="auth-message mb-3" role="status" aria-live="polite" hidden></div>
        <!-- Email -->
        <label for="registerFullName" class="form-label">Họ và tên</label>

        <div class="login-input d-flex align-items-center gap-3 mb-3">
            <svg class="auth-field-icon text-secondary ms-3" aria-hidden="true" focusable="false"><use href="#auth-fa-user"></use></svg>

            <input id="registerFullName" type="text" class="form-control border-0 shadow-none bg-transparent"
                placeholder="Nhập họ và tên" name="full_name" autocomplete="name" maxlength="120" required>
        </div>

        <!-- Tên tài khoản -->
        <label for="registerUsername" class="form-label">Tên tài khoản</label>

        <div class="login-input d-flex align-items-center gap-3 mb-3">
            <svg class="auth-field-icon text-secondary ms-3" aria-hidden="true" focusable="false"><use href="#auth-fa-user"></use></svg>

            <input id="registerUsername" type="text" class="form-control border-0 shadow-none bg-transparent"
                placeholder="Nhập tên tài khoản" name="username" autocomplete="username" maxlength="50"
                aria-describedby="registerUsernameHelp" required>
        </div>
        <p id="registerUsernameHelp" class="small text-secondary">Tên tài khoản gồm chữ không dấu, số, dấu chấm, gạch
            dưới hoặc gạch ngang.</p>

        <!-- Email -->
        <label for="registerEmail" class="form-label">Địa chỉ email</label>

        <div class="login-input d-flex align-items-center gap-3 mb-3">
            <svg class="auth-field-icon text-secondary ms-3" aria-hidden="true" focusable="false"><use href="#auth-fa-envelope"></use></svg>

            <input id="registerEmail" type="email" class="form-control border-0 shadow-none bg-transparent"
                placeholder="hoten.mssv@ueh.edu.vn" name="email" autocomplete="email" maxlength="191" required>
        </div>

        <label for="registerPhone" class="form-label">Số điện thoại</label>

        <div class="login-input d-flex align-items-center gap-3 mb-3">
            <svg class="auth-field-icon text-secondary ms-3" aria-hidden="true" focusable="false"><use href="#auth-fa-phone"></use></svg>

            <input id="registerPhone" type="tel" class="form-control border-0 shadow-none bg-transparent"
                placeholder="Nhập số điện thoại" name="phone" autocomplete="tel" maxlength="20" required>
        </div>

        <div class="row g-3">
            <!-- Mật khẩu -->
            <div class="col-12">
                <label for="registerPassword" class="form-label">Mật khẩu</label>
                <div class="login-input d-flex align-items-center gap-3 mb-3">
                    <svg class="auth-field-icon text-secondary ms-3" aria-hidden="true" focusable="false"><use href="#auth-fa-lock"></use></svg>

                    <input id="registerPassword" type="password"
                        class="form-control border-0 shadow-none bg-transparent" placeholder="Tối thiểu 8 ký tự"
                        name="password" autocomplete="new-password" minlength="8"
                        aria-describedby="registerPasswordHelp" required>
                    <button type="button" class="btn border-0 p-0 me-3 flex-shrink-0 text-secondary"
                        data-password-toggle="registerPassword" aria-controls="registerPassword"
                        aria-label="Hiện mật khẩu" aria-pressed="false">
                        <svg class="auth-password-eye auth-password-eye--show" aria-hidden="true" focusable="false"><use href="#auth-fa-eye"></use></svg><svg class="auth-password-eye auth-password-eye--hide" aria-hidden="true" focusable="false"><use href="#auth-fa-eye-slash"></use></svg>
                    </button>
                </div>
            </div>

            <!-- Xác nhận mật khẩu -->
            <div class="col-12">
                <label for="registerPasswordConfirm" class="form-label">Nhập lại mật khẩu</label>
                <div class="login-input d-flex align-items-center gap-3 mb-3">
                    <svg class="auth-field-icon text-secondary ms-3" aria-hidden="true" focusable="false"><use href="#auth-fa-lock"></use></svg>

                    <input id="registerPasswordConfirm" type="password"
                        class="form-control border-0 shadow-none bg-transparent" placeholder="Nhập lại mật khẩu"
                        name="password_confirmation" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="btn border-0 p-0 me-3 flex-shrink-0 text-secondary"
                        data-password-toggle="registerPasswordConfirm" aria-controls="registerPasswordConfirm"
                        aria-label="Hiện mật khẩu nhập lại" aria-pressed="false">
                        <svg class="auth-password-eye auth-password-eye--show" aria-hidden="true" focusable="false"><use href="#auth-fa-eye"></use></svg><svg class="auth-password-eye auth-password-eye--hide" aria-hidden="true" focusable="false"><use href="#auth-fa-eye-slash"></use></svg>
                    </button>
                </div>
            </div>
        </div>
        <p id="registerPasswordHelp" class="small text-secondary">
            Mật khẩu có ít nhất 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt; tối đa 72 byte (ký tự có dấu có
            thể chiếm nhiều byte).
        </p>

    </form>
    <button type="submit" form="registerForm" class="btn w-100 rounded-4 fw-bold text-white login-btn mb-2">
        Tạo tài khoản
    </button>

    <div class="d-flex flex-row justify-content-center align-items-center mb-3 gap-1">
        <span class="form-label mb-0">
            Đã có tài khoản ?
        </span>

        <button type="button" class="btn btn-link p-0 text-decoration-none" data-auth-target="login">
            Đăng nhập ngay
        </button>
    </div>
</div>
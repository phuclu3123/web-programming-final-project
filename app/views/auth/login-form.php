<?php
use App\Core\Session;
?>
<!-- Nội dung đăng nhập; khung modal nằm trong auth-modal.php. -->
<div class="modal-header ms-3 mt-3 me-3 pe-5">
    <div>
        <p class="text-success mb-1">Đăng nhập tài khoản</p>
        <h1 class="modal-title fs-1 mb-1" id="loginTitle">Chào mừng bạn trở lại</h1>
        <p class="text-secondary mb-0">Đăng nhập để lưu và chia sẻ ưu đãi bạn yêu thích.</p>
    </div>
</div>
<div class="modal-body ms-3 me-3">
    <div>

        <form method="POST" action="<?= e(url('/api/login.php')) ?>" class="mb-4" id="loginForm" novalidate>
            <!-- ---- CSRF/REDIRECT: PHP tạo token, JS gửi kèm FormData ---- -->
            <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
            <input type="hidden" name="return_url" value="<?= e(is_string($_GET['return'] ?? null) ? $_GET['return'] : '') ?>">
            <div id="loginMessage" class="auth-message mb-3" role="status" aria-live="polite" hidden></div>
            <!-- Email -->
            <label for="loginAccount" class="form-label">Email hoặc tài khoản</label>

            <div class="login-input d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-envelope fs-4 text-secondary ms-3"></i>

                <input id="loginAccount" type="text" class="form-control border-0 shadow-none bg-transparent"
                    placeholder="Nhập email hoặc tài khoản" name="account" autocomplete="username" required>
            </div>

            <!-- Mật khẩu -->
            <div class="d-flex flex-row justify-content-between align-items-center mb-1">
                <label for="loginPassword" class="form-label mb-0">
                    Mật khẩu
                </label>

                <a href="<?= e(url('/forgot-password')) ?>" class="btn btn-link p-0 text-decoration-none">
                    Quên mật khẩu?
                </a>
            </div>

            <div class="login-input d-flex align-items-center gap-3 mb-3">
                <i class="bi bi-lock fs-4 text-secondary ms-3"></i>

                <input id="loginPassword" type="password" class="form-control border-0 shadow-none bg-transparent"
                    placeholder="Nhập mật khẩu" name="password" autocomplete="current-password" required>
                <button type="button" class="btn border-0 p-0 me-3 flex-shrink-0 text-secondary"
                    data-password-toggle="loginPassword" aria-controls="loginPassword" aria-label="Hiện mật khẩu"
                    aria-pressed="false">
                    <i class="bi bi-eye fs-5" aria-hidden="true"></i>
                </button>
            </div>


        </form>

        <!-- button Đăng nhập -->

        <button type="submit" form="loginForm" class="btn w-100 rounded-4 fw-bold text-white login-btn">
            Đăng nhập
        </button>

        <!-- Hoặc tiếp tục với -->
        <div class="d-flex align-items-center gap-3 my-4">
            <div class="flex-grow-1 border-top"></div>

            <span class="text-secondary fw-semibold text-nowrap">
                Đăng nhập nhanh · Sắp ra mắt
            </span>

            <div class="flex-grow-1 border-top"></div>
        </div>


        <div class="row g-3 mb-4">
            <!-- OAuth Google: chuẩn bị cấu hình, chưa bật đăng nhập thật. -->
            <div class="col-6">
                <button type="button" disabled title="Đăng nhập Google sẽ được bổ sung sau" class="btn social-btn w-100 rounded-4 fw-bold d-flex align-items-center justify-content-center gap-3">

                    <img src="https://www.google.com/favicon.ico" alt="" width="20" height="20">

                    Google
                </button>
            </div>

            <!-- GitHub -->
            <div class="col-6">
                <button type="button" disabled title="Đăng nhập GitHub sẽ được bổ sung sau" class="btn social-btn w-100 rounded-4 fw-bold d-flex align-items-center justify-content-center gap-3">

                    <i class="bi bi-github fs-5"></i>

                    GitHub
                </button>
            </div>
        </div>

        <div class="d-flex flex-row justify-content-center align-items-center mb-3 gap-1">
            <span class="form-label mb-0">
                Chưa có tài khoản ?
            </span>

            <button type="button" class="btn btn-link p-0 text-decoration-none" data-auth-target="register">
                Đăng ký ngay
            </button>
        </div>

    </div>
</div>

<!-- Nhúng một lần trước thẻ đóng body. Trang chứa modal tải Bootstrap và Bootstrap Icons. -->
<link rel="stylesheet" href="<?= e(url('/assets/css/auth/auth.css')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/css/auth/auth.css') ?>">

<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered login-dialog">
        <div class="modal-content">
            <button type="button" class="btn-close auth-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            <div data-auth-view="login">
                <?php require __DIR__ . '/login-form.php'; ?>
            </div>
            <div data-auth-view="register" hidden>
                <?php require __DIR__ . '/register-form.php'; ?>
            </div>
            <div data-auth-view="forgot-password" hidden>
                <?php require __DIR__ . '/forgot-password-form.php'; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?= e(url('/assets/js/auth/auth.js')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/js/auth/auth.js') ?>" defer></script>

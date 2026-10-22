<?php
$authPath = (new \App\Core\Request())->path();
$autoAuthView = in_array($authPath, ['/register'], true) ? 'register' : (in_array($authPath, ['/login', '/login.php'], true) ? 'login' : '');
?>
<!-- Native dialog works across both existing Bootstrap layout versions. -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(url('/assets/css/auth/auth.css')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/css/auth/auth.css') ?>">

<dialog class="auth-dialog" id="loginModal" aria-labelledby="loginTitle" data-auth-auto="<?= e($autoAuthView) ?>">
    <div class="modal-dialog modal-dialog-centered login-dialog">
        <div class="modal-content">
            <button type="button" class="auth-close" data-auth-close aria-label="Đóng">×</button>
            <div data-auth-view="login">
                <?php require __DIR__ . '/login-form.php'; ?>
            </div>
            <div data-auth-view="register" hidden>
                <?php require __DIR__ . '/register-form.php'; ?>
            </div>
        </div>
    </div>
</dialog>

<script src="<?= e(url('/assets/js/auth/auth.js')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/js/auth/auth.js') ?>" defer></script>

<?php

declare(strict_types=1);

use App\Core\Session;
use App\Core\OAuthPreparation;

require __DIR__ . '/app/bootstrap.php';
try {
    Session::start();
    OAuthPreparation::requireSetupAccess();
    $preparation = new OAuthPreparation(require __DIR__ . '/config/oauth.php');
} catch (Throwable $error) {
    http_response_code($error instanceof HttpError ? $error->status : 503);
    exit('Không thể truy cập công cụ chuẩn bị OAuth.');
}
header('Cache-Control: no-store');
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Chuẩn bị OAuth · CouponHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <main class="container py-5">
        <h1>Chuẩn bị Google / GitHub OAuth</h1>
        <p>Đây là công cụ thử cấu hình và callback, chưa đăng nhập tài khoản OAuth.</p>
        <?php foreach (['google' => 'Google', 'github' => 'GitHub'] as $key => $label): $status = $preparation->status($key); ?>
            <section class="card p-4 mb-3">
                <h2 class="h4"><?= e($label) ?></h2>
                <p><?= $status['ready'] ? 'Đã đủ trường cấu hình để thử nhà cung cấp.' : 'Còn thiếu: ' . e(implode(', ', $status['missing'])) ?>
                </p>
                <label class="form-label">Callback cần khai báo trong App</label><input class="form-control mb-3" readonly
                    value="<?= e($status['callback']) ?>">
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach (['probe' => 'Thử callback nội bộ', 'provider' => 'Thử chuyển tới nhà cung cấp'] as $mode => $text): ?>
                        <form data-oauth-prepare><input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
                            <input type="hidden" name="provider" value="<?= e($key) ?>"><input type="hidden" name="mode"
                                value="<?= e($mode) ?>">
                            <button class="btn btn-outline-primary" type="submit"
                                <?= $mode === 'provider' && !$status['ready'] ? 'disabled' : '' ?>><?= e($text) ?></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
        <p id="setup-message" role="status"></p><a href="login.php">Về đăng nhập</a>
    </main>
    <script>
        document.querySelectorAll('[data-oauth-prepare]').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const button = form.querySelector('button');
                if (button.disabled) return;
                button.disabled = true;
                try {
                    const response = await fetch('api/oauth/prepare.php', {
                        method: 'POST',
                        body: new FormData(form)
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) throw new Error(result.message ||
                        'Không thể thử callback.');
                    const target = new URL(result.url, window.location.href);
                    if (target.origin !== window.location.origin && target.origin !==
                        'https://github.com' && target.origin !== 'https://accounts.google.com') {
                        throw new Error('Địa chỉ nhà cung cấp không hợp lệ.');
                    }
                    window.location.assign(target.href);
                } catch (error) {
                    document.getElementById('setup-message').textContent = error.message;
                } finally {
                    button.disabled = false;
                }
            });
        });
    </script>
</body>

</html>
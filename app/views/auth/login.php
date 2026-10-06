<?php use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h4 mb-4">Đăng nhập</h2>

                <?php if (!empty($errors['auth'])): ?>
                    <div class="alert alert-danger"><?= e($errors['auth']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/login')) ?>" novalidate>
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" autocomplete="current-password" required>
                        <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
                </form>
                <p class="text-muted small mt-3 mb-0">Chưa có tài khoản? <a href="<?= e(url('/register')) ?>">Đăng ký</a>.</p>
            </div>
        </div>
    </div>
</div>

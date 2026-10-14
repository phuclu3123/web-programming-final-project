<?php

use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm auth-card">
            <div class="card-body p-4">
                <h1 class="h4 mb-4">Đăng nhập CouponHub</h1>
                <?php if (!empty($errors['auth'])): ?>
                <div class="alert alert-danger" role="alert"><?= e($errors['auth']) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/login')) ?>">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text auth-input-icon"><i
                                        class="fa-solid fa-envelope" aria-hidden="true"></i></span></div>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                id="email" name="email" value="<?= e((string) ($old['email'] ?? '')) ?>" maxlength="191"
                                autocomplete="email" placeholder="Nhập email của bạn" required>
                        </div>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block">
                            <?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text auth-input-icon"><i
                                        class="fa-solid fa-lock" aria-hidden="true"></i></span></div>
                            <input type="password"
                                class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password"
                                name="password" autocomplete="current-password" placeholder="Nhập mật khẩu của bạn"
                                required>
                            <div class="input-group-append">
                                <button class="btn password-toggle" type="button" data-target="#password"
                                    aria-label="Hiện mật khẩu" aria-pressed="false" title="Hiện mật khẩu"><i
                                        class="fa-solid fa-eye password-eye-symbol" aria-hidden="true"></i></button>
                            </div>
                        </div>
                        <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block">
                            <?= e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <div class="text-right mb-3"><a class="small" href="<?= e(url('/forgot-password')) ?>"><i
                                class="fa-solid fa-key mr-1" aria-hidden="true"></i>Quên mật khẩu?</a></div>
                    <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-right-to-bracket mr-1"
                            aria-hidden="true"></i> Đăng nhập</button>
                </form>
                <p class="text-muted small mt-3 mb-0">Chưa có tài khoản? <a href="<?= e(url('/register')) ?>">Đăng
                        ký</a>.</p>
            </div>
        </div>
    </div>
</div>
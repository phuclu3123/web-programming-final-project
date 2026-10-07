<?php use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h2 class="h4 mb-4">Tạo tài khoản User</h2>

                <?php if (!empty($errors['auth'])): ?>
                    <div class="alert alert-danger"><?= e($errors['auth']) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('/register')) ?>" novalidate>
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="form-group">
                        <label for="full_name">Họ và tên</label>
                        <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name" maxlength="120" value="<?= e($old['full_name'] ?? '') ?>" required>
                        <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback"><?= e($errors['full_name']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" maxlength="191" value="<?= e($old['email'] ?? '') ?>" required>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="password">Mật khẩu</label>
                        <div class="input-group">
                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" minlength="8" autocomplete="new-password" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary password-toggle" type="button" data-target="#password" aria-label="Hiện mật khẩu" aria-pressed="false" title="Hiện mật khẩu">
                                    <i class="fa-solid fa-eye password-eye-symbol" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Xác nhận mật khẩu</label>
                        <div class="input-group">
                            <input type="password" class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary password-toggle" type="button" data-target="#password_confirmation" aria-label="Hiện mật khẩu xác nhận" aria-pressed="false" title="Hiện mật khẩu">
                                    <i class="fa-solid fa-eye password-eye-symbol" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <?php if (isset($errors['password_confirmation'])): ?><div class="invalid-feedback d-block"><?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
                </form>
                <p class="text-muted mt-3 mb-0">Đã có tài khoản? <a href="<?= e(url('/login')) ?>">Đăng nhập</a>.</p>
            </div>
        </div>
    </div>
</div>

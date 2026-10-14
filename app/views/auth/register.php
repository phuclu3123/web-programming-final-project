<?php

use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm auth-card">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-2">Đăng ký tài khoản</h1>
                <p class="text-muted mb-4">Mọi tài khoản mới đều là User. Bạn có thể đăng ký quyền quản lý nhiều
                    Merchant/Shop sau khi đăng nhập.</p>
                <?php if (!empty($errors['auth'])): ?><div class="alert alert-danger"><?= e($errors['auth']) ?></div>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/register')) ?>">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="form-group"><label for="full_name">Họ và tên</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i
                                        class="fa-solid fa-user"></i></span></div><input class="form-control"
                                id="full_name" name="full_name" placeholder="Nhập họ và tên" maxlength="120" required
                                value="<?= e((string)($old['full_name'] ?? '')) ?>">
                        </div><?php if (isset($errors['full_name'])): ?><small
                            class="text-danger"><?= e($errors['full_name']) ?></small><?php endif; ?>
                    </div>
                    <div class="form-group"><label for="email">Email</label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i
                                        class="fa-solid fa-envelope"></i></span></div><input class="form-control"
                                id="email" name="email" type="email" autocomplete="email"
                                placeholder="Nhập email của bạn" maxlength="191" required
                                value="<?= e((string)($old['email'] ?? '')) ?>">
                        </div><?php if (isset($errors['email'])): ?><small
                            class="text-danger"><?= e($errors['email']) ?></small><?php endif; ?>
                    </div>
                    <?php foreach (['password' => 'Mật khẩu', 'password_confirmation' => 'Xác nhận mật khẩu'] as $field => $label): ?>
                    <div class="form-group"><label for="<?= e($field) ?>"><?= e($label) ?></label>
                        <div class="input-group">
                            <div class="input-group-prepend"><span class="input-group-text"><i
                                        class="fa-solid fa-lock"></i></span></div><input class="form-control"
                                id="<?= e($field) ?>" name="<?= e($field) ?>" type="password" minlength="8"
                                autocomplete="new-password"
                                placeholder="<?= $field === 'password' ? 'Nhập mật khẩu (ít nhất 8 ký tự)' : 'Nhập lại mật khẩu' ?>"
                                required>
                            <div class="input-group-append"><button type="button" class="btn password-toggle"
                                    data-target="#<?= e($field) ?>" aria-label="Hiện mật khẩu" aria-pressed="false"><i
                                        class="fa-solid fa-eye password-eye-symbol"></i></button></div>
                        </div><?php if (isset($errors[$field])): ?><small
                            class="text-danger"><?= e($errors[$field]) ?></small><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <button class="btn btn-primary btn-block" type="submit">Tạo tài khoản User</button>
                </form>
                <p class="text-center mt-3 mb-0 small">Đã có tài khoản? <a href="<?= e(url('/login')) ?>">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</div>
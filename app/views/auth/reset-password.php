<?php

use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm auth-card">
            <div class="card-body p-4">
                <h1 class="h4 mb-3"><i class="fa-solid fa-lock text-primary mr-2" aria-hidden="true"></i>Đặt lại mật
                    khẩu</h1>
                <?php if (!$valid): ?>
                    <div class="alert alert-warning">Liên kết đặt lại mật khẩu không hợp lệ, đã hết hạn hoặc đã được sử
                        dụng.</div>
                    <a class="btn btn-primary btn-block" href="<?= e(url('/forgot-password')) ?>">Yêu cầu liên kết mới</a>
                <?php else: ?>
                    <p class="text-muted small">Hãy tạo mật khẩu mới có ít nhất 8 ký tự (tối đa 72 byte).</p>
                    <?php if (!empty($errors['reset'])): ?><div class="alert alert-danger"><?= e($errors['reset']) ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('/reset-password')) ?>">
                        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                        <input type="hidden" name="token" value="<?= e($token) ?>">
                        <div class="form-group">
                            <label for="new-password">Mật khẩu mới</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text auth-input-icon"><i
                                            class="fa-solid fa-lock" aria-hidden="true"></i></span></div>
                                <input id="new-password" name="password" type="password" class="form-control"
                                    placeholder="Nhập mật khẩu mới" autocomplete="new-password" minlength="8" required>
                                <div class="input-group-append"><button type="button" class="btn password-toggle"
                                        data-target="#new-password" aria-label="Hiện mật khẩu" aria-pressed="false"><i
                                            class="fa-solid fa-eye password-eye-symbol" aria-hidden="true"></i></button>
                                </div>
                            </div>
                            <?php if (!empty($errors['password'])): ?><div class="invalid-feedback d-block">
                                    <?= e($errors['password']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="confirm-new-password">Xác nhận mật khẩu</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text auth-input-icon"><i
                                            class="fa-solid fa-shield-halved" aria-hidden="true"></i></span></div>
                                <input id="confirm-new-password" name="password_confirmation" type="password"
                                    class="form-control" placeholder="Nhập lại mật khẩu mới" autocomplete="new-password"
                                    minlength="8" required>
                                <div class="input-group-append"><button type="button" class="btn password-toggle"
                                        data-target="#confirm-new-password" aria-label="Hiện mật khẩu"
                                        aria-pressed="false"><i class="fa-solid fa-eye password-eye-symbol"
                                            aria-hidden="true"></i></button></div>
                            </div>
                            <?php if (!empty($errors['password_confirmation'])): ?><div class="invalid-feedback d-block">
                                    <?= e($errors['password_confirmation']) ?></div><?php endif; ?>
                        </div>
                        <button class="btn btn-primary btn-block" type="submit">Lưu mật khẩu mới</button>
                    </form>
                <?php endif; ?>
                <div class="mt-3 text-center"><a href="<?= e(url('/login')) ?>">Quay lại đăng nhập</a></div>
            </div>
        </div>
    </div>
</div>
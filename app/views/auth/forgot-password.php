<?php

use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm auth-card">
            <div class="card-body p-4">
                <h1 class="h4 mb-2">Quên mật khẩu?
                </h1>
                <p class="text-muted small mb-4">Nhập email tài khoản CouponHub. Nếu email hợp lệ, chúng tôi sẽ gửi liên
                    kết đặt lại mật khẩu có hiệu lực trong 30 phút.</p>
                <form method="post" action="<?= e(url('/forgot-password')) ?>">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="mb-3">
                        <label for="reset-email">Email</label>
                        <div class="input-group">
                            <span class="input-group-text auth-input-icon"><i class="fa-solid fa-envelope"
                                    aria-hidden="true"></i></span>
                            <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                id="reset-email" name="email" maxlength="191" autocomplete="email"
                                placeholder="Nhập email của bạn" value="<?= e((string) ($old['email'] ?? '')) ?>"
                                required>
                        </div>
                        <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block">
                                <?= e($errors['email']) ?></div><?php endif; ?>
                    </div>
                    <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-paper-plane me-1"
                            aria-hidden="true"></i>Gửi liên kết đặt lại</button>
                </form>
                <div class="mt-3 text-center"><a href="<?= e(url('/login')) ?>"><i class="fa-solid fa-arrow-left me-1"
                            aria-hidden="true"></i>Quay lại đăng nhập</a></div>
            </div>
        </div>
    </div>
</div>
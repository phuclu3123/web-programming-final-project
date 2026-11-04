<?php

use App\Core\Session; ?>
<?php
$profileValues = $profileValues ?? [];
$profileErrors = $profileErrors ?? [];
$passwordErrors = $passwordErrors ?? [];
$profileFullName = is_string($profileValues['full_name'] ?? null) ? $profileValues['full_name'] : (string)$actor['full_name'];
$profilePhone = is_string($profileValues['phone'] ?? null) ? $profileValues['phone'] : (string)($actor['phone'] ?? '');
?>
<div class="row justify-content-center">
  <div class="col-lg-9">
    <nav aria-label="Breadcrumb" class="mb-3"><a href="<?= e(url('/dashboard')) ?>">Tài khoản</a> / Hồ sơ cá nhân
    </nav>
    <h1 class="h3 mb-4">Thông tin tài khoản</h1>
    <div class="row g-4">
      <section class="col-md-6" aria-labelledby="account-edit-title">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h2 class="h5" id="account-edit-title">Cập nhật hồ sơ</h2>
            <form method="post" action="<?= e(url('/account')) ?>" data-account-submit-form>
              <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
              <div class="mb-3"><label class="form-label" for="account-full-name">Họ và tên</label>
                <input class="form-control <?= isset($profileErrors['full_name']) ? 'is-invalid' : '' ?>" id="account-full-name" name="full_name" maxlength="120"
                  required value="<?= e($profileFullName) ?>" <?= isset($profileErrors['full_name']) ? 'aria-invalid="true" aria-describedby="account-full-name-error"' : '' ?>>
                <?php if (isset($profileErrors['full_name'])): ?><div class="invalid-feedback d-block" id="account-full-name-error"><?= e($profileErrors['full_name']) ?></div><?php endif; ?>
              </div>
              <div class="mb-3"><label class="form-label" for="account-phone">Số điện thoại</label>
                <input class="form-control <?= isset($profileErrors['phone']) ? 'is-invalid' : '' ?>" id="account-phone" name="phone" maxlength="20"
                  autocomplete="tel" required value="<?= e($profilePhone) ?>" <?= isset($profileErrors['phone']) ? 'aria-invalid="true" aria-describedby="account-phone-error"' : '' ?>>
                <?php if (isset($profileErrors['phone'])): ?><div class="invalid-feedback d-block" id="account-phone-error"><?= e($profileErrors['phone']) ?></div><?php endif; ?>
              </div>
              <div class="mb-3"><label class="form-label" for="account-email">Email (không thể đổi tại
                  đây)</label>
                <input class="form-control" id="account-email" type="email" readonly
                  value="<?= e((string)$actor['email']) ?>">
              </div>
              <button class="btn btn-primary" type="submit" data-account-submit-button data-pending-label="Đang lưu...">Lưu thông tin</button>
            </form>
          </div>
        </div>
      </section>
      <section class="col-md-6" aria-labelledby="account-password-title">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h2 class="h5" id="account-password-title">Đổi mật khẩu</h2>
            <p class="text-muted small">Sau khi đổi mật khẩu, các phiên đăng nhập cũ sẽ hết hiệu lực.</p>
            <form method="post" action="<?= e(url('/account/password')) ?>" data-account-submit-form>
              <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
              <div class="mb-3"><label class="form-label" for="account-current-password">Mật khẩu hiện
                  tại</label>
                <input class="form-control <?= isset($passwordErrors['current_password']) ? 'is-invalid' : '' ?>" id="account-current-password" name="current_password"
                  type="password" autocomplete="current-password" required <?= isset($passwordErrors['current_password']) ? 'aria-invalid="true" aria-describedby="account-current-password-error"' : '' ?>>
                <?php if (isset($passwordErrors['current_password'])): ?><div class="invalid-feedback d-block" id="account-current-password-error"><?= e($passwordErrors['current_password']) ?></div><?php endif; ?>
              </div>
              <div class="mb-3"><label class="form-label" for="account-new-password">Mật khẩu mới</label>
                <input class="form-control <?= isset($passwordErrors['new_password']) ? 'is-invalid' : '' ?>" id="account-new-password" name="new_password"
                  type="password" minlength="8" maxlength="72" autocomplete="new-password" required aria-describedby="account-new-password-help<?= isset($passwordErrors['new_password']) ? ' account-new-password-error' : '' ?>" <?= isset($passwordErrors['new_password']) ? 'aria-invalid="true"' : '' ?>>
                <div class="form-text" id="account-new-password-help">Từ 8 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt; tối đa 72 byte.</div>
                <?php if (isset($passwordErrors['new_password'])): ?><div class="invalid-feedback d-block" id="account-new-password-error"><?= e($passwordErrors['new_password']) ?></div><?php endif; ?>
              </div>
              <div class="mb-3"><label class="form-label" for="account-confirm-password">Xác nhận mật khẩu
                  mới</label>
                <input class="form-control <?= isset($passwordErrors['new_password_confirmation']) ? 'is-invalid' : '' ?>" id="account-confirm-password"
                  name="new_password_confirmation" type="password" minlength="8" maxlength="72"
                  autocomplete="new-password" required <?= isset($passwordErrors['new_password_confirmation']) ? 'aria-invalid="true" aria-describedby="account-confirm-password-error"' : '' ?>>
                <?php if (isset($passwordErrors['new_password_confirmation'])): ?><div class="invalid-feedback d-block" id="account-confirm-password-error"><?= e($passwordErrors['new_password_confirmation']) ?></div><?php endif; ?>
              </div>
              <button class="btn btn-outline-primary" type="submit" data-account-submit-button data-pending-label="Đang cập nhật...">Đổi mật khẩu</button>
            </form>
          </div>
        </div>
      </section>
    </div>
    <section class="card mt-4" aria-labelledby="linked-providers-title">
      <div class="card-body">
        <h2 class="h5" id="linked-providers-title">Tài khoản liên kết</h2>
        <p class="text-muted">Liên kết để đăng nhập nhanh. Tài khoản có cùng email chỉ được liên kết khi bạn chủ
          động xác nhận tại đây.</p>
        <div class="d-flex flex-wrap gap-3">
          <?php foreach (['google' => 'Google', 'github' => 'GitHub', 'facebook' => 'Facebook'] as $provider => $label):
            $ready = (new \App\Core\OAuthPreparation((array)config('oauth')))->status($provider)['ready']; ?>
            <?php if (in_array($provider, $oauthLinks ?? [], true)): ?><span
                class="badge bg-success align-self-center p-3"><?= e($label) ?> · Đã liên kết</span>
            <?php else: ?><form method="post" action="<?= e(url('/auth/oauth/' . $provider)) ?>">
                <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
                <input type="hidden" name="mode" value="link">
                <button class="btn btn-outline-primary" type="submit"
                  <?= $ready ? '' : 'disabled title="Chưa cấu hình nhà cung cấp"' ?>>Liên kết
                  <?= e($label) ?></button>
              </form><?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
  </div>
</div>

<?php use App\Core\Session; ?>
<div class="modal-header mx-3 mt-3 pe-5"><div>
    <p class="text-success mb-1">Khôi phục tài khoản</p>
    <h2 class="modal-title fs-2 mb-2" id="forgotTitle">Bạn quên mật khẩu?</h2>
    <p class="text-secondary mb-0">Nhập email tài khoản để nhận mã xác minh có hiệu lực trong 10 phút.</p>
</div></div>
<div class="modal-body mx-3">
    <form id="forgotPasswordForm" method="post" action="<?= e(url('/forgot-password')) ?>">
        <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
        <div class="auth-message mb-3" role="status" aria-live="polite" hidden></div>
        <label class="form-label" for="resetEmail">Email tài khoản</label>
        <input class="form-control mb-4" id="resetEmail" name="email" type="email" maxlength="191" autocomplete="email" required>
        <button type="submit" class="btn login-btn w-100 text-white fw-bold rounded-4">Gửi mã xác minh</button>
    </form>
    <button type="button" data-auth-target="login" class="btn btn-link mt-3 w-100">Quay lại đăng nhập</button>
</div>

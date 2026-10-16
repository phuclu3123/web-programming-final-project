<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Repositories\PasswordResetRepository;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use App\Services\SmtpMailService;

/** ---- HTTP AUTH: đọc Request, gọi AuthService rồi trả JSON/View/redirect ---- */
final class AuthController extends Controller
{
    public function showLogin(Request $request): void { $this->authPage($request, 'login'); }
    public function showRegister(Request $request): void
    {
        $this->authPage($request, 'register');
    }
    private function authPage(Request $request, string $authInitialView): void
    {
        $currentUser = Auth::user();
        if ($currentUser) Response::redirect(url(Auth::safeReturn($this->field($request, 'return'), $currentUser['role'])), 303);
        header('Cache-Control: no-store');
        $this->view('auth/page', compact('currentUser', 'authInitialView'), null);
    }
    private function authService(): AuthService { return new AuthService(new UserRepository(Database::connection())); }
    private function field(Request $request, string $key): string
    {
        $value = $request->input($key, '');
        if (!is_string($value) || preg_match('//u', $value) !== 1) throw new HttpError(400, 'Dữ liệu gửi lên không hợp lệ.');
        return $value;
    }
    public function login(Request $request): void
    {
        $account = $request->input('account', $request->input('email', ''));
        if (!is_string($account)) throw new HttpError(400, 'Tài khoản không hợp lệ.');
        $result = $this->authService()->login($account, $this->field($request, 'password'), $this->field($request, 'return_url'));
        if ($request->expectsJson()) Response::json($result, $result['success'] ? 200 : 422);
        if (!$result['success']) Session::flash('error', $result['message']);
        Response::redirect($result['success'] ? url($result['redirect']) : url('/login'), 303);
    }
    public function register(Request $request): void
    {
        $input = [];
        foreach (['full_name','username','phone','email','password','password_confirmation'] as $field) $input[$field] = $this->field($request, $field);
        $result = $this->authService()->register($input);
        if ($request->expectsJson()) Response::json($result, $result['success'] ? 201 : 422);
        Session::flash($result['success'] ? 'success' : 'error', $result['message']);
        Response::redirect(url($result['success'] ? '/login' : '/register'), 303);
    }
    public function logout(Request $request): void { Auth::logout(); Response::redirect(url('/'), 303); }

    // ---- QUÊN MẬT KHẨU TỪ QUANG: dùng cùng users/session và cấu hình SMTP ----
    private function resetService(): PasswordResetService
    {
        $db = Database::connection();
        return new PasswordResetService(new UserRepository($db), new PasswordResetRepository($db), new SmtpMailService());
    }
    private function resetHeaders(): void
    {
        header('Cache-Control: no-store, private'); header('Referrer-Policy: no-referrer'); header('X-Robots-Tag: noindex, nofollow');
    }
    public function showForgotPassword(Request $request): void
    {
        $this->resetHeaders();
        $this->view('auth/forgot-password', ['pageTitle'=>'Quên mật khẩu', 'errors'=>[], 'old'=>[]]);
    }
    public function forgotPassword(Request $request): void
    {
        $this->resetHeaders();
        $email = trim($this->field($request, 'email'));
        if (strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('auth/forgot-password', ['pageTitle'=>'Quên mật khẩu', 'errors'=>['email'=>'Email không hợp lệ.'], 'old'=>['email'=>$email]]);
            return;
        }
        if (time() - (int) Session::get('password_reset_last_request', 0) >= 60) {
            Session::put('password_reset_last_request', time());
            try { $this->resetService()->request($email, $_SERVER['REMOTE_ADDR'] ?? 'unknown', url('')); }
            catch (\Throwable $error) { error_log('Password reset request: ' . get_class($error)); }
        }
        Session::flash('success', 'Nếu email có tài khoản hợp lệ, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu.');
        Response::redirect(url('/forgot-password'), 303);
    }
    public function showResetPassword(Request $request): void
    {
        $this->resetHeaders(); $token = $this->field($request, 'token');
        $valid = $this->resetService()->isValid($token);
        $this->view('auth/reset-password', ['pageTitle'=>'Đặt lại mật khẩu', 'token'=>$valid ? $token : '', 'valid'=>$valid, 'errors'=>[]]);
    }
    public function resetPassword(Request $request): void
    {
        $this->resetHeaders(); $token = $this->field($request, 'token'); $password = $this->field($request, 'password');
        $errors = [];
        if (!hash_equals($password, $this->field($request, 'password_confirmation'))) $errors['password_confirmation'] = 'Mật khẩu xác nhận chưa khớp.';
        $service = $this->resetService();
        try {
            if (!$errors && $service->reset($token, $password)) {
                Auth::logout(); Session::start(); Session::flash('success', 'Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới.');
                Response::redirect(url('/login'), 303);
            }
        } catch (\RuntimeException $error) { $errors['password'] = $error->getMessage(); }
        if (!$errors) $errors['reset'] = 'Liên kết không hợp lệ hoặc đã hết hạn.';
        $valid = $service->isValid($token);
        $this->view('auth/reset-password', ['pageTitle'=>'Đặt lại mật khẩu', 'token'=>$valid ? $token : '', 'valid'=>$valid, 'errors'=>$errors]);
    }
}

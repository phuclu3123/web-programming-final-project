<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Repositories\UserRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\MerchantRepository;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use App\Services\SmtpMailService;
use App\Services\MerchantService;
use App\Services\RegistrationService;
use RuntimeException;
use Throwable;

final class AuthController extends Controller
{
    private AuthService $auth;
    private RegistrationService $registration;
    private MerchantRepository $merchants;
    private PasswordResetService $passwordResets;

    public function __construct()
    {
        $pdo = Database::connection();
        $this->auth = new AuthService(new UserRepository($pdo));
        $this->merchants = new MerchantRepository($pdo);
        $this->passwordResets = new PasswordResetService(new UserRepository($pdo), new PasswordResetRepository($pdo), new SmtpMailService());
        $this->registration = new RegistrationService(
            $pdo,
            $this->auth,
            new MerchantService($this->merchants)
        );
    }

    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect(url('/dashboard'));
        }

        $this->view('auth/login', [
            'pageTitle' => 'Đăng nhập',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function login(Request $request): void
    {
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }

        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        $validator = (new Validator())
            ->required('email', $email, 'Email')
            ->email('email', $email, 'Email')
            ->required('password', $password, 'Mật khẩu');

        if ($validator->fails()) {
            $this->view('auth/login', [
                'pageTitle' => 'Đăng nhập',
                'errors' => $validator->errors(),
                'old' => ['email' => $email],
            ]);
            return;
        }

        try {
            $this->auth->login($email, $password);
            Session::flash('success', 'Đăng nhập thành công.');
            Response::redirect(url('/dashboard'));
        } catch (RuntimeException $e) {
            $this->view('auth/login', [
                'pageTitle' => 'Đăng nhập',
                'errors' => ['auth' => $e->getMessage()],
                'old' => ['email' => $email],
            ]);
        }
    }

    public function showForgotPassword(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect(url('/dashboard'));
        }
        $this->setResetHeaders();
        $this->view('auth/forgot-password', [
            'pageTitle' => 'Quên mật khẩu', 'errors' => [], 'old' => []
        ]);
    }

    public function forgotPassword(Request $request): void
    {
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }
        $this->setResetHeaders();
        $email = trim((string) $request->input('email', ''));
        if (strlen($email) > 191 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('auth/forgot-password', [
                'pageTitle' => 'Quên mật khẩu',
                'errors' => ['email' => 'Email không hợp lệ.'], 'old' => ['email' => $email]
            ]);
            return;
        }
        // Per-session throttle complements DB per-IP and per-account limits.
        $now = time();
        $last = (int) Session::get('password_reset_last_request', 0);
        if ($now - $last >= 60) {
            Session::put('password_reset_last_request', $now);
            try {
                $this->passwordResets->request($email, (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), url(''));
            } catch (Throwable $e) {
                error_log('CouponHub password reset request failed: ' . get_class($e));
            }
        }
        // Same response whether address exists, is locked, rate-limited or mail fails.
        Session::flash('success', 'Nếu email có tài khoản hợp lệ, bạn sẽ nhận được hướng dẫn đặt lại mật khẩu. Hãy kiểm tra hộp thư.');
        Response::redirect(url('/forgot-password'));
    }

    public function showResetPassword(Request $request): void
    {
        $this->setResetHeaders();
        $token = (string) $request->input('token', '');
        try {
            $valid = $this->passwordResets->isValid($token);
        } catch (Throwable $e) {
            error_log('CouponHub reset token lookup failed: ' . get_class($e));
            $valid = false;
        }
        $this->view('auth/reset-password', [
            'pageTitle' => 'Đặt lại mật khẩu', 'valid' => $valid,
            'token' => $valid ? $token : '', 'errors' => []
        ]);
    }

    public function resetPassword(Request $request): void
    {
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }
        $this->setResetHeaders();
        $token = (string) $request->input('token', '');
        $password = (string) $request->input('password', '');
        $confirmation = (string) $request->input('password_confirmation', '');
        $errors = [];
        if (strlen($password) < 8 || strlen($password) > 72) {
            $errors['password'] = 'Mật khẩu phải có từ 8 đến 72 byte.';
        }
        if (!hash_equals($password, $confirmation)) {
            $errors['password_confirmation'] = 'Mật khẩu xác nhận chưa khớp.';
        }
        if ($errors === []) {
            try {
                if ($this->passwordResets->reset($token, $password)) {
                    Auth::logout();
                    Session::flash('success', 'Đặt lại mật khẩu thành công. Hãy đăng nhập bằng mật khẩu mới.');
                    Response::redirect(url('/login'));
                }
            } catch (Throwable $e) {
                error_log('CouponHub password reset failed: ' . get_class($e));
                $errors['reset'] = 'Không thể đặt lại mật khẩu lúc này. Vui lòng thử lại.';
            }
        }
        try {
            $valid = $this->passwordResets->isValid($token);
        } catch (Throwable $e) {
            $valid = false;
        }
        $this->view('auth/reset-password', [
            'pageTitle' => 'Đặt lại mật khẩu', 'valid' => $valid,
            'token' => $valid ? $token : '', 'errors' => $errors
        ]);
    }

    private function setResetHeaders(): void
    {
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow');
    }

    public function showRegister(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect(url('/dashboard'));
        }

        $this->view('auth/register', [
            'pageTitle' => 'Đăng ký tài khoản',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function register(Request $request): void
    {
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }

        $fullName = trim((string) $request->input('full_name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');
        $confirmation = (string) $request->input('password_confirmation', '');
        $old = ['full_name' => $fullName, 'email' => $email];
        $validator = (new Validator())
            ->required('full_name', $fullName, 'Họ tên')
            ->maxLength('full_name', $fullName, 120, 'Họ tên')
            ->required('email', $email, 'Email')
            ->email('email', $email, 'Email')
            ->maxLength('email', $email, 191, 'Email')
            ->required('password', $password, 'Mật khẩu')
            ->minLength('password', $password, 8, 'Mật khẩu')
            ->same('password_confirmation', $confirmation, $password, 'Xác nhận mật khẩu');

        $errors = $validator->errors();
        if ($errors !== []) {
            $this->renderRegistrationErrors($errors, $old);
            return;
        }

        try {
            $this->registration->register([
                'full_name'=>$fullName, 'email'=>$email, 'password'=>$password, 'account_type'=>'user'
            ]);
            Session::flash('success', 'Tạo tài khoản thành công. Vui lòng đăng nhập.');
            Response::redirect(url('/register'));
        } catch (RuntimeException $e) {
            $this->renderRegistrationErrors(['auth' => $e->getMessage()], $old);
        } catch (Throwable $e) {
            error_log('CouponHub registration failed: ' . get_class($e));
            $this->renderRegistrationErrors(['auth' => 'Không thể tạo tài khoản. Vui lòng thử lại.'], $old);
        }
    }

    /** @param array<string,string> $errors
     *  @param array<string,mixed> $old
     */
    private function renderRegistrationErrors(array $errors, array $old): void
    {
        $this->view('auth/register', [
            'pageTitle' => 'Đăng ký tài khoản',
            'errors' => $errors,
            'old' => $old,
        ]);
    }

    public function logout(Request $request): void
    {
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }

        $this->auth->logout();
        Session::flash('success', 'Đã đăng xuất.');
        Response::redirect(url('/'));
    }
}

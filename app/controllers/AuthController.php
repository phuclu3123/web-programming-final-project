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
use App\Services\AuthService;
use RuntimeException;

final class AuthController extends Controller
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService(new UserRepository(Database::connection()));
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

    public function showRegister(Request $request): void
    {
        if (Auth::check()) {
            Response::redirect(url('/dashboard'));
        }

        $this->view('auth/register', [
            'pageTitle' => 'Đăng ký',
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

        $validator = (new Validator())
            ->required('full_name', $fullName, 'Họ tên')
            ->maxLength('full_name', $fullName, 120, 'Họ tên')
            ->required('email', $email, 'Email')
            ->email('email', $email, 'Email')
            ->maxLength('email', $email, 191, 'Email')
            ->required('password', $password, 'Mật khẩu')
            ->minLength('password', $password, 8, 'Mật khẩu')
            ->same('password_confirmation', $confirmation, $password, 'Xác nhận mật khẩu');

        if ($validator->fails()) {
            $this->view('auth/register', [
                'pageTitle' => 'Đăng ký',
                'errors' => $validator->errors(),
                'old' => ['full_name' => $fullName, 'email' => $email],
            ]);
            return;
        }

        try {
            $this->auth->register($fullName, $email, $password);
            Session::flash('success', 'Tạo tài khoản thành công.');
            Response::redirect(url('/register'));
        } catch (RuntimeException $e) {
            $this->view('auth/register', [
                'pageTitle' => 'Đăng ký',
                'errors' => ['auth' => $e->getMessage()],
                'old' => ['full_name' => $fullName, 'email' => $email],
            ]);
        }
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

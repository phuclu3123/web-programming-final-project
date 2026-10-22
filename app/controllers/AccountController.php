<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\UserRepository;
use App\Services\AccountService;
use RuntimeException;

final class AccountController extends Controller
{
    public function index(Request $request): void
    {
        $actor = Auth::requireAuth();
        $profile = (new UserRepository(Database::connection()))->findById((int)$actor['id']);
        if ($profile === null || $profile['status'] !== 'ACTIVE') {
            throw new \App\Core\HttpError(401, 'Tài khoản không còn hoạt động.');
        }
        $this->view('account/profile', [
            'pageTitle' => 'Hồ sơ cá nhân', 'actor' => array_replace($profile, $actor),
            'oauthLinks' => (new \App\Repositories\OAuthRepository(Database::connection()))->linkedProviders((int)$actor['id']),
        ]);
    }

    public function update(Request $request): void
    {
        $actor = Auth::requireAuth();
        // Global router verifies CSRF before any POST controller is instantiated.
        try {
            (new AccountService(new UserRepository(Database::connection())))->updateProfile(
                (int)$actor['id'], $request->input('full_name'), $request->input('phone')
            );
            Session::flash('success', 'Đã cập nhật hồ sơ cá nhân.');
        } catch (RuntimeException $e) {
            if ($e instanceof \mysqli_sql_exception) error_log((string)$e);
            Session::flash('error', $e instanceof \mysqli_sql_exception ? 'Không thể cập nhật hồ sơ lúc này.' : $e->getMessage());
        }
        Response::redirect(url('/account'), 303);
    }

    public function changePassword(Request $request): void
    {
        $actor = Auth::requireAuth();
        try {
            (new AccountService(new UserRepository(Database::connection())))->changePassword(
                (int)$actor['id'], $request->input('current_password'),
                $request->input('new_password'), $request->input('new_password_confirmation')
            );
            // Changing the password increments auth_version: all old sessions are invalid.
            Auth::logout();
            Session::start();
            Session::flash('success', 'Đổi mật khẩu thành công. Vui lòng đăng nhập lại.');
            Response::redirect(url('/login'), 303);
        } catch (RuntimeException $e) {
            if ($e instanceof \mysqli_sql_exception) error_log((string)$e);
            Session::flash('error', $e instanceof \mysqli_sql_exception ? 'Không thể đổi mật khẩu lúc này.' : $e->getMessage());
            Response::redirect(url('/account'), 303);
        }
    }
}

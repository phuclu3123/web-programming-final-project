<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        $user = Session::get('auth_user');
        return is_array($user) ? $user : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function role(): ?string
    {
        $user = self::user();
        return $user ? (string) $user['role'] : null;
    }

    /** @param array<string,mixed> $user */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('auth_user', [
            'id' => (int) $user['id'],
            'email' => (string) $user['email'],
            'full_name' => (string) $user['full_name'],
            'role' => (string) $user['role'],
            'status' => (string) $user['status'],
        ]);
    }

    public static function logout(): void
    {
        Session::forget('auth_user');
        Session::regenerate();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            Session::flash('error', 'Bạn cần đăng nhập để tiếp tục.');
            Response::redirect(url('/login'));
        }
    }

    /** @param string[] $roles */
    public static function requireRole(array $roles): void
    {
        self::requireAuth();
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            $controller = new class extends Controller {};
            $controller->renderError(403, 'Bạn không có quyền truy cập chức năng này.');
            exit;
        }
    }
}

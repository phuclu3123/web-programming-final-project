<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class Auth
{
    private static bool $synced = false;

    /** @return array<string,mixed>|null */
    public static function user(): ?array
    {
        $user = Session::get('auth_user');
        if (!is_array($user)) {
            return null;
        }

        // Refresh role/status once per request so Admin changes (lock user,
        // approve merchant -> MERCHANT role) are reflected without stale sessions.
        if (!self::$synced) {
            self::$synced = true;
            try {
                $stmt = Database::connection()->prepare(
                    'SELECT id,email,full_name,role,status FROM users WHERE id=? AND deleted_at IS NULL LIMIT 1'
                );
                $stmt->execute([(int) ($user['id'] ?? 0)]);
                $fresh = $stmt->fetch();
                if (!is_array($fresh) || (string) $fresh['status'] !== 'ACTIVE') {
                    Session::forget('auth_user');
                    return null;
                }
                $user = [
                    'id' => (int) $fresh['id'],
                    'email' => (string) $fresh['email'],
                    'full_name' => (string) $fresh['full_name'],
                    'role' => (string) $fresh['role'],
                    'status' => (string) $fresh['status'],
                ];
                Session::put('auth_user', $user);
            } catch (Throwable $e) {
                // Do not make the whole public site fail solely because a session
                // refresh query could not run. Protected actions still hit DB later.
            }
        }

        return $user;
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
        self::$synced = true;
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
        self::$synced = false;
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

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
                    'SELECT id,email,full_name,role,status,auth_version FROM users WHERE id=? AND deleted_at IS NULL LIMIT 1'
                );
                $stmt->execute([(int) ($user['id'] ?? 0)]);
                $fresh = $stmt->fetch();
                if (
                    !is_array($fresh) || (string) $fresh['status'] !== 'ACTIVE'
                    || (int) ($user['auth_version'] ?? 0) !== (int) $fresh['auth_version']
                ) {
                    Session::forget('auth_user');
                    return null;
                }
                if ((string)$fresh['role'] !== 'ADMIN') {
                    $membership = Database::connection()->prepare("SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id WHERE mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL LIMIT 1");
                    $membership->execute([(int)$fresh['id']]);
                    $fresh['role'] = $membership->fetchColumn() ? 'MERCHANT' : 'USER';
                }
                $user = [
                    'id' => (int) $fresh['id'],
                    'email' => (string) $fresh['email'],
                    'full_name' => (string) $fresh['full_name'],
                    'role' => (string) $fresh['role'],
                    'status' => (string) $fresh['status'],
                    'auth_version' => (int) $fresh['auth_version'],
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
            'auth_version' => (int) ($user['auth_version'] ?? 0),
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

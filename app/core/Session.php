<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;


final class Session
{
    // ---- SESSION DÙNG CHUNG: login/API/trang HTML dùng cùng cấu hình ----
    private const IDLE_SECONDS = 1800; // 30 phút không thao tác.
    private const MAX_SECONDS = 28800; // Tối đa 8 giờ cho một phiên đăng nhập.
    private const AUTH_SCHEMA = 'quang-integration-20261016';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name('COUPONHUBSESSID');
        if (!session_start([
            'cookie_httponly' => true, 'cookie_samesite' => 'Lax',
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'use_strict_mode' => true, 'cookie_lifetime' => 0,
        ])) throw new RuntimeException('Không thể mở session.');
    }

    public static function establish(array $user): void
    {
        self::start();
        if (!session_regenerate_id(true)) throw new RuntimeException('Không thể đổi ID session.');
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['auth_version'] = (int) ($user['auth_version'] ?? 0);
        $_SESSION['auth_schema'] = self::AUTH_SCHEMA;
        $_SESSION['authenticated_at'] = $_SESSION['last_activity'] = time();
        unset($_SESSION['csrf'], $_SESSION['oauth_pending']);
    }

    public static function userId(): int
    {
        self::start();
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id <= 0) return 0;
        // ID trong database cũ có thể khác ID của dump Quang: bắt buộc đăng nhập lại.
        if (($_SESSION['auth_schema'] ?? '') !== self::AUTH_SCHEMA) { self::clearAuthentication(); return 0; }
        $now = time();
        if ($now - (int) ($_SESSION['last_activity'] ?? 0) > self::IDLE_SECONDS
            || $now - (int) ($_SESSION['authenticated_at'] ?? 0) > self::MAX_SECONDS) {
            self::clearAuthentication();
            return 0;
        }
        $_SESSION['last_activity'] = $now;
        return $id;
    }

    public static function clearAuthentication(): void
    {
        foreach (['user_id', 'user_role', 'user_name', 'auth_version', 'auth_schema', 'authenticated_at', 'last_activity', 'csrf', 'oauth_pending'] as $key) {
            unset($_SESSION[$key]);
        }
    }

    // ---- ĐĂNG XUẤT: xóa dữ liệu server và cookie ID phiên ----
    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600, 'path' => $params['path'], 'domain' => $params['domain'],
                'secure' => $params['secure'], 'httponly' => true, 'samesite' => 'Lax',
            ]);
        }
        if (!session_destroy()) throw new RuntimeException('Không thể kết thúc session.');
    }

    public static function token(): string
    {
        self::start();
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function checkCsrf(string $token): void
    {
        if ($token === '' || !hash_equals(self::token(), $token)) {
            throw new HttpError(403, 'Phiên gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.');
        }
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }
        $value = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $value;
    }

    // ---- DỮ LIỆU PHIÊN VÀ CSRF: dùng chung cho mọi module ----
    public static function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public static function put(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public static function forget(string $key): void { unset($_SESSION[$key]); }
    public static function regenerate(): void { if (!session_regenerate_id(true)) throw new RuntimeException('Không thể đổi ID session.'); }
    public static function csrfToken(): string { return self::token(); }
    public static function verifyCsrf(?string $token): bool { return is_string($token) && $token !== '' && hash_equals(self::token(), $token); }
}

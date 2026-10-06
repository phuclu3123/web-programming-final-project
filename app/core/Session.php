<?php
declare(strict_types=1);

final class Session
{
    public static function localDemo(array $config): bool
    {
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';
        $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
        return ($config['local_demo'] ?? false)
            && in_array($remote, ['127.0.0.1', '::1'], true)
            && in_array($host, ['127.0.0.1', 'localhost'], true)
            && !isset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    public static function actor(Database $db, array $config, string $role): array
    {
        $demo = self::localDemo($config);
        $id = $demo ? 0 : (int) ($_SESSION['user_id'] ?? 0);
        if (!$demo && $id <= 0) {
            throw new HttpError(401, 'Bạn cần đăng nhập để truy cập trang này.');
        }
        $actor = $db->call('ch_actor', [$id, $role, $demo ? 1 : 0])[0][0] ?? null;
        if (!$actor) {
            throw new HttpError(403, 'Tài khoản không có quyền truy cập.');
        }
        return $actor;
    }

    public static function token(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function checkCsrf(string $token): void
    {
        if (!hash_equals(self::token(), $token)) {
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
}

<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\RoutineGateway;

/** Xác thực/kiểm tra quyền; Session giữ dữ liệu phiên, repository xử lý dữ liệu nghiệp vụ. */
final class Auth
{
    /** Đọc quyền/trạng thái từ DB; không tin role client hoặc session cũ. */
    public static function user(?RoutineGateway $db = null): ?array
    {
        $id = Session::userId();
        if ($id <= 0) return null;
        $db ??= Database::connection();
        $user = $db->call('ch_auth_user', [$id])[0][0] ?? null;
        if (
            !$user || (int) $user['id'] !== $id || $user['status'] !== 'ACTIVE'
            || $user['deleted_at'] !== null || !in_array($user['role'], ['USER', 'MERCHANT', 'ADMIN'], true)
            || (int) ($user['auth_version'] ?? 0) !== (int) ($_SESSION['auth_version'] ?? 0)
        ) {
            Session::clearAuthentication();
            return null;
        }
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        return $user;
    }

    // ---- ĐIỀU HƯỚNG: chỉ nhận trang nội bộ đã biết, không open redirect ----
    public static function home(string $role): string
    {
        return match ($role) {
            'ADMIN' => '/admin',
            'MERCHANT' => '/merchant',
            default => 'index.php'
        };
    }

    public static function safeReturn(string $url, string $role): string
    {
        $fallback = self::home($role);
        if ($url === '' || strlen($url) > 2000 || preg_match('/[\x00-\x20\\\\]/', $url)) return $fallback;
        $parts = parse_url($url);
        if (!$parts || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user'])) return $fallback;
        // Return app-relative paths; the HTTP controller adds the deployment base once.
        $path = $parts['path'] ?? '';
        $base = parse_url((string) config('app.base_url', ''), PHP_URL_PATH) ?: '';
        if ($base !== '' && str_starts_with($path, rtrim($base, '/') . '/')) $path = substr($path, strlen(rtrim($base, '/')));
        $suffix = isset($parts['query']) ? '?' . $parts['query'] : '';
        $suffix .= isset($parts['fragment']) ? '#' . $parts['fragment'] : '';
        if (in_array($path, ['index.php', '/index.php', '/'], true)) return 'index.php' . $suffix;
        // Old bookmarks/return parameters must lead to the same current centers.
        if (in_array($path, ['admin.php', '/admin.php'], true)) {
            if ($role !== 'ADMIN') return $fallback;
            parse_str($parts['query'] ?? '', $query);
            $section = $query['section'] ?? 'dashboard';
            $sections = ['users', 'merchants', 'merchant-requests', 'categories', 'coupons', 'campaigns', 'reports', 'articles'];
            $target = is_string($section) && in_array($section, $sections, true) ? '/admin/' . $section : '/admin';
            unset($query['section']);
            return $target . ($query ? '?' . http_build_query($query) : '') . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
        }
        if (in_array($path, ['shop.php', '/shop.php'], true)) return $role === 'MERCHANT' ? '/merchant' . $suffix : $fallback;
        if (!str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '%')) return $fallback;
        if ($path === '/rewards' && in_array($role, ['USER', 'MERCHANT'], true)) return $path . $suffix;
        if (preg_match('~\A/deal-requests(?:/create|/[0-9]+)?\z~', $path)) return $path . $suffix;
        $public = preg_match('~\A/(?:notifications|dashboard|account|my-coupons|saved-coupons|categories|coupons(?:/[0-9]+|/create)?|campaigns(?:/[0-9]+)?|articles(?:/[A-Za-z0-9_-]+)?|portal|campaign-calendar|about|terms|privacy|community-guidelines|contact)\z~', $path);
        $merchant = preg_match('~\A/merchant(?:/apply|/coupons(?:/create|/[0-9]+/edit)?|/campaigns(?:/create|/[0-9]+(?:/edit)?)?|/profile/[0-9]+)?\z~', $path);
        $admin = preg_match('~\A/admin(?:/(?:users|merchants|merchant-requests|rewards|categories|coupons(?:/[0-9]+/edit)?|campaigns(?:/[0-9]+)?|reports|articles(?:/create|/[0-9]+/edit)?))?\z~', $path);
        return $public || ($merchant && ($role === 'MERCHANT' || $path === '/merchant/apply')) || ($admin && $role === 'ADMIN') ? $path . $suffix : $fallback;
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
        return self::user()['role'] ?? null;
    }
    public static function login(array $user): void
    {
        Session::establish($user);
    }
    public static function logout(): void
    {
        Session::logout();
    }

    public static function requireAuth(): array
    {
        $user = self::user();
        if (!$user) throw new HttpError(401, 'Bạn cần đăng nhập để tiếp tục.');
        return $user;
    }

    /** Trả tài khoản đã kiểm tra để controller không phải đọc lại quyền lần nữa. */
    public static function requireRole(array $roles, ?RoutineGateway $db = null): array
    {
        $user = self::user($db);
        if (!$user) throw new HttpError(401, 'Bạn cần đăng nhập để tiếp tục.');
        if (!in_array($user['role'], $roles, true)) throw new HttpError(403, 'Bạn không có quyền truy cập chức năng này.');
        return $user;
    }

    /** Owner lấy từ routine, không lấy từ hidden input do người dùng gửi. */
    public static function requireMerchantOwner(RoutineGateway $db, array $actor, int $merchantId): void
    {
        if ($actor['role'] !== 'MERCHANT') throw new HttpError(403, 'Không có quyền thao tác cửa hàng.');
        $owned = $db->call('ch_owned_merchant', [$merchantId, (int) $actor['id']])[0][0] ?? null;
        if (
            !$owned || (int) $owned['id'] !== $merchantId
            || (int) ($owned['manager_user_id'] ?? $owned['owner_user_id']) !== (int) $actor['id']
        ) {
            throw new HttpError(403, 'Bạn không sở hữu cửa hàng này.');
        }
    }
}

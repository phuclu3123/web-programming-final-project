<?php

declare(strict_types=1);

use App\Core\App;

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        return App::config($key, $default);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim((string) config('app.base_url', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $base . ($path === '/' ? '' : $path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('/' . ltrim($path, '/'));
    }
}

if (!function_exists('format_money')) {
    function format_money($value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        return number_format((float) $value, 0, ',', '.') . ' ₫';
    }
}

if (!function_exists('format_discount')) {
    function format_discount(array $coupon): string
    {
        $type = (string) ($coupon['discount_type'] ?? '');
        $value = $coupon['discount_value'] ?? null;

        return match ($type) {
            'PERCENTAGE' => $value !== null ? 'Giảm ' . rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') . '%' : 'Giảm theo %',
            'FIXED_AMOUNT' => $value !== null ? 'Giảm ' . format_money($value) : 'Giảm tiền',
            'FREE_SHIPPING' => 'Miễn phí vận chuyển',
            'BUY_X_GET_Y' => 'Mua X tặng Y',
            'GIFT' => 'Quà tặng',
            'CASHBACK' => !array_key_exists('cashback_unit', $coupon)
                ? 'Hoàn tiền'
                : ($value !== null
                    ? (($coupon['cashback_unit'] ?? '') === 'PERCENTAGE'
                        ? 'Hoàn ' . rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.') . '%'
                        : 'Hoàn ' . format_money($value))
                    : 'Hoàn tiền'),
            default => 'Ưu đãi',
        };
    }
}

if (!function_exists('format_db_datetime')) {
    function format_db_datetime(?string $value, string $format = 'd/m/Y H:i'): string
    {
        if ($value === null || trim($value) === '') {
            return '—';
        }

        try {
            $utc = new DateTimeZone('UTC');
            $target = new DateTimeZone((string) config('app.timezone', 'Asia/Ho_Chi_Minh'));
            return (new DateTimeImmutable($value, $utc))->setTimezone($target)->format($format);
        } catch (Throwable $e) {
            return $value;
        }
    }
}

if (!function_exists('coupon_status_label')) {
    function coupon_status_label(string $status): string
    {
        return match ($status) {
            'ACTIVE' => 'Đang hoạt động',
            'SCHEDULED' => 'Sắp diễn ra',
            'PENDING' => 'Chờ duyệt',
            'REJECTED' => 'Bị từ chối',
            'EXPIRED' => 'Đã hết hạn',
            'DISABLED' => 'Đã tắt',
            'ARCHIVED' => 'Đã lưu trữ',
            default => $status,
        };
    }
}

if (!function_exists('coupon_status_class')) {
    function coupon_status_class(string $status): string
    {
        return match ($status) {
            'ACTIVE' => 'success',
            'SCHEDULED' => 'info',
            'PENDING' => 'warning',
            'REJECTED', 'DISABLED' => 'danger',
            'EXPIRED', 'ARCHIVED' => 'secondary',
            default => 'secondary',
        };
    }
}

if (!function_exists('category_badge_class')) {
    function category_badge_class(?string $categoryName): string
    {
        $name = mb_strtolower(trim((string) $categoryName));
        return match ($name) {
            'mua sắm' => 'badge-category-shopping',
            'ăn uống' => 'badge-category-food',
            'du lịch' => 'badge-category-travel',
            'công nghệ' => 'badge-category-tech',
            'giải trí' => 'badge-category-entertainment',
            'làm đẹp' => 'badge-category-beauty',
            'dịch vụ' => 'badge-category-service',
            'thời trang' => 'badge-category-fashion',
            default => 'badge-category-default',
        };
    }
}

if (!function_exists('usage_channel_label')) {
    function usage_channel_label(?string $channel): string
    {
        return match (strtoupper(trim((string) $channel))) {
            'ONLINE' => 'Online',
            'OFFLINE' => 'Offline',
            'BOTH' => 'Online + Offline',
            default => 'Không rõ',
        };
    }
}

if (!function_exists('usage_badge_class')) {
    function usage_badge_class(?string $channel): string
    {
        return match (strtoupper(trim((string) $channel))) {
            'ONLINE' => 'badge-usage-online',
            'OFFLINE' => 'badge-usage-offline',
            'BOTH' => 'badge-usage-both',
            default => 'badge-usage-default',
        };
    }
}

if (!function_exists('vote_label')) {
    function vote_label(?string $vote): string
    {
        return match (strtoupper(trim((string) $vote))) {
            'WORKING' => 'Dùng được',
            'NOT_WORKING' => 'Không dùng được',
            'CONDITION_CHANGED' => 'Điều kiện thay đổi',
            default => 'Chưa xác nhận',
        };
    }
}

if (!function_exists('report_reason_label')) {
    function report_reason_label(?string $reason): string
    {
        return match (strtoupper(trim((string) $reason))) {
            'EXPIRED' => 'Coupon đã hết hạn',
            'NOT_WORKING' => 'Coupon không hoạt động',
            'WRONG_TERMS' => 'Sai điều kiện sử dụng',
            'DUPLICATE' => 'Coupon bị trùng',
            'SPAM' => 'Spam',
            'FAKE' => 'Thông tin giả',
            'INAPPROPRIATE' => 'Nội dung không phù hợp',
            'OTHER' => 'Khác',
            default => (string) $reason,
        };
    }
}

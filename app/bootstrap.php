<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Ho_Chi_Minh');
ini_set('display_errors', '0');

final class HttpError extends RuntimeException
{
    public function __construct(public int $status, string $message) { parent::__construct($message); }
}

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/contracts/CouponRepositoryInterface.php';
require_once __DIR__ . '/core/Request.php';
require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/AbstractController.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/models/Coupon.php';
require_once __DIR__ . '/controllers/HomeController.php';
require_once __DIR__ . '/controllers/ShopController.php';
require_once __DIR__ . '/controllers/AdminController.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(mixed $value): string
{
    return number_format((float) $value, 0, ',', '.') . 'đ';
}

function textLength(string $value): int
{
    if (function_exists('mb_strlen')) return mb_strlen($value);
    if (function_exists('iconv_strlen')) return (int) iconv_strlen($value, 'UTF-8');
    return strlen($value);
}

function textPrefix(string $value, int $length = 2): string
{
    if (function_exists('mb_substr')) return mb_substr($value, 0, $length);
    if (function_exists('iconv_substr')) return (string) iconv_substr($value, 0, $length, 'UTF-8');
    return substr($value, 0, $length);
}

function day(mixed $value): string
{
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? '—' : date('d/m/Y', $timestamp);
}

function dateTimeVi(mixed $value): string
{
    $timestamp = strtotime((string) $value);
    return $timestamp === false ? '—' : date('H:i · d/m/Y', $timestamp);
}

function daysLeft(mixed $value): int
{
    $now = new DateTimeImmutable('today');
    $end = new DateTimeImmutable(date('Y-m-d', strtotime((string) $value)));
    return (int) $now->diff($end)->format('%r%a');
}

function expiryLabel(mixed $value): string
{
    $days = daysLeft($value);
    return match (true) {
        $days < 0 => 'Đã hết hạn',
        $days === 0 => 'Hết hạn hôm nay',
        $days === 1 => 'Còn 1 ngày',
        $days <= 7 => 'Còn ' . $days . ' ngày',
        default => 'Đến ' . day($value),
    };
}

function saving(array $coupon): string
{
    return match ($coupon['discount_type']) {
        'PERCENTAGE' => rtrim(rtrim(number_format((float) $coupon['discount_value'], 2, ',', '.'), '0'), ',') . '%',
        'FIXED_AMOUNT' => money($coupon['discount_value']),
        'FREE_SHIPPING' => 'Miễn phí ship',
        'BUY_X_GET_Y' => 'Mua kèm ưu đãi',
        'GIFT' => 'Quà tặng',
        'CASHBACK' => 'Hoàn tiền',
        default => 'Ưu đãi',
    };
}

function icon(string $name): string
{
    return '<svg class="icon" aria-hidden="true"><use href="assets/icons.svg#i-' . e($name) . '"/></svg>';
}

function statusLabel(string $status): string
{
    return [
        'PENDING' => 'Chờ duyệt',
        'APPROVED' => 'Đã duyệt',
        'REJECTED' => 'Từ chối',
        'ENABLED' => 'Đang bật',
        'DISABLED' => 'Đã tắt',
        'EXPIRED' => 'Hết hạn',
    ][$status] ?? $status;
}

function channelLabel(string $channel): string
{
    return [
        'ONLINE' => 'Online',
        'OFFLINE' => 'Tại cửa hàng',
        'BOTH' => 'Online & tại cửa hàng',
    ][$channel] ?? $channel;
}

function sourceLabel(string $source): string
{
    return [
        'OFFICIAL' => 'Coupon chính thức từ cửa hàng',
        'COMMUNITY' => 'Cộng đồng đóng góp',
    ][$source] ?? $source;
}

function positiveId(string $value, bool $allowZero = false): int
{
    if (!ctype_digit($value) || strlen($value) > 15 || (int) $value < ($allowZero ? 0 : 1)) {
        throw new HttpError(422, 'ID không hợp lệ.');
    }
    return (int) $value;
}

function runPage(string $controllerClass): void
{
    $viewEngine = new View(__DIR__ . '/views');
    try {
        if (session_status() !== PHP_SESSION_ACTIVE && !session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'use_strict_mode' => true,
        ])) {
            throw new HttpError(503, 'PHP không lưu được session. Kiểm tra quyền ghi thư mục session.save_path trong php.ini.');
        }

        $config = require __DIR__ . '/../config/database.php';
        $db = new Database($config);
        if (!is_subclass_of($controllerClass, AbstractController::class)) {
            throw new LogicException('Controller không hợp lệ.');
        }
        (new $controllerClass($db, $config, Request::fromGlobals(), $viewEngine))->handle();
    } catch (Throwable $error) {
        $business = $error instanceof mysqli_sql_exception && $error->getCode() === 1644;
        $status = $error instanceof HttpError ? $error->status : ($business ? 422 : 503);
        http_response_code($status);
        if (!$business && !($error instanceof HttpError)) error_log((string) $error);
        $message = $error instanceof HttpError || $business
            ? $error->getMessage()
            : 'Chưa kết nối được dữ liệu. Kiểm tra MySQL, config/database.php và import routines theo README.';
        $viewEngine->render('error', compact('message', 'status'));
    }
}

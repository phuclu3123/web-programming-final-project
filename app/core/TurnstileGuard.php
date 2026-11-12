<?php
declare(strict_types=1);
namespace App\Core;

// Ánh xạ từng biểu mẫu sang action Turnstile trước khi controller xử lý dữ liệu.
final class TurnstileGuard
{
    public static function check(Request $request): void
    {
        $action = match ($request->path()) {
            '/login', '/api/login.php' => 'login',
            '/register', '/api/register.php' => 'register',
            '/forgot-password' => 'reset_request',
            '/coupons' => 'community_coupon',
            '/merchant/apply' => 'merchant_application',
            default => null,
        };
        if ($action === null) return;
        (new \App\Services\TurnstileService(new HttpClient(), (array)config('integrations.turnstile', [])))
            ->verify($request->input('cf-turnstile-response'), $action, $_SERVER['REMOTE_ADDR'] ?? null);
    }
}

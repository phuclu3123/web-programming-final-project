<?php
declare(strict_types=1);
namespace App\Services;

final class AuthMailFactory
{
    public static function create(): \App\Contracts\AuthMailerInterface
    {
        if (config('mail.driver') === 'smtp') return new SmtpMailService();
        if (config('mail.driver') === 'resend') {
            if (config('mail.resend_api_key','')==='' || config('mail.resend_from','')==='') throw new \App\Core\HttpError(503,'Chưa cấu hình Resend. Vui lòng liên hệ quản trị viên.');
            return new ResendMailService(new \App\Core\HttpClient(), (array)config('mail'));
        }
        throw new \RuntimeException('Nhà cung cấp email không hợp lệ.');
    }
}

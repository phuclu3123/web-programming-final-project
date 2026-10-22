<?php
declare(strict_types=1);
namespace App\Services;

use App\Contracts\{AuthMailerInterface, HttpClientInterface};

final class ResendMailService implements AuthMailerInterface
{
    public function __construct(private HttpClientInterface $http, private array $config) {}

    public function sendPasswordReset(string $recipient, string $resetUrl): void
    {
        $this->send($recipient, 'Đặt lại mật khẩu CouponHub', "Mở liên kết để đặt mật khẩu mới (30 phút, chỉ dùng một lần):\n" . $resetUrl);
    }

    public function sendOtp(string $recipient, string $code): void
    {
        $this->send($recipient, 'Mã khôi phục mật khẩu CouponHub', "Mã xác minh của bạn: " . $code . "\nMã có hiệu lực 10 phút, chỉ sử dụng một lần. Không cung cấp mã cho bất kỳ ai. Nếu không yêu cầu, hãy bỏ qua email này.");
    }

    private function send(string $to, string $subject, string $text): void
    {
        $key = (string)($this->config['resend_api_key'] ?? '');
        $from = (string)($this->config['resend_from'] ?? '');
        $address = preg_match('/<([^<>]+)>$/', $from, $match) ? $match[1] : $from;
        if ($key === '' || preg_match('/[\r\n]/', $key.$from) || !filter_var($address,FILTER_VALIDATE_EMAIL) || !filter_var($to,FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Resend chưa được cấu hình hợp lệ.');
        $result = $this->http->json('POST','https://api.resend.com/emails',
            ['Authorization: Bearer '.$key, 'Content-Type: application/json', 'Idempotency-Key: '.bin2hex(random_bytes(16))],
            json_encode(['from'=>$from,'to'=>[$to],'subject'=>$subject,'text'=>$text], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        if (!is_string($result['id'] ?? null) || $result['id'] === '') throw new \RuntimeException('Resend chưa chấp nhận email.');
    }
}

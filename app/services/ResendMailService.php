<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\{AuthMailerInterface, HttpClientInterface};

final class ResendMailService implements AuthMailerInterface
{
    public function __construct(private HttpClientInterface $http, private array $config) {}

    public function sendPasswordReset(string $recipient, string $resetUrl): void
    {
        $this->send($recipient, PasswordResetEmail::link($resetUrl));
    }

    public function sendOtp(string $recipient, string $code): void
    {
        $this->send($recipient, PasswordResetEmail::otp($code));
    }

    /** @param array{subject:string,text:string,html:string} $message */
    private function send(string $to, array $message): void
    {
        $key = (string)($this->config['resend_api_key'] ?? '');
        $from = (string)($this->config['resend_from'] ?? '');
        $address = preg_match('/<([^<>]+)>$/', $from, $match) ? $match[1] : $from;
        if ($key === '' || preg_match('/[\r\n]/', $key . $from) || !filter_var($address, FILTER_VALIDATE_EMAIL) || !filter_var($to, FILTER_VALIDATE_EMAIL)) throw new \RuntimeException('Resend chưa được cấu hình hợp lệ.');
        $result = $this->http->json(
            'POST',
            'https://api.resend.com/emails',
            ['Authorization: Bearer ' . $key, 'Content-Type: application/json', 'Idempotency-Key: ' . bin2hex(random_bytes(16))],
            json_encode(['from' => $from, 'to' => [$to], 'subject' => $message['subject'], 'text' => $message['text'], 'html' => $message['html']], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );
        if (!is_string($result['id'] ?? null) || $result['id'] === '') throw new \RuntimeException('Resend chưa chấp nhận email.');
    }
}

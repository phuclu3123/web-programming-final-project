<?php
declare(strict_types=1);
namespace App\Contracts;

// Hợp đồng gửi OTP và liên kết khôi phục; nghiệp vụ không phụ thuộc transport email.
// Hợp đồng gửi OTP và liên kết khôi phục; nghiệp vụ không phụ thuộc transport email.
interface AuthMailerInterface
{
    public function sendPasswordReset(string $recipient, string $resetUrl): void;
    public function sendOtp(string $recipient, string $code): void;
}

<?php
declare(strict_types=1);
namespace App\Contracts;

// Phát hành token OTP cho repository giữ giới hạn gửi và một lần tiêu thụ.
// Phát hành token OTP cho repository giữ giới hạn gửi và một lần tiêu thụ.
interface OtpResetRepositoryInterface extends PasswordResetRepositoryInterface
{
    public function issueOtp(int $userId, string $tokenHash, string $ipHash): void;
}

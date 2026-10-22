<?php
declare(strict_types=1);
namespace App\Contracts;

interface OtpResetRepositoryInterface extends PasswordResetRepositoryInterface
{
    public function issueOtp(int $userId, string $tokenHash, string $ipHash): void;
}

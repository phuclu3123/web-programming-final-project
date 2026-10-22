<?php
declare(strict_types=1);
namespace App\Contracts;

interface AuthMailerInterface
{
    public function sendPasswordReset(string $recipient, string $resetUrl): void;
    public function sendOtp(string $recipient, string $code): void;
}

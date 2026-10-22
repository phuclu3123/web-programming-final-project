<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PasswordResetRepositoryInterface;
use App\Contracts\UserProfileRepositoryInterface;
use RuntimeException;
use Throwable;

final class PasswordResetService
{
    public function __construct(
        private UserProfileRepositoryInterface $users,
        private PasswordResetRepositoryInterface $tokens,
        private \App\Contracts\AuthMailerInterface $mail
    ) {}

    /** Always return a generic result; the caller never discloses account existence. */
    public function request(string $email, string $ip, string $baseUrl): void
    {
        $user = $this->users->findByEmail(mb_strtolower(trim($email)));
        $userId = is_array($user) && ($user['status'] ?? '') === 'ACTIVE' ? (int) $user['id'] : null;
        $ipHash = hash('sha256', $ip);
        if (!$this->tokens->canRequest($ipHash, $userId) || $userId === null) {
            return;
        }
        $raw = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $this->tokens->save($userId, $hash, $ipHash);
        try {
            $link = rtrim($baseUrl, '/') . '/reset-password?token=' . rawurlencode($raw);
            $this->mail->sendPasswordReset((string) $user['email'], $link);
        } catch (Throwable $e) {
            // No reset link is displayed in browser or written to logs.
            $this->tokens->delete($hash);
            error_log('CouponHub password-reset email delivery failed: ' . get_class($e));
        }
    }

    public function isValid(string $token): bool
    {
        return $this->validFormat($token) && $this->tokens->isUsable(hash('sha256', $token));
    }

    public function reset(string $token, string $password): bool
    {
        if (!$this->validFormat($token)) {
            return false;
        }
        if (preg_match('//u', $password) !== 1 || preg_match_all('/./us', $password) < 8 || strlen($password) > 72
            || str_contains($password, "\0") || !preg_match('/\p{Lu}/u', $password) || !preg_match('/\p{Ll}/u', $password)
            || !preg_match('/[0-9]/', $password) || !preg_match('/[^\p{L}\p{N}\s]/u', $password)) {
            throw new RuntimeException('Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường, số, ký tự đặc biệt và tối đa 72 byte.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        if (!is_string($hash)) {
            throw new RuntimeException('Không thể tạo mật khẩu an toàn.');
        }
        return $this->tokens->consume(hash('sha256', $token), $hash);
    }

    private function validFormat(string $token): bool
    {
        return preg_match('/\A[a-f0-9]{64}\z/D', $token) === 1;
    }
}

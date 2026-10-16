<?php
declare(strict_types=1);
namespace App\Contracts;


/** Repository contract: services do not depend on a specific SQL driver. */
interface PasswordResetRepositoryInterface
{
    public function canRequest(string $ipHash, ?int $userId): bool;
    public function save(int $userId, string $tokenHash, string $ipHash): void;
    public function delete(string $tokenHash): void;
    public function isUsable(string $tokenHash): bool;
    public function consume(string $tokenHash, string $newPasswordHash): bool;
}

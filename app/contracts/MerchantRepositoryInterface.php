<?php
declare(strict_types=1);
namespace App\Contracts;
use App\Models\Merchant;

use RuntimeException;

/** Repository contract: services do not depend on a specific SQL driver. */
interface MerchantRepositoryInterface
{
    public function availableForCommunitySubmission(): array;
    public function publicFilterOptions(): array;
    public function claimable(): array;
    public function applicationsByUser(int $userId): array;
    public function ownedApproved(int $userId): array;
    public function findOwnedApproved(int $merchantId, int $userId): ?array;
    public function requestClaim(int $userId, int $merchantId, ?string $evidence = null): void;
    public function requestNew(int $userId, string $name, string $slug, ?string $description, ?string $websiteUrl, ?string $evidence = null): int;
    public function updateProfile(int $merchantId, int $userId, string $name, ?string $description, ?string $websiteUrl): void;
    public function stats(int $actorId, int $merchantId): array;
    public function popular(int $limit = 6): array;
}

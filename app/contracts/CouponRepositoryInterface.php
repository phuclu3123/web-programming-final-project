<?php
declare(strict_types=1);
namespace App\Contracts;


/** Repository contract: services do not depend on a specific SQL driver. */
interface CouponRepositoryInterface
{
    public function search(array $filters): array;
    public function countPublicSearch(array $filters): int;
    public function findPublic(int $id): ?array;
    public function isActive(int $couponId): bool;
    public function findBySubmitter(int $userId): array;
    public function submitCommunity(int $actorId, array $data): int;
    public function pendingForModeration(): array;
    public function submitOfficial(int $actorId, array $data): int;
    public function officialByOwner(int $userId): array;
    public function findOwnedOfficialForEdit(int $couponId, int $userId): ?array;
    public function updateOwnedOfficial(int $actorId, int $couponId, array $data): void;
    public function disableOwnedOfficial(int $actorId, int $couponId): void;
    public function eligibleForMerchantCampaign(int $merchantId, int $userId): array;
    public function hotDeals(int $limit = 6): array;
    public function moderate(int $actorId, int $couponId, string $moderation, string $publication, ?string $note): void;
}

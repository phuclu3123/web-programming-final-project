<?php
declare(strict_types=1);
namespace App\Contracts;

use RuntimeException;

/** Repository contract: services do not depend on a specific SQL driver. */
interface CampaignRepositoryInterface
{
    public function publicCurrent(int $limit = 3): array;
    public function publicUpcoming(int $limit = 4): array;
    public function publicListing(string $phase, int $page, int $pageSize = 9): array;
    public function findPublic(int $id): ?array;
    public function publicCoupons(int $campaignId): array;
    public function findAdmin(int $campaignId): ?array;
    public function allActiveCoupons(): array;
    public function detachAdmin(int $campaignId, int $couponId): void;
    public function merchantCampaigns(int $userId): array;
    public function findOwned(int $campaignId, int $userId): ?array;
    public function create(int $actorId, ?int $merchantId, string $name, string $slug, ?string $description, string $startsAtUtc, string $endsAtUtc, string $status): int;
    public function updateOwned(int $campaignId, int $userId, string $name, ?string $description, string $startsAtUtc, string $endsAtUtc, string $status): void;
    public function attach(int $actorId, int $campaignId, int $couponId): void;
    public function detachOwned(int $campaignId, int $couponId, int $userId): void;
    public function attachedCoupons(int $campaignId): array;
}

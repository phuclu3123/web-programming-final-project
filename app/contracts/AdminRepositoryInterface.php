<?php
declare(strict_types=1);
namespace App\Contracts;

use RuntimeException;

/** Repository contract: services do not depend on a specific SQL driver. */
interface AdminRepositoryInterface
{
    public function dashboardStats(): array;
    public function users(): array;
    public function setUserStatus(int $actorId, int $userId, string $status): void;
    public function merchants(): array;
    public function reviewMerchant(int $actorId, int $merchantId, string $action, ?string $note): void;
    public function setMerchantStatus(int $merchantId, string $action, int $actorId, string $note = ''): void;
    public function categories(): array;
    public function addCategory(int $actorId, string $name, string $slug, ?string $description): void;
    public function toggleCategory(int $categoryId, bool $active): void;
    public function updateCategory(int $actorId, int $categoryId, string $name, string $slug, ?string $description, int $sortOrder): void;
    public function deleteUnusedCategory(int $actorId, int $categoryId): void;
    public function campaigns(): array;
    public function setCampaignStatus(int $campaignId, string $status, int $actorId = 0): void;
}

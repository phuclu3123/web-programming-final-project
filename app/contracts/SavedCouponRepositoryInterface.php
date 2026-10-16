<?php
declare(strict_types=1);
namespace App\Contracts;


/** Repository contract: services do not depend on a specific SQL driver. */
interface SavedCouponRepositoryInterface
{
    public function countForUser(int $userId): int;
    public function savedIds(int $userId, array $couponIds): array;
    public function categoriesForUser(int $userId): array;
    public function paginated(int $userId, array $filters, int $requestedPage, int $pageSize = 9): array;
}

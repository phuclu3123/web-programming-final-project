<?php
declare(strict_types=1);

namespace App\Contracts;

/** Dữ liệu cho trang chủ, form shop và màn duyệt coupon hiện tại. */
interface CatalogRepositoryInterface
{
    public function search(
        string $query,
        int $categoryId,
        int $merchantId,
        string $discountType,
        string $usageChannel,
        bool $endingSoon,
        string $sort
    ): array;

    public function shop(int $userId): array;

    public function create(int $userId, array $input): int;

    public function admin(int $userId, string $status, int $selectedId): array;

    public function approve(int $userId, int $couponId): void;

    public function reject(int $userId, int $couponId, string $reason): void;
}

<?php

declare(strict_types=1);

namespace App\Contracts;

// Ghi sự kiện xem, sao chép và chia sẻ theo request key để chống ghi trùng.
// Ghi sự kiện xem, sao chép và chia sẻ theo request key để chống ghi trùng.
interface CouponEventRepositoryInterface
{
    public function recordCopy(?int $actorId, int $couponId, string $requestKey): void;
    public function recordView(?int $actorId, int $couponId, string $requestKey): void;
    public function recordShare(?int $actorId, int $couponId, string $requestKey): void;
}

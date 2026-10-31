<?php

declare(strict_types=1);

namespace App\Contracts;

interface CouponEventRepositoryInterface
{
    public function recordCopy(?int $actorId, int $couponId, string $requestKey): void;
    public function recordView(?int $actorId, int $couponId, string $requestKey): void;
    public function recordShare(?int $actorId, int $couponId, string $requestKey): void;
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\CouponEventRepositoryInterface;
use App\Core\Database;

/** Persist public coupon events via the existing database procedure. */
final class CouponEventRepository implements CouponEventRepositoryInterface
{
    public function __construct(private Database $db) {}

    public function recordCopy(?int $actorId, int $couponId, string $requestKey): void
    {
        $this->record($actorId, $couponId, 'COPY', $requestKey);
    }

    public function recordView(?int $actorId, int $couponId, string $requestKey): void
    {
        $this->record($actorId, $couponId, 'VIEW', $requestKey);
    }

    public function recordShare(?int $actorId, int $couponId, string $requestKey): void
    {
        $this->record($actorId, $couponId, 'SHARE', $requestKey);
    }

    private function record(?int $actorId, int $couponId, string $event, string $requestKey): void
    {
        $stmt = $this->db->prepare('CALL sp_record_coupon_event(?, ?, ?, ?)');
        try {
            $stmt->execute([$actorId, $couponId, $event, $requestKey]);
        } finally {
            $stmt->closeCursor();
        }
    }
}

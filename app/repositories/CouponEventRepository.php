<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/** Persist real user activity through the existing database routine. */
final class CouponEventRepository
{
    public function __construct(private Database $db) {}

    public function recordCopy(?int $actorId, int $couponId, string $requestKey): void
    {
        $stmt = $this->db->prepare('CALL sp_record_coupon_event(?, ?, ?, ?)');
        try {
            $stmt->execute([$actorId, $couponId, 'COPY', $requestKey]);
        } finally {
            $stmt->closeCursor();
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CouponEventRepository;
use InvalidArgumentException;

/** Business rules and per-session throttling for public coupon-copy analytics. */
final class CouponEventService
{
    public function __construct(private CouponEventRepository $repository) {}

    public function recordCopy(?int $actorId, int $couponId): bool
    {
        if ($couponId <= 0) {
            throw new InvalidArgumentException('Coupon không hợp lệ.');
        }
        \App\Core\Session::start();
        // One COPY per coupon per browser session in any 15-second window.
        $last = (int) ($_SESSION['coupon_copy_last'][$couponId] ?? 0);
        if ($last > 0 && time() - $last < 15) {
            return false;
        }
        $this->repository->recordCopy($actorId, $couponId, bin2hex(random_bytes(16)));
        $_SESSION['coupon_copy_last'][$couponId] = time();
        return true;
    }
}

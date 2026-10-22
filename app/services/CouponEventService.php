<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CouponEventRepositoryInterface;
use InvalidArgumentException;

/** Business rules and per-session throttling for public coupon-copy analytics. */
final class CouponEventService
{
    public function __construct(private CouponEventRepositoryInterface $repository) {}

    public function recordCopy(?int $actorId, int $couponId): bool
    {
        if ($couponId <= 0) {
            throw new InvalidArgumentException('Coupon không hợp lệ.');
        }
        \App\Core\Session::start();
        $now = time();
        // Expired entries must not accumulate for the lifetime of a session.
        $_SESSION['coupon_copy_last'] = array_filter($_SESSION['coupon_copy_last'] ?? [], static fn($time) => is_int($time) && $now - $time < 15);
        // One COPY per coupon per browser session in any 15-second window.
        $last = (int) ($_SESSION['coupon_copy_last'][$couponId] ?? 0);
        if ($last > 0 && time() - $last < 15) {
            return false;
        }
        $this->repository->recordCopy($actorId, $couponId, bin2hex(random_bytes(16)));
        if (count($_SESSION['coupon_copy_last']) >= 100) unset($_SESSION['coupon_copy_last'][array_key_first($_SESSION['coupon_copy_last'])]);
        $_SESSION['coupon_copy_last'][$couponId] = time();
        return true;
    }
}

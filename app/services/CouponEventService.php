<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\CouponEventRepositoryInterface;
use App\Core\Session;
use InvalidArgumentException;

/** Business rules and per-session throttling for public coupon analytics. */
final class CouponEventService
{
    private const VIEW_WINDOW_SECONDS = 900; // One view per coupon per browser session every 15 minutes.
    private const COPY_WINDOW_SECONDS = 15;
    private const SHARE_WINDOW_SECONDS = 60;
    private const MAX_TRACKED_COUPONS = 100;

    public function __construct(private CouponEventRepositoryInterface $repository) {}

    public function recordView(?int $actorId, int $couponId): bool
    {
        return $this->recordThrottled($actorId, $couponId, 'VIEW', 'coupon_view_last', self::VIEW_WINDOW_SECONDS);
    }

    public function recordCopy(?int $actorId, int $couponId): bool
    {
        return $this->recordThrottled($actorId, $couponId, 'COPY', 'coupon_copy_last', self::COPY_WINDOW_SECONDS);
    }

    public function recordShare(?int $actorId, int $couponId): bool
    {
        return $this->recordThrottled($actorId, $couponId, 'SHARE', 'coupon_share_last', self::SHARE_WINDOW_SECONDS);
    }

    private function recordThrottled(?int $actorId, int $couponId, string $event, string $sessionKey, int $window): bool
    {
        if ($couponId <= 0) {
            throw new InvalidArgumentException('Coupon không hợp lệ.');
        }

        Session::start();
        $now = time();
        $recent = $_SESSION[$sessionKey] ?? [];
        if (!is_array($recent)) {
            $recent = [];
        }
        // Prune expired and malformed session entries; avoid unbounded growth.
        $recent = array_filter($recent, static fn ($last): bool =>
            is_int($last) && $last <= $now && $now - $last < $window);
        $_SESSION[$sessionKey] = $recent;
        if (isset($recent[$couponId])) {
            return false;
        }

        $requestKey = bin2hex(random_bytes(16));
        match ($event) {
            'VIEW' => $this->repository->recordView($actorId, $couponId, $requestKey),
            'SHARE' => $this->repository->recordShare($actorId, $couponId, $requestKey),
            default => $this->repository->recordCopy($actorId, $couponId, $requestKey),
        };
        // Set the throttle only after the database has successfully persisted the event.
        if (count($recent) >= self::MAX_TRACKED_COUPONS) {
            unset($recent[array_key_first($recent)]);
        }
        $recent[$couponId] = $now;
        $_SESSION[$sessionKey] = $recent;
        return true;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AdminCouponRepository;
use RuntimeException;

final class AdminCouponService
{
    public function __construct(private AdminCouponRepository $repo, private CouponService $couponValidation) {}

    /** @param array<string,mixed> $input */
    public function update(int $actorId, int $couponId, array $input): void
    {
        if ($actorId <= 0 || $couponId <= 0) throw new RuntimeException('Thông tin coupon không hợp lệ.');
        foreach ($input as $value) {
            if ($value !== null && (!is_scalar($value) || !mb_check_encoding((string)$value, 'UTF-8'))) {
                throw new RuntimeException('Thông tin coupon không hợp lệ.');
            }
        }
        $coupon = $this->repo->find($couponId);
        if ($coupon === null) throw new RuntimeException('Coupon không tồn tại.');
        // Merchant ownership cannot be moved using request fields.
        $input['merchant_id'] = (int)$coupon['merchant_id'];
        $data = $this->couponValidation->validateSubmission($input);
        $this->repo->update($actorId, $couponId, $data);
    }

    public function expire(int $actorId, int $couponId, string $reason): void
    {
        $this->change($actorId, $couponId, $reason, false);
    }

    public function delete(int $actorId, int $couponId, string $reason): void
    {
        $this->change($actorId, $couponId, $reason, true);
    }

    public function revoke(int $actorId, int $couponId, string $reason): void
    {
        $reason=trim($reason);
        if ($actorId<=0 || $couponId<=0 || !mb_check_encoding($reason,'UTF-8') || mb_strlen($reason)<10 || mb_strlen($reason)>500) throw new RuntimeException('Lý do thu hồi phải từ 10 đến 500 ký tự.');
        $this->repo->revoke($actorId,$couponId,$reason);
    }

    private function change(int $actorId, int $couponId, string $reason, bool $delete): void
    {
        $reason = trim($reason);
        if ($actorId <= 0 || $couponId <= 0) throw new RuntimeException('Thông tin coupon không hợp lệ.');
        if (!mb_check_encoding($reason, 'UTF-8') || mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
            throw new RuntimeException('Lý do xử lý phải từ 10 đến 500 ký tự.');
        }
        if ($delete) $this->repo->delete($actorId, $couponId, $reason);
        else $this->repo->expire($actorId, $couponId, $reason);
    }
}

<?php
declare(strict_types=1);

final class Coupon implements CouponRepositoryInterface
{
    public function __construct(private readonly RoutineGateway $db) {}

    public function search(
        string $q,
        int $categoryId,
        int $merchantId,
        string $discountType,
        string $usageChannel,
        bool $endingSoon,
        string $sort
    ): array
    {
        return $this->db->call('ch_public_catalog_v3', [
            $q,
            $categoryId,
            $merchantId,
            $discountType,
            $usageChannel,
            $endingSoon ? 1 : 0,
            $sort,
        ]);
    }

    public function shop(int $userId): array
    {
        return $this->db->call('ch_shop_dashboard_v2', [$userId]);
    }

    public function create(int $userId, array $input): int
    {
        $sets = $this->db->call('ch_create_coupon', [
            $userId,
            (int) $input['merchant_id'],
            (int) $input['category_id'],
            $input['title'],
            strtoupper($input['code']),
            $input['description'],
            $input['terms'],
            $input['discount_type'],
            $input['discount_value'],
            $input['min_order_amount'],
            $input['max_discount_amount'] === '' ? null : $input['max_discount_amount'],
            $input['usage_channel'],
            $input['starts_on'] . ' 00:00:00',
            $input['expires_on'] . ' 23:59:59',
        ]);
        return (int) $sets[0][0]['coupon_id'];
    }

    public function admin(int $userId, string $status, int $selectedId): array
    {
        return $this->db->call('ch_admin_dashboard_v2', [$userId, $status, $selectedId]);
    }

    public function approve(int $userId, int $couponId): void
    {
        $this->db->call('ch_approve_coupon', [$userId, $couponId]);
    }

    public function reject(int $userId, int $couponId, string $reason): void
    {
        $this->db->call('ch_reject_coupon', [$userId, $couponId, $reason]);
    }
}

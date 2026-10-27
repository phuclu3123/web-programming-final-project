<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Ước tính số tiền giảm giá cho một đơn hàng, chỉ áp dụng một coupon. Các giá trị tiền tệ sử dụng số nguyên (VNĐ).
 * Số tiền thanh toán cuối cùng được xác định tại merchant. */
final class BestDealCalculator
{
    public const MAX_ORDER = 1_000_000_000;
    public const MAX_SHIPPING = 100_000_000;

    public function parseAmount(string $value, int $max): ?int
    {
        // Chỉ chấp nhận số nguyên không âm trong giới hạn cho phép. (Loại bỏ số thập phân, số âm, ký hiệu phân cách và giá trị quá lớn.)
        if ($value === '' || !preg_match('/^\d{1,11}$/D', $value)) {
            return null;
        }
        $normalized = ltrim($value, '0');
        $normalized = $normalized === '' ? '0' : $normalized;
        if (strlen($normalized) > strlen((string) $max)) {
            return null;
        }
        $parsed = (int) $normalized;
        return $parsed <= $max ? $parsed : null;
    }

    /** @param array<string,mixed> $coupon
     *  @return array{eligible:bool,saving:int,payable:int,reason:string,type:string}
     */
    public function estimate(array $coupon, int $order, int $shipping, string $channel): array
    {
        $base = ['eligible' => false, 'saving' => 0, 'payable' => $order + $shipping, 'reason' => '', 'type' => (string) ($coupon['discount_type'] ?? '')];
        if ($order < 0 || $order > self::MAX_ORDER || $shipping < 0 || $shipping > self::MAX_SHIPPING) {
            $base['reason'] = 'Giá trị đơn hàng không hợp lệ.';
            return $base;
        }
        if (!in_array($channel, ['ONLINE', 'OFFLINE'], true)) {
            $base['reason'] = 'Kênh sử dụng không hợp lệ.';
            return $base;
        }
        if (($coupon['currency'] ?? 'VND') !== 'VND') {
            $base['reason'] = 'Chỉ hỗ trợ coupon VND.';
            return $base;
        }
        $usage = (string) ($coupon['usage_channel'] ?? '');
        if (!in_array($usage, [$channel, 'BOTH'], true)) {
            $base['reason'] = 'Không hỗ trợ kênh sử dụng đã chọn.';
            return $base;
        }
        $minOrder = (float) ($coupon['min_order_amount'] ?? 0);
        if (!is_finite($minOrder) || $minOrder < 0 || $order < $minOrder) {
            $base['reason'] = 'Chưa đạt giá trị đơn hàng tối thiểu.';
            return $base;
        }

        $type = $base['type'];
        $value = (float) ($coupon['discount_value'] ?? 0);
        $capValue = $coupon['max_discount_amount'] ?? null;
        $cap = $capValue === null || $capValue === '' ? null : (float) $capValue;
        if (($cap !== null && (!is_finite($cap) || $cap < 0)) || !is_finite($value)) {
            $base['reason'] = 'Thông tin ưu đãi chưa hợp lệ.';
            return $base;
        }
        $saving = 0;
        switch ($type) {
            case 'PERCENTAGE':
                if ($value <= 0 || $value > 100) {
                    $base['reason'] = 'Phần trăm giảm không hợp lệ.';
                    return $base;
                }
                $saving = (int) round($order * $value / 100, 0, PHP_ROUND_HALF_UP);
                break;
            case 'FIXED_AMOUNT':
                if ($value <= 0) {
                    $base['reason'] = 'Số tiền giảm không hợp lệ.';
                    return $base;
                }
                $saving = (int) round($value, 0, PHP_ROUND_HALF_UP);
                break;
            case 'FREE_SHIPPING':
                if ($channel !== 'ONLINE' || $shipping === 0) {
                    $base['reason'] = 'Cần chọn mua trực tuyến và có phí vận chuyển.';
                    return $base;
                }
                $saving = $shipping;
                break;
            default:
                // Cashback is generally received later. Gift/BXGY require item-level rules.
                $base['reason'] = 'Loại ưu đãi này chưa thể ước tính giảm trực tiếp.';
                return $base;
        }

        if ($cap !== null) {
            $saving = min($saving, (int) round($cap, 0, PHP_ROUND_HALF_UP));
        }
        $saving = min($saving, $type === 'FREE_SHIPPING' ? $shipping : $order);
        if ($saving <= 0) {
            $base['reason'] = 'Không có khoản giảm trực tiếp ước tính.';
            return $base;
        }
        return ['eligible' => true, 'saving' => $saving, 'payable' => $order + $shipping - $saving, 'reason' => '', 'type' => $type];
    }

    /**
     * @param array<int,array<string,mixed>> $coupons
     * @return array{eligible:array<int,array<string,mixed>>,excluded:int}
     */
    public function compare(array $coupons, int $order, int $shipping, string $channel): array
    {
        $eligible = [];
        $excluded = 0;
        foreach ($coupons as $coupon) {
            $quote = $this->estimate($coupon, $order, $shipping, $channel);
            if (!$quote['eligible']) {
                ++$excluded;
                continue;
            }
            $coupon['estimated_saving'] = $quote['saving'];
            $coupon['estimated_payable'] = $quote['payable'];
            $eligible[] = $coupon;
        }
        usort($eligible, static function (array $a, array $b): int {
            return ($b['estimated_saving'] <=> $a['estimated_saving'])
                ?: strcmp((string) $a['ends_at'], (string) $b['ends_at'])
                ?: ((int) $a['id'] <=> (int) $b['id']);
        });
        return ['eligible' => $eligible, 'excluded' => $excluded];
    }
}

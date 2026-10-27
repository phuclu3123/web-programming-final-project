<?php

declare(strict_types=1);

namespace App\Services;

/** Conservative feasibility check, not an assertion that any merchant permits stacking. */
final class CouponStackChecker
{
    /**
     * @param array<string,int> $slots IDs chosen by the visitor: shop, shipping, payment
     * @param array<int,array<string,mixed>> $available Public active coupons for ONE merchant/channel
     * @return array{status:string,reason:string,details:array<int,array<string,mixed>>,maximum_saving:int,minimum_payable:int}
     */
    public function check(array $slots, array $available, int $order, int $shipping, string $channel): array
    {
        $result = ['status' => 'INCOMPATIBLE', 'reason' => '', 'details' => [], 'maximum_saving' => 0, 'minimum_payable' => $order + $shipping];
        $ids = array_filter($slots, static fn(int $id): bool => $id > 0);
        if ($ids === []) {
            $result['reason'] = 'Hãy chọn ít nhất một coupon.';
            return $result;
        }
        if (count($ids) !== count(array_unique($ids))) {
            $result['reason'] = 'Một coupon không thể nằm trong nhiều vị trí.';
            return $result;
        }
        if (!in_array($channel, ['ONLINE', 'OFFLINE'], true) || $order < 1 || $order > BestDealCalculator::MAX_ORDER || $shipping < 0 || $shipping > BestDealCalculator::MAX_SHIPPING) {
            $result['reason'] = 'Dữ liệu đơn hàng không hợp lệ.';
            return $result;
        }
        $byId = [];
        foreach ($available as $item) {
            if (isset($item['id'])) $byId[(int)$item['id']] = $item;
        }
        $calculator = new BestDealCalculator();
        $itemSaving = 0;
        $shipSaving = 0;
        foreach ($ids as $slot => $id) {
            $coupon = $byId[$id] ?? null;
            if ($coupon === null || (isset($coupon['effective_status']) && $coupon['effective_status'] !== 'ACTIVE')) {
                $result['reason'] = 'Có coupon không công khai, hết hiệu lực hoặc không thuộc cửa hàng/kênh này.';
                return $result;
            }
            $type = (string)($coupon['discount_type'] ?? '');
            if (($slot === 'shipping') !== ($type === 'FREE_SHIPPING')) {
                $result['reason'] = 'Vị trí miễn phí vận chuyển chỉ nhận mã freeship; các vị trí khác nhận mã giảm giá đơn hàng.';
                return $result;
            }
            $quote = $calculator->estimate($coupon, $order, $shipping, $channel);
            if (!$quote['eligible']) {
                $result['reason'] = 'Một coupon không đạt điều kiện: ' . $quote['reason'];
                return $result;
            }
            $result['details'][] = ['slot' => $slot, 'id' => $id, 'title' => (string)$coupon['title'], 'saving' => $quote['saving']];
            if ($slot === 'shipping') $shipSaving += $quote['saving'];
            else $itemSaving += $quote['saving'];
        }
        // Independent quotes yield only an optimistic ceiling, not a checkout guarantee.
        $result['maximum_saving'] = min($order, $itemSaving) + min($shipping, $shipSaving);
        $result['minimum_payable'] = $order + $shipping - $result['maximum_saving'];
        if (count($ids) === 1) {
            $result['status'] = 'SINGLE';
            $result['reason'] = 'Một coupon hợp lệ theo dữ liệu có sẵn; không phải kết hợp nhiều mã.';
        } else {
            $result['status'] = 'UNVERIFIED';
            $result['reason'] = 'Các điều kiện cơ bản có thể đáp ứng, nhưng CouponHub chưa có quy tắc kết hợp và thông tin phương thức thanh toán do Merchant xác nhận. Không thể khẳng định các mã được dùng đồng thời.';
        }
        return $result;
    }
}

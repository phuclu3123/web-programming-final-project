<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\CouponRepository;
use App\Repositories\MerchantRepository;
use App\Services\BestDealCalculator;

/** Public GET-only quote: no checkout writes, no access to private coupon rows. */
final class DealCalculatorController extends Controller
{
    public function index(Request $request): void
    {
        $merchants = (new MerchantRepository(Database::connection()))->publicFilterOptions();
        $calculator = new BestDealCalculator();

        $merchantInput = $request->query('merchant_id');
        $orderInput = $request->query('order_amount');
        $shippingInput = $request->query('shipping_fee', '0');
        $channel = $request->query('channel', 'ONLINE');
        $submitted = $merchantInput !== '' || $orderInput !== '';
        $errors = [];
        $merchantId = 0;
        $merchantName = '';
        $order = null;
        $shipping = null;
        $comparison = null;

        if ($submitted) {
            if (!ctype_digit($merchantInput) || $merchantInput === '0') {
                $errors['merchant_id'] = 'Vui lòng chọn cửa hàng.';
            } else {
                $selectedId = (int) $merchantInput;
                foreach ($merchants as $merchant) {
                    if ($selectedId === (int) $merchant->id) {
                        $merchantId = $selectedId;
                        $merchantName = (string) $merchant->name;
                        break;
                    }
                }
                if ($merchantId === 0) {
                    $errors['merchant_id'] = 'Cửa hàng không có coupon công khai.';
                }
            }
            $order = $calculator->parseAmount($orderInput, BestDealCalculator::MAX_ORDER);
            if ($order === null || $order === 0) {
                $errors['order_amount'] = 'Nhập giá trị đơn hàng từ 1 đến 1.000.000.000đ (số nguyên).';
            }
            $shipping = $calculator->parseAmount($shippingInput, BestDealCalculator::MAX_SHIPPING);
            if ($shipping === null) {
                $errors['shipping_fee'] = 'Phí vận chuyển phải từ 0 đến 100.000.000đ.';
            }
            if (!in_array($channel, ['ONLINE', 'OFFLINE'], true)) {
                $errors['channel'] = 'Kênh sử dụng không hợp lệ.';
            }
            if ($errors === []) {
                $coupons = (new CouponRepository(Database::connection()))
                    ->publicDiscountsForMerchant($merchantId, $channel);
                $comparison = $calculator->compare($coupons, $order, $shipping, $channel);
            }
        }

        $this->view('coupons/best-deal', [
            'pageTitle' => 'So sánh mã giảm giá | CouponHub',
            'merchants' => $merchants,
            'merchantId' => $merchantId,
            'merchantInput' => $merchantInput,
            'merchantName' => $merchantName,
            'orderInput' => $orderInput,
            'shippingInput' => $shippingInput,
            'channel' => $channel,
            'errors' => $errors,
            'submitted' => $submitted,
            'comparison' => $comparison,
            'order' => $order,
            'shipping' => $shipping,
        ]);
    }
}

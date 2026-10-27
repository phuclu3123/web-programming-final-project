<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\MerchantRepository;
use App\Services\BestDealCalculator;
use App\Services\CouponStackChecker;
use App\Services\SmartCouponMatcher;

/** GET-only public tools; no coupon ownership or database state mutations. */
final class CouponDiscoveryToolsController extends Controller
{
    public function match(Request $request): void
    {
        $db = Database::connection();
        $merchants = (new MerchantRepository($db))->publicFilterOptions();
        $categories = (new CategoryRepository($db))->activeAll();
        $repository = new CouponRepository($db);
        $matcher = new SmartCouponMatcher();
        $calculator = new BestDealCalculator();

        $input = [
            'q'=>$request->query('q'),
            'merchant_id'=>$request->query('merchant_id'),
            'category_id'=>$request->query('category_id'),
            'order_amount'=>$request->query('order_amount'),
            'channel'=>$request->query('channel', 'ONLINE'),
        ];
        $submitted = $input['q'] !== '' || $input['merchant_id'] !== '' || $input['category_id'] !== '' || $input['order_amount'] !== '';
        $errors = [];
        $matches = [];
        $budget = null;
        $merchantId = null;
        $categoryId = null;
        if ($submitted) {
            if (preg_match('//u', $input['q']) !== 1 || preg_match_all('/./us', $input['q']) > 160) {
                $errors['q'] = 'Nhu cầu phải là văn bản UTF-8 tối đa 160 ký tự.';
            }
            if (!in_array($input['channel'], ['ONLINE','OFFLINE'], true)) $errors['channel'] = 'Kênh sử dụng không hợp lệ.';
            $merchantId = self::selectedId($input['merchant_id'], $merchants);
            $categoryId = self::selectedId($input['category_id'], $categories);
            if ($input['merchant_id'] !== '' && $merchantId === null) $errors['merchant_id'] = 'Merchant không có coupon công khai.';
            if ($input['category_id'] !== '' && $categoryId === null) $errors['category_id'] = 'Danh mục không hợp lệ.';
            if ($input['order_amount'] !== '') {
                $budget = $calculator->parseAmount($input['order_amount'], BestDealCalculator::MAX_ORDER);
                if ($budget === null || $budget < 1) $errors['order_amount'] = 'Giá trị đơn hàng phải từ 1 đến 1.000.000.000đ.';
            } else {
                $budget = $matcher->budgetFromText($input['q']);
            }
            if ($input['q'] === '' && $merchantId === null && $categoryId === null) {
                $errors['q'] = 'Nhập nhu cầu hoặc chọn Merchant/danh mục để tìm mã.';
            }
            if ($errors === []) {
                $candidates = $repository->publicMatchingCandidates($merchantId, $categoryId, $input['channel']);
                $matches = $matcher->recommend($candidates, $input['q'], $budget, $input['channel']);
            }
        }
        $this->view('coupons/match', compact('merchants','categories','input','submitted','errors','matches','budget') + [
            'pageTitle'=>'Tìm coupon theo nhu cầu | CouponHub',
        ]);
    }

    public function stack(Request $request): void
    {
        $db = Database::connection();
        $merchants = (new MerchantRepository($db))->publicFilterOptions();
        $coupons = new CouponRepository($db);
        $calculator = new BestDealCalculator();
        $input = [
            'merchant_id'=>$request->query('merchant_id'),
            'order_amount'=>$request->query('order_amount'),
            'shipping_fee'=>$request->query('shipping_fee', '0'),
            'channel'=>$request->query('channel', 'ONLINE'),
            'shop'=>$request->query('shop'),
            'shipping'=>$request->query('shipping'),
            'payment'=>$request->query('payment'),
        ];
        $merchantId = self::selectedId($input['merchant_id'], $merchants);
        $errors = [];
        $options = [];
        $outcome = null;
        $submitted = $request->query('check') === '1';
        if ($input['merchant_id'] !== '' && $merchantId === null) $errors['merchant_id'] = 'Vui lòng chọn Merchant công khai.';
        if (!in_array($input['channel'], ['ONLINE','OFFLINE'], true)) $errors['channel'] = 'Kênh sử dụng không hợp lệ.';
        if ($merchantId !== null && !isset($errors['channel'])) {
            $options = $coupons->publicDiscountsForMerchant($merchantId, $input['channel']);
        }
        if ($submitted) {
            if ($merchantId === null) $errors['merchant_id'] = 'Vui lòng chọn Merchant công khai.';
            $order = $calculator->parseAmount($input['order_amount'], BestDealCalculator::MAX_ORDER);
            $shipping = $calculator->parseAmount($input['shipping_fee'], BestDealCalculator::MAX_SHIPPING);
            if ($order === null || $order < 1) $errors['order_amount'] = 'Đơn hàng phải từ 1 đến 1.000.000.000đ.';
            if ($shipping === null) $errors['shipping_fee'] = 'Phí vận chuyển phải từ 0 đến 100.000.000đ.';
            $slots = [];
            foreach (['shop','shipping','payment'] as $name) {
                $raw = $input[$name];
                $parsed = filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
                if ($raw !== '' && $parsed === false) $errors[$name] = 'ID coupon không hợp lệ.';
                $slots[$name] = $raw === '' ? 0 : ($parsed === false ? 0 : $parsed);
            }
            if ($errors === []) $outcome = (new CouponStackChecker())->check($slots, $options, $order, $shipping, $input['channel']);
        }
        $this->view('coupons/stack-checker', compact('merchants','input','options','errors','outcome','submitted') + [
            'pageTitle'=>'Kiểm tra kết hợp coupon | CouponHub',
        ]);
    }

    /** @param array<int,object> $allowed */
    private static function selectedId(string $raw, array $allowed): ?int
    {
        if ($raw === '') return null;
        $parsed = filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if ($parsed === false) return null;
        foreach ($allowed as $option) if ((int)$option->id === $parsed) return $parsed;
        return null;
    }
}

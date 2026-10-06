<?php
declare(strict_types=1);

final class HomeController extends AbstractController
{
    public function handle(): void
    {
        $this->requireMethod('GET');

        $q = $this->request->query('q');
        if (textLength($q) > 100) {
            throw new HttpError(422, 'Từ khóa tối đa 100 ký tự.');
        }

        $categoryId = positiveId($this->request->query('category', '0'), true);
        $merchantId = positiveId($this->request->query('merchant', '0'), true);
        $discountType = $this->request->query('type');
        if (!in_array($discountType, ['', 'PERCENTAGE', 'FIXED_AMOUNT', 'FREE_SHIPPING', 'BUY_X_GET_Y', 'GIFT', 'CASHBACK'], true)) {
            throw new HttpError(422, 'Loại ưu đãi không hợp lệ.');
        }
        $usageChannel = $this->request->query('channel');
        if (!in_array($usageChannel, ['', 'ONLINE', 'OFFLINE', 'BOTH'], true)) {
            throw new HttpError(422, 'Kênh sử dụng không hợp lệ.');
        }
        $ending = $this->request->query('ending', '0');
        if (!in_array($ending, ['0', '1'], true)) {
            throw new HttpError(422, 'Bộ lọc sắp hết hạn không hợp lệ.');
        }
        $endingSoon = $ending === '1';
        $sort = $this->request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'expiry'], true)) {
            throw new HttpError(422, 'Thứ tự sắp xếp không hợp lệ.');
        }

        [$categories, $merchants, $coupons] = (new Coupon($this->db))->search(
            $q,
            $categoryId,
            $merchantId,
            $discountType,
            $usageChannel,
            $endingSoon,
            $sort
        );

        $role = 'user';
        $title = 'Khám phá ưu đãi';
        $demo = Session::localDemo($this->config);

        if ($this->request->isAjax()) {
            header('Vary: X-Requested-With');
        }

        $this->render('home', compact(
            'q', 'categoryId', 'merchantId', 'discountType', 'usageChannel', 'endingSoon', 'sort',
            'categories', 'merchants', 'coupons',
            'role', 'title', 'demo'
        ));
    }
}

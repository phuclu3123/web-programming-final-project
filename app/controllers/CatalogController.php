<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\Request;
use App\Repositories\CatalogRepository;
use App\Repositories\SavedCouponRepository;
use App\Repositories\CampaignRepository;
use App\Repositories\ArticleRepository;

final class CatalogController extends Controller
{
    public function index(Request $request): void
    {

        $currentUser = Auth::user();

        $q = $request->query('q');
        if (textLength($q) > 100) {
            throw new HttpError(422, 'Từ khóa tối đa 100 ký tự.');
        }

        $categoryId = positiveId($request->query('category', '0'), true);
        $merchantId = positiveId($request->query('merchant', '0'), true);
        $discountType = $request->query('type');
        if (!in_array($discountType, ['', 'PERCENTAGE', 'FIXED_AMOUNT', 'FREE_SHIPPING', 'BUY_X_GET_Y', 'GIFT', 'CASHBACK'], true)) {
            throw new HttpError(422, 'Loại ưu đãi không hợp lệ.');
        }
        $usageChannel = $request->query('channel');
        if (!in_array($usageChannel, ['', 'ONLINE', 'OFFLINE', 'BOTH'], true)) {
            throw new HttpError(422, 'Kênh sử dụng không hợp lệ.');
        }
        $ending = $request->query('ending', '0');
        if (!in_array($ending, ['0', '1'], true)) {
            throw new HttpError(422, 'Bộ lọc sắp hết hạn không hợp lệ.');
        }
        $endingSoon = $ending === '1';
        $sort = $request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'expiry'], true)) {
            throw new HttpError(422, 'Thứ tự sắp xếp không hợp lệ.');
        }

        [$categories, $merchants, $coupons] = (new CatalogRepository(Database::connection()))->search(
            $q,
            $categoryId,
            $merchantId,
            $discountType,
            $usageChannel,
            $endingSoon,
            $sort
        );

        $role = 'user';
        $savedCouponIds = $currentUser
            ? (new SavedCouponRepository(Database::connection()))->savedIds((int)$currentUser['id'], array_column($coupons, 'id')) : [];
        $title = 'Khám phá ưu đãi';
        $demo = false;
        $campaignRepo = new CampaignRepository(Database::connection());
        $homeCampaigns = array_merge($campaignRepo->publicCurrent(2),$campaignRepo->publicUpcoming(1));
        $homeArticles = (new ArticleRepository(Database::connection()))->published(3);

        if ($request->isAjax()) {
            header('Vary: X-Requested-With');
        }

        $this->view('home', compact(
            'q', 'categoryId', 'merchantId', 'discountType', 'usageChannel', 'endingSoon', 'sort',
            'categories', 'merchants', 'coupons',
            'role', 'title', 'demo', 'currentUser', 'savedCouponIds', 'homeCampaigns', 'homeArticles'
        ), null);
    }
}

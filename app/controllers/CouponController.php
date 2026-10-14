<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\InteractionRepository;
use App\Repositories\MerchantRepository;
use App\Services\CouponService;
use App\Services\InteractionService;
use RuntimeException;
use Throwable;

final class CouponController extends Controller
{
    private CouponRepository $coupons;
    private CategoryRepository $categories;
    private MerchantRepository $merchants;
    private CouponService $service;
    private InteractionService $interactions;

    public function __construct()
    {
        $pdo = Database::connection();
        $this->coupons = new CouponRepository($pdo);
        $this->categories = new CategoryRepository($pdo);
        $this->merchants = new MerchantRepository($pdo);
        $this->service = new CouponService($this->coupons);
        $this->interactions = new InteractionService(new InteractionRepository($pdo), $this->coupons);
    }

    public function index(Request $request): void
    {
        $filters = $this->service->normalizeSearch($request->all());
        [$coupons, $pagination] = $this->publicSearchPage($filters);

        $this->view('coupons/index', [
            'pageTitle' => 'Tìm coupon',
            'coupons' => $coupons,
            'categories' => $this->categories->activeAll(),
            'merchants' => $this->merchants->publicFilterOptions(),
            'filters' => $filters,
            'pagination' => $pagination,
            'pageScripts' => ['/js/search.js'],
        ]);
    }

    public function show(Request $request, string $id): void
    {
        $couponId = filter_var($id, FILTER_VALIDATE_INT);
        if ($couponId === false || (int) $couponId <= 0) {
            $this->renderError(404, 'Không tìm thấy coupon.');
            return;
        }

        $coupon = $this->coupons->findPublic((int) $couponId);
        if ($coupon === null) {
            $this->renderError(404, 'Coupon không tồn tại hoặc hiện không được công khai.');
            return;
        }

        $state = $this->interactions->initialState(Auth::id(), (int) $couponId);

        $this->view('coupons/show', [
            'pageTitle' => (string) $coupon['title'],
            'coupon' => $coupon,
            'interactionState' => $state,
        ]);
    }

    public function create(Request $request): void
    {
        // Community contribution belongs to USER/MERCHANT. ADMIN should review,
        // not submit through the same community flow.
        Auth::requireRole(['USER', 'MERCHANT']);
        $this->renderCreate([], []);
    }

    public function store(Request $request): void
    {
        Auth::requireRole(['USER', 'MERCHANT']);
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }

        $input = $request->all();
        try {
            $this->service->submitCommunity((int) Auth::id(), $input);
            Session::flash('success', 'Đã gửi coupon. Coupon đang chờ Admin kiểm duyệt.');
            Response::redirect(url('/my-coupons'));
        } catch (ValidationException $e) {
            $this->renderCreate($e->errors(), $input);
        } catch (RuntimeException $e) {
            $this->renderCreate(['auth' => $e->getMessage()], $input);
        } catch (Throwable $e) {
            if ((bool) config('app.debug', false)) {
                $this->renderCreate(['auth' => $e->getMessage()], $input);
                return;
            }
            $this->renderCreate(['auth' => 'Không thể gửi coupon lúc này.'], $input);
        }
    }

    public function myCoupons(Request $request): void
    {
        Auth::requireRole(['USER', 'MERCHANT']);
        $this->view('coupons/my', [
            'pageTitle' => 'Coupon đã đóng góp',
            'coupons' => $this->coupons->findBySubmitter((int) Auth::id()),
        ]);
    }

    public function apiSearch(Request $request): void
    {
        try {
            $filters = $this->service->normalizeSearch($request->all());
            [$rows, $pagination] = $this->publicSearchPage($filters);
            $data = array_map(static function (array $row): array {
                return [
                    'id' => (int) $row['id'],
                    'title' => (string) $row['title'],
                    'code' => $row['code'],
                    'merchantName' => (string) $row['merchant_name'],
                    'categoryName' => (string) ($row['category_name'] ?? 'Khác'),
                    'discountType' => (string) $row['discount_type'],
                    'discountValue' => $row['discount_value'],
                    'minOrderAmount' => $row['min_order_amount'],
                    'maxDiscountAmount' => $row['max_discount_amount'],
                    'usageChannel' => (string) $row['usage_channel'],
                    'startsAt' => (string) $row['starts_at'],
                    'endsAt' => (string) $row['ends_at'],
                    'endsAtDisplay' => format_db_datetime((string) $row['ends_at']),
                    'isOfficial' => (bool) $row['is_official'],
                    'trustScore' => $row['trust_score'] !== null ? (float) $row['trust_score'] : null,
                    'totalVotes' => (int) $row['total_votes'],
                    'favoriteCount' => (int) $row['favorite_count'],
                    'url' => url('/coupons/' . (int) $row['id']),
                ];
            }, $rows);

            Response::json(['success' => true, 'data' => $data, 'filters' => $filters, 'pagination' => $pagination]);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => (bool) config('app.debug', false) ? $e->getMessage() : 'Không thể tải danh sách coupon.',
            ], 500);
        }
    }

    /**
     * @param array<string,mixed> $filters
     * @return array{0:array<int,array<string,mixed>>,1:array<string,int>}
     */
    private function publicSearchPage(array &$filters): array
    {
        // Same page size for server render, API, and navigation; do not trust a
        // client-supplied page_size that could create inconsistent page counts.
        $filters['page_size'] = 9;
        $total = $this->coupons->countPublicSearch($filters);
        $totalPages = max(1, (int) ceil($total / $filters['page_size']));
        $filters['page'] = min(max(1, (int) $filters['page']), $totalPages);

        return [
            $this->service->search($filters),
            [
                'page' => $filters['page'],
                'pageSize' => $filters['page_size'],
                'total' => $total,
                'totalPages' => $totalPages,
            ],
        ];
    }

    /** @param array<string,string> $errors @param array<string,mixed> $old */
    private function renderCreate(array $errors, array $old): void
    {
        $this->view('coupons/create', [
            'pageTitle' => 'Chia sẻ coupon',
            'errors' => $errors,
            'old' => $old,
            'categories' => $this->categories->activeAll(),
            'merchants' => $this->merchants->availableForCommunitySubmission(),
            'pageScripts' => ['/js/coupon-form.js'],
        ]);
    }
}

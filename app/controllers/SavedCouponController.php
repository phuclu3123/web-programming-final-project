<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\SavedCouponRepository;
use App\Repositories\CouponRepository;
use App\Repositories\InteractionRepository;
use App\Services\InteractionService;
use RuntimeException;
use Throwable;

final class SavedCouponController extends Controller
{
    public function save(Request $request, string $id): void
    {
        $actor = Auth::requireRole(['USER','MERCHANT']);
        $couponId = filter_var($id, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        $saved = $request->input('saved');
        if ($couponId === false || !in_array($saved, ['0','1'], true)) throw new \App\Core\HttpError(422, 'Yêu cầu lưu coupon không hợp lệ.');
        $db = Database::connection();
        try {
            $data = (new InteractionService(new InteractionRepository($db), new CouponRepository($db)))
                ->setFavorite((int)$actor['id'], (int)$couponId, $saved === '1');
            $message = $data['saved'] ? 'Đã lưu vào bộ sưu tập của bạn.' : 'Đã bỏ lưu coupon.';
            if ($request->expectsJson()) Response::json(['success'=>true,'message'=>$message,'data'=>$data]);
            Session::flash('success',$message);
        } catch (RuntimeException $error) {
            if ($request->expectsJson()) Response::json(['success'=>false,'message'=>$error->getMessage()],422);
            Session::flash('error',$error->getMessage());
        }
        // Đích cố định, không dùng URL tùy ý từ form.
        Response::redirect(url('/index.php') . '#coupons',303);
    }

    public function index(Request $request): void
    {
        Auth::requireRole(['USER', 'MERCHANT']);
        $repo = new SavedCouponRepository(Database::connection());
        $userId = (int) Auth::id();
        $rawKeyword = $request->input('q', '');
        $keyword = is_string($rawKeyword) ? trim($rawKeyword) : '';
        $keyword = mb_substr($keyword, 0, 100);
        $rawCategory = $request->input('category_id', '');
        $categoryId = is_scalar($rawCategory) && preg_match('/^[1-9][0-9]{0,10}$/', (string) $rawCategory)
            ? (int) $rawCategory : 0;
        $rawStatus = $request->input('status', 'ALL');
        $status = is_string($rawStatus) && in_array($rawStatus, ['ALL','ACTIVE','SCHEDULED','UNAVAILABLE'], true)
            ? $rawStatus : 'ALL';
        $rawSort = $request->input('sort', 'RECENT');
        $sort = $rawSort === 'EXPIRING' ? 'EXPIRING' : 'RECENT';
        $rawPage = $request->input('page', '1');
        $page = is_scalar($rawPage) && preg_match('/^[1-9][0-9]{0,5}$/', (string) $rawPage)
            ? (int) $rawPage : 1;
        $filters = ['keyword' => $keyword, 'category_id' => $categoryId, 'status' => $status, 'sort' => $sort];

        $this->view('coupons/saved', [
            'pageTitle' => 'Coupon đã lưu',
            'saved' => $repo->paginated($userId, $filters, $page),
            'totalSaved' => $repo->countForUser($userId),
            'categories' => $repo->categoriesForUser($userId),
            'filters' => $filters,
            'pageScripts' => ['/js/saved-coupons.js'],
        ]);
    }

    public function remove(Request $request, string $id): void
    {
        Auth::requireRole(['USER', 'MERCHANT']);
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }
        $couponId = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($couponId === false) {
            $this->renderError(404, 'Coupon không hợp lệ.');
            return;
        }
        $db = Database::connection();
        $repo = new InteractionRepository($db);
        $userId = (int) Auth::id();
        if (!$repo->isFavorite($userId, (int) $couponId)) {
            Session::flash('info', 'Coupon này không còn trong danh sách đã lưu.');
            Response::redirect(url('/saved-coupons'));
        }
        try {
            $service = new InteractionService($repo, new CouponRepository($db));
            $service->setFavorite($userId, (int) $couponId, false);
            Session::flash('success', 'Đã bỏ lưu coupon.');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        } catch (Throwable $e) {
            Session::flash('error', 'Không thể bỏ lưu coupon lúc này.');
        }
        Response::redirect(url('/saved-coupons'));
    }
}

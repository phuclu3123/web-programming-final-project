<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CouponRepository;
use App\Repositories\InteractionRepository;
use App\Services\InteractionService;
use RuntimeException;
use Throwable;

final class CouponInteractionController extends Controller
{
    private InteractionService $service;

    public function __construct()
    {
        $db = Database::connection();
        $this->service = new InteractionService(
            new InteractionRepository($db),
            new CouponRepository($db)
        );
    }

    public function favorite(Request $request, string $id): void
    {
        $actorId = $this->requireAjaxUser($request);
        $couponId = $this->couponId($id);
        $savedRaw = $request->input('saved', null);
        $saved = in_array((string) $savedRaw, ['1', 'true', 'on'], true);

        try {
            $data = $this->service->setFavorite($actorId, $couponId, $saved);
            Response::json([
                'success' => true,
                'message' => $data['saved'] ? 'Đã lưu coupon.' : 'Đã bỏ lưu coupon.',
                'data' => $data,
            ]);
        } catch (RuntimeException $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => (bool) config('app.debug', false) ? $e->getMessage() : 'Không thể cập nhật coupon đã lưu.',
            ], 500);
        }
    }

    public function vote(Request $request, string $id): void
    {
        $actorId = $this->requireAjaxUser($request);
        $couponId = $this->couponId($id);

        try {
            $data = $this->service->setVote($actorId, $couponId, (string) $request->input('vote', ''));
            Response::json([
                'success' => true,
                'message' => 'Đã cập nhật xác nhận coupon.',
                'data' => $data,
            ]);
        } catch (RuntimeException $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => (bool) config('app.debug', false) ? $e->getMessage() : 'Không thể cập nhật xác nhận coupon.',
            ], 500);
        }
    }

    public function comment(Request $request, string $id): void
    {
        $actorId = $this->requireAjaxUser($request);
        $couponId = $this->couponId($id);

        try {
            $comment = $this->service->addComment($actorId, $couponId, (string) $request->input('body', ''));
            Response::json([
                'success' => true,
                'message' => 'Đã gửi bình luận.',
                'data' => [
                    'id' => (int) $comment['id'],
                    'userId' => (int) $comment['user_id'],
                    'fullName' => (string) $comment['full_name'],
                    'body' => (string) $comment['body'],
                    'createdAt' => (string) $comment['created_at'],
                    'createdAtDisplay' => format_db_datetime((string) $comment['created_at']),
                ],
            ], 201);
        } catch (RuntimeException $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => (bool) config('app.debug', false) ? $e->getMessage() : 'Không thể gửi bình luận.',
            ], 500);
        }
    }

    public function report(Request $request, string $id): void
    {
        $actorId = $this->requireAjaxUser($request);
        $couponId = $this->couponId($id);

        try {
            $reportId = $this->service->createReport(
                $actorId,
                $couponId,
                (string) $request->input('reason', ''),
                (string) $request->input('details', '')
            );
            Response::json([
                'success' => true,
                'message' => 'Đã gửi báo cáo đến Admin.',
                'data' => ['reportId' => $reportId],
            ], 201);
        } catch (RuntimeException $e) {
            Response::json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Response::json([
                'success' => false,
                'message' => (bool) config('app.debug', false) ? $e->getMessage() : 'Không thể gửi báo cáo.',
            ], 500);
        }
    }

    private function requireAjaxUser(Request $request): int
    {
        if (!Auth::check() || Auth::id() === null) {
            Response::json([
                'success' => false,
                'message' => 'Bạn cần đăng nhập để thực hiện chức năng này.',
                'loginUrl' => url('/login'),
            ], 401);
        }

        $token = (string) $request->input('_token', '');
        if (!Session::verifyCsrf($token)) {
            Response::json(['success' => false, 'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.'], 419);
        }

        return (int) Auth::id();
    }

    private function couponId(string $id): int
    {
        $couponId = filter_var($id, FILTER_VALIDATE_INT);
        if ($couponId === false || (int) $couponId <= 0) {
            Response::json(['success' => false, 'message' => 'Coupon không hợp lệ.'], 404);
        }
        return (int) $couponId;
    }
}

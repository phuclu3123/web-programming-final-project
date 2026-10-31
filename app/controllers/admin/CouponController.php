<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Repositories\AdminCouponRepository;
use App\Repositories\CouponRepository;
use App\Services\AdminCouponService;
use App\Services\CouponService;
use RuntimeException;
use Throwable;

final class CouponController extends Controller
{
    private CouponRepository $coupons;
    private AdminCouponRepository $adminCoupons;
    private CouponService $service;
    private AdminCouponService $adminService;

    public function __construct()
    {
        $db = Database::connection();
        $this->coupons = new CouponRepository($db);
        $this->adminCoupons = new AdminCouponRepository($db);
        $this->service = new CouponService($this->coupons);
        $this->adminService = new AdminCouponService($this->adminCoupons, $this->service);
    }

    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $statuses = ['PENDING', 'ACTIVE', 'SCHEDULED', 'DISABLED', 'EXPIRED', 'REJECTED'];
        $rawStatus = $request->query('status', '');
        $status = is_string($rawStatus) ? strtoupper($rawStatus) : 'INVALID';
        if ($status !== '' && !in_array($status, $statuses, true)) {
            $this->renderError(422, 'Bộ lọc trạng thái không hợp lệ.');
            return;
        }
        $total = $this->adminCoupons->count($status !== '' ? $status : null);
        $pages = max(1, (int)ceil($total / 25));
        $page = filter_var($request->query('page', '1'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
        if ($page === false) {
            $this->renderError(422, 'Trang không hợp lệ.');
            return;
        }
        $page = min($pages, $page);
        $this->view('admin/coupons/index', [
            'pageTitle' => 'Quản lý và kiểm duyệt coupon',
            'coupons' => $this->adminCoupons->all($status !== '' ? $status : null, $page),
            'page' => $page, 'pages' => $pages, 'total' => $total,
            'status' => $status,
            'statuses' => $statuses,
        ]);
    }

    public function moderate(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!$this->verifyCsrf($request)) return;
        $couponId = $this->positiveId($id);
        if ($couponId === null) return;
        $action = (string)$request->input('action', '');
        $note = trim((string)$request->input('note', ''));
        try {
            $this->service->moderate((int) Auth::id(), $couponId, $action, $note !== '' ? $note : null);
            Session::flash('success', strtoupper($action) === 'APPROVE' ? 'Đã duyệt coupon.' : 'Đã từ chối coupon.');
        } catch (Throwable $e) {
            $this->flashFailure($e, 'Không thể kiểm duyệt coupon.');
        }
        Response::redirect(url('/admin/coupons'));
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        $couponId = $this->positiveId($id);
        if ($couponId === null) return;
        $coupon = $this->adminCoupons->find($couponId);
        if ($coupon === null) {
            $this->renderError(404, 'Không tìm thấy coupon.');
            return;
        }
        $this->renderEdit($coupon, []);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!$this->verifyCsrf($request)) return;
        $couponId = $this->positiveId($id);
        if ($couponId === null) return;
        try {
            $this->adminService->update((int)Auth::id(), $couponId, $request->all());
            Session::flash('success', 'Đã lưu coupon. Xác minh cộng đồng chỉ được đặt lại khi điều kiện sử dụng thay đổi.');
            Response::redirect(url('/admin/coupons'));
        } catch (ValidationException $e) {
            $coupon = $this->adminCoupons->find($couponId);
            if ($coupon === null) {
                $this->renderError(404, 'Không tìm thấy coupon.');
                return;
            }
            http_response_code(422);
            $this->renderEdit($coupon, $e->errors(), $request->all());
        } catch (Throwable $e) {
            $this->flashFailure($e, 'Không thể cập nhật coupon.');
            Response::redirect(url('/admin/coupons/' . $couponId . '/edit'));
        }
    }

    public function expire(Request $request, string $id): void
    {
        $this->changeState($request, $id, false);
    }

    public function delete(Request $request, string $id): void
    {
        $this->changeState($request, $id, true);
    }

    public function revoke(Request $request, string $id): void
    {
        $actor=Auth::requireRole(['ADMIN']);
        if (!$this->verifyCsrf($request)) return;
        $couponId=$this->positiveId($id);
        if ($couponId===null) return;
        try {
            $reason=$request->input('reason','');
            if (!is_string($reason)) throw new RuntimeException('Lý do thu hồi không hợp lệ.');
            $this->adminService->revoke((int)$actor['id'],$couponId,$reason);
            Session::flash('success','Đã thu hồi coupon và lưu người xử lý, thời điểm, lý do.');
        } catch (Throwable $error) { $this->flashFailure($error,'Không thể thu hồi coupon.'); }
        Response::redirect(url('/admin/coupons'),303);
    }

    private function changeState(Request $request, string $id, bool $delete): void
    {
        Auth::requireRole(['ADMIN']);
        if (!$this->verifyCsrf($request)) return;
        $couponId = $this->positiveId($id);
        if ($couponId === null) return;
        try {
            $reason = (string)$request->input('reason', '');
            if ($delete) $this->adminService->delete((int)Auth::id(), $couponId, $reason);
            else $this->adminService->expire((int)Auth::id(), $couponId, $reason);
            Session::flash('success', $delete ? 'Đã xóa mềm coupon và lưu lịch sử.' : 'Đã buộc hết hạn coupon.');
        } catch (Throwable $e) {
            $this->flashFailure($e, 'Không thể thay đổi trạng thái coupon.');
        }
        Response::redirect(url('/admin/coupons'));
    }

    /** @param array<string,mixed> $coupon @param array<string,string> $errors @param array<string,mixed> $old */
    private function renderEdit(array $coupon, array $errors, array $old = []): void
    {
        foreach (['starts_at', 'ends_at'] as $key) {
            if (!isset($old[$key])) {
                $old[$key] = (new \DateTimeImmutable((string)$coupon[$key], new \DateTimeZone('UTC')))
                    ->setTimezone(new \DateTimeZone((string)config('app.timezone', 'Asia/Ho_Chi_Minh')))
                    ->format('Y-m-d\TH:i:s');
            }
        }
        $this->view('admin/coupons/edit', [
            'pageTitle' => 'Sửa coupon #' . (int)$coupon['id'],
            'coupon' => $coupon,
            'categories' => $this->adminCoupons->activeCategories(),
            'errors' => $errors,
            'old' => $old,
            'pageScripts' => ['/js/coupon-form.js'],
        ]);
    }

    private function verifyCsrf(Request $request): bool
    {
        if (Session::verifyCsrf((string)$request->input('_token', ''))) return true;
        $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
        return false;
    }

    private function positiveId(string $id): ?int
    {
        $value = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($value !== false) return (int)$value;
        $this->renderError(404, 'Không tìm thấy coupon.');
        return null;
    }

    private function flashFailure(Throwable $e, string $fallback): void
    {
        if ($e instanceof \mysqli_sql_exception) error_log((string)$e);
        Session::flash('error', ($e instanceof RuntimeException && !$e instanceof \mysqli_sql_exception) || (bool)config('app.debug', false) ? $e->getMessage() : $fallback);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CouponRepository;
use App\Services\CouponService;
use RuntimeException;
use Throwable;

final class CouponController extends Controller
{
    private CouponRepository $coupons;
    private CouponService $service;

    public function __construct()
    {
        $this->coupons = new CouponRepository(Database::connection());
        $this->service = new CouponService($this->coupons);
    }

    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $this->view('admin/coupons/index', [
            'pageTitle' => 'Kiểm duyệt coupon',
            'coupons' => $this->coupons->pendingForModeration(),
        ]);
    }

    public function moderate(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }

        $couponId = filter_var($id, FILTER_VALIDATE_INT);
        $action = (string) $request->input('action', '');
        $note = trim((string) $request->input('note', ''));

        try {
            $this->service->moderate((int) Auth::id(), (int) $couponId, $action, $note !== '' ? $note : null);
            Session::flash('success', strtoupper($action) === 'APPROVE' ? 'Đã duyệt coupon.' : 'Đã từ chối coupon.');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        } catch (Throwable $e) {
            Session::flash('error', (bool) config('app.debug', false) ? $e->getMessage() : 'Không thể kiểm duyệt coupon.');
        }
        Response::redirect(url('/admin/coupons'));
    }
}

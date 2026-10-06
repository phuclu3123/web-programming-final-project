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
use App\Repositories\InteractionRepository;
use App\Services\InteractionService;
use RuntimeException;
use Throwable;

final class ReportController extends Controller
{
    private InteractionRepository $reports;
    private InteractionService $service;

    public function __construct()
    {
        $pdo = Database::connection();
        $this->reports = new InteractionRepository($pdo);
        $this->service = new InteractionService($this->reports, new CouponRepository($pdo));
    }

    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $this->view('admin/reports/index', [
            'pageTitle' => 'Báo cáo coupon',
            'reports' => $this->reports->pendingReports(),
        ]);
    }

    public function resolve(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
            return;
        }

        $reportId = filter_var($id, FILTER_VALIDATE_INT);
        $action = (string) $request->input('action', '');
        $note = (string) $request->input('note', '');

        try {
            $this->service->resolveReport((int) Auth::id(), (int) $reportId, $action, $note);
            Session::flash('success', strtoupper($action) === 'RESOLVED' ? 'Đã xử lý báo cáo.' : 'Đã bỏ qua báo cáo.');
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        } catch (Throwable $e) {
            Session::flash('error', (bool) config('app.debug', false) ? $e->getMessage() : 'Không thể xử lý báo cáo.');
        }

        Response::redirect(url('/admin/reports'));
    }
}

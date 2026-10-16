<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CatalogRepository;
use mysqli_sql_exception;

final class ReviewController extends Controller
{
    public function index(Request $request): void
    {
        $method = $request->method();
        $actor = Auth::requireRole(['ADMIN']);
        $model = new CatalogRepository(Database::connection());
        // Các đường dẫn cũ tiếp tục mở đúng trang quản trị tương ứng.
        $section = $request->query('section', 'coupons');
        $routes = ['dashboard'=>'/admin', 'users'=>'/admin/users', 'merchants'=>'/admin/merchants',
            'categories'=>'/admin/categories', 'campaigns'=>'/admin/campaigns', 'reports'=>'/admin/reports'];
        if ($section !== 'coupons') {
            if (!isset($routes[$section])) throw new HttpError(404, 'Không tìm thấy trang quản trị.');
            if ($method !== 'GET') {
                header('Allow: GET');
                throw new HttpError(405, 'Trang này chỉ hỗ trợ GET.');
            }
            Response::redirect(url($routes[$section]), 303);
        }
        $errorMessage = null;
        $reviewNote = '';
        $selectedId = positiveId($request->query('id', '0'), true);
        $status = $request->query('status', 'PENDING');
        if (!in_array($status, ['PENDING', 'APPROVED', 'REJECTED'], true)) throw new HttpError(422, 'Trạng thái không hợp lệ.');
        if ($method === 'POST') {
            Session::checkCsrf($request->post('csrf'));
            $selectedId = positiveId($request->post('coupon_id'));
            $action = $request->post('action', 'approve');
            try {
                switch ($action) {
                    case 'approve':
                        if ($request->post('review_confirm') !== '1') {
                            $errorMessage = 'Cần xác nhận đã kiểm tra thông tin mã.';
                            http_response_code(422);
                            break;
                        }
                        $model->approve((int) $actor['id'], $selectedId);
                        Session::flash('admin', 'Đã duyệt mã #' . $selectedId . '. Mã sẽ hiển thị trên trang khám phá khi đến thời gian hiệu lực.');
                        Response::redirect('admin.php?status=APPROVED&id=' . $selectedId);

                    case 'reject':
                        $reviewNote = $request->post('review_note');
                        if (textLength($reviewNote) < 10 || textLength($reviewNote) > 1000) {
                            $errorMessage = 'Lý do từ chối cần từ 10 đến 1.000 ký tự.';
                            http_response_code(422);
                            break;
                        }
                        $model->reject((int) $actor['id'], $selectedId, $reviewNote);
                        Session::flash('admin', 'Đã từ chối mã #' . $selectedId . ' và lưu lý do cho Merchant.');
                        Response::redirect('admin.php?status=REJECTED&id=' . $selectedId);

                    default:
                        throw new HttpError(422, 'Hành động kiểm duyệt không hợp lệ.');
                }
            } catch (mysqli_sql_exception $error) {
                if ($error->getCode() !== 1644) throw $error;
                $errorMessage = $error->getMessage();
                http_response_code(409);
            }
        }
        [$statRows, $coupons, $detailRows] = $model->admin((int) $actor['id'], $status, $selectedId);
        $stats = $statRows[0]; $selected = $detailRows[0] ?? null;
        if ($selectedId > 0 && !$selected && !$errorMessage) throw new HttpError(404, 'Không tìm thấy mã ưu đãi.');
        $success = Session::flash('admin'); $role = 'admin'; $title = 'Duyệt mã giảm giá';
        $demo = false;
        $this->view('admin', compact('actor', 'stats', 'coupons', 'selected', 'status', 'success', 'errorMessage', 'reviewNote', 'role', 'title', 'demo', 'section'), null);
    }
}

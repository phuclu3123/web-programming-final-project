<?php
declare(strict_types=1);

final class AdminController extends AbstractController
{
    public function handle(): void
    {
        $method = $this->requireMethod('GET', 'POST');
        $actor = Session::actor($this->db, $this->config, 'ADMIN');
        $model = new Coupon($this->db);
        $errorMessage = null;
        $reviewNote = '';
        $selectedId = positiveId($this->request->query('id', '0'), true);
        $status = $this->request->query('status', 'PENDING');
        if (!in_array($status, ['PENDING', 'APPROVED', 'REJECTED'], true)) throw new HttpError(422, 'Trạng thái không hợp lệ.');
        if ($method === 'POST') {
            Session::checkCsrf($this->request->post('csrf'));
            $selectedId = positiveId($this->request->post('coupon_id'));
            $action = $this->request->post('action', 'approve');
            try {
                switch ($action) {
                    case 'approve':
                        if ($this->request->post('review_confirm') !== '1') {
                            $errorMessage = 'Cần xác nhận đã kiểm tra thông tin mã.';
                            http_response_code(422);
                            break;
                        }
                        $model->approve((int) $actor['id'], $selectedId);
                        Session::flash('admin', 'Đã duyệt mã #' . $selectedId . '. Mã sẽ hiển thị trên trang khám phá khi đến thời gian hiệu lực.');
                        $this->redirect('admin.php?status=APPROVED&id=' . $selectedId);

                    case 'reject':
                        $reviewNote = $this->request->post('review_note');
                        if (textLength($reviewNote) < 10 || textLength($reviewNote) > 1000) {
                            $errorMessage = 'Lý do từ chối cần từ 10 đến 1.000 ký tự.';
                            http_response_code(422);
                            break;
                        }
                        $model->reject((int) $actor['id'], $selectedId, $reviewNote);
                        Session::flash('admin', 'Đã từ chối mã #' . $selectedId . ' và lưu lý do cho Merchant.');
                        $this->redirect('admin.php?status=REJECTED&id=' . $selectedId);

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
        $demo = Session::localDemo($this->config);
        $this->render('admin', compact('actor', 'stats', 'coupons', 'selected', 'status', 'success', 'errorMessage', 'reviewNote', 'role', 'title', 'demo'));
    }
}

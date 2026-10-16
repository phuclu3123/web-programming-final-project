<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CatalogRepository;
use DateTimeImmutable;
use mysqli_sql_exception;


final class ShopController extends Controller
{
    public function index(Request $request): void
    {
        $method = $request->method();
        $actor = Auth::requireRole(['MERCHANT']);
        $model = new CatalogRepository(Database::connection());
        [$merchants, $categories, $coupons, $statRows] = $model->shop((int) $actor['id']);
        $stats = $statRows[0];
        $input = [
            'merchant_id' => (string) ($merchants[0]['id'] ?? ''),
            'category_id' => (string) ($categories[0]['id'] ?? ''),
            'title' => '', 'code' => '', 'description' => '', 'terms' => '',
            'discount_type' => 'PERCENTAGE', 'discount_value' => '20',
            'min_order_amount' => '0', 'max_discount_amount' => '', 'usage_channel' => 'BOTH',
            'starts_on' => date('Y-m-d'), 'expires_on' => date('Y-m-d', strtotime('+20 days')),
        ];
        $errors = [];
        if ($method === 'POST') {
            Session::checkCsrf($request->post('csrf'));
            foreach ($input as $key => $_) $input[$key] = $request->post($key);
            $errors = $this->validate($input);
            if ($errors === []) {
                // ---- OWNER GUARD: sửa merchant_id trong request không đổi được chủ ----
                Auth::requireMerchantOwner(Database::connection(), $actor, (int) $input['merchant_id']);
                try {
                    $id = $model->create((int) $actor['id'], $input);
                    Session::flash('shop', 'Đã gửi mã #' . $id . ' để kiểm duyệt. Bạn có thể theo dõi trạng thái trong danh sách bên dưới.');
                    Response::redirect('shop.php#my-coupons');
                } catch (mysqli_sql_exception $error) {
                    if ($error->getCode() !== 1644) throw $error;
                    $errors['general'] = $error->getMessage();
                }
            }
            http_response_code(422);
        }
        $success = Session::flash('shop');
        $role = 'shop'; $title = 'Quản lý mã giảm giá'; $demo = false;
        $this->view('shop', compact('actor', 'merchants', 'categories', 'coupons', 'stats', 'input', 'errors', 'success', 'role', 'title', 'demo'), null);
    }
    private function validate(array $input): array
    {
        $errors = [];
        foreach (['merchant_id', 'category_id'] as $field) {
            if (!ctype_digit($input[$field]) || (int) $input[$field] < 1 || strlen($input[$field]) > 15) $errors[$field] = 'Vui lòng chọn giá trị hợp lệ.';
        }
        foreach (['title' => 200, 'description' => 1000, 'terms' => 1000] as $field => $max) {
            if ($input[$field] === '' || textLength($input[$field]) > $max) $errors[$field] = "Trường này cần từ 1 đến {$max} ký tự.";
        }
        if (!preg_match('/\A[A-Za-z0-9_-]{3,32}\z/', $input['code'])) $errors['code'] = 'Mã gồm 3–32 ký tự chữ, số, dấu - hoặc _.';
        if (!in_array($input['discount_type'], ['PERCENTAGE', 'FIXED_AMOUNT'], true)) $errors['discount_type'] = 'Loại giảm giá không hợp lệ.';
        foreach (['discount_value', 'min_order_amount', 'max_discount_amount'] as $field) {
            if ($field === 'max_discount_amount' && $input[$field] === '') continue;
            if (!ctype_digit($input[$field]) || strlen($input[$field]) > 10 || (float) $input[$field] > 1000000000) $errors[$field] = 'Nhập số nguyên từ 0 đến 1.000.000.000.';
        }
        if ((float) $input['discount_value'] <= 0 || ($input['discount_type'] === 'PERCENTAGE' && (float) $input['discount_value'] > 100)) $errors['discount_value'] = 'Giá trị phải dương; phần trăm không vượt quá 100.';
        if ($input['max_discount_amount'] !== '' && (float) $input['max_discount_amount'] <= 0) $errors['max_discount_amount'] = 'Mức giảm tối đa phải lớn hơn 0, hoặc để trống.';
        if (!in_array($input['usage_channel'], ['ONLINE', 'OFFLINE', 'BOTH'], true)) $errors['usage_channel'] = 'Kênh sử dụng không hợp lệ.';
        foreach (['starts_on', 'expires_on'] as $field) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $input[$field]);
            if (!$date || $date->format('Y-m-d') !== $input[$field]) $errors[$field] = 'Ngày không hợp lệ.';
        }
        if ($input['expires_on'] < $input['starts_on'] || $input['expires_on'] < date('Y-m-d')) $errors['expires_on'] = 'Ngày kết thúc không được trước ngày bắt đầu hoặc trước hôm nay.';
        return $errors;
    }
}

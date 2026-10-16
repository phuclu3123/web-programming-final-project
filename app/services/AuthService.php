<?php
declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Core\Auth;
use App\Core\Session;
use mysqli_sql_exception;

final class AuthService
{
    public function __construct(private readonly UserRepositoryInterface $users) {}

    // ---- PHẦN ĐĂNG NHẬP (ĐÃ CÓ) ----
    /** Controller truyền dữ liệu vào; service không xuất HTML hay JSON. */
    public function login(string $account, string $password, string $returnUrl = ''): array
    {
        $account = trim($account);

        if ($account === '' || $password === '') {
            return [
                'success' => false,
                'message' => 'Chưa nhập tài khoản hoặc mật khẩu.',
            ];
        }

        $user = $this->users->findByLogin($account);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            return [
                'success' => false,
                'message' => 'Tài khoản hoặc mật khẩu không đúng.',
            ];
        }

        if ($user['status'] !== 'ACTIVE' || $user['deleted_at'] !== null) {
            return [
                'success' => false,
                'message' => 'Tài khoản hiện không thể đăng nhập.',
            ];
        }

        // ---- SESSION/REDIRECT: ghi phiên tập trung và chọn trang theo quyền thật ----
        if (!in_array($user['role'], ['USER', 'MERCHANT', 'ADMIN'], true)) {
            return ['success' => false, 'message' => 'Vai trò tài khoản không hợp lệ.'];
        }
        Session::establish($user);

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'redirect' => Auth::safeReturn($returnUrl, $user['role']),
        ];
    }
    // ---- PHẦN ĐĂNG KÝ (BỔ SUNG): kiểm tra ở server trước khi INSERT ----
    public function register(array $input): array
    {
        // Chỉ lấy các trường cho phép. role/status gửi từ client sẽ bị bỏ qua.
        $data = [];
        foreach (['full_name', 'username', 'email', 'phone', 'password', 'password_confirmation'] as $field) {
            $value = $input[$field] ?? '';
            if (!is_string($value) || preg_match('//u', $value) !== 1) {
                return ['success' => false, 'message' => 'Dữ liệu đăng ký không hợp lệ.'];
            }
            // Mật khẩu giữ nguyên, kể cả khoảng trắng đầu/cuối.
            $data[$field] = str_starts_with($field, 'password') ? $value : trim($value);
        }

        $errors = [];
        $length = static fn (string $value): int => function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8') : (int) preg_match_all('/./us', $value);

        if ($data['full_name'] === '' || $length($data['full_name']) > 120) {
            $errors['full_name'] = 'Họ và tên bắt buộc, tối đa 120 ký tự.';
        }
        // Không cho @ trong username để không nhầm username với email người khác.
        if (!preg_match('/\A[A-Za-z0-9_.-]{1,50}\z/', $data['username'])) {
            $errors['username'] = 'Tên tài khoản gồm 1–50 chữ không dấu, số, dấu chấm, gạch dưới hoặc gạch ngang.';
        }
        if ($length($data['email']) > 191 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Địa chỉ email không hợp lệ hoặc dài quá 191 ký tự.';
        }
        $digits = preg_replace('/\D/', '', $data['phone']);
        if (strlen($data['phone']) > 20 || !preg_match('/\A\+?[0-9][0-9 ()-]*\z/', $data['phone'])
            || strlen($digits) < 8 || strlen($digits) > 15) {
            $errors['phone'] = 'Số điện thoại cần có 8–15 chữ số, tối đa 20 ký tự.';
        }

        $password = $data['password'];
        // Bcrypt trên PHP hiện tại chỉ xử lý tối đa 72 byte; không cắt mật khẩu.
        if ($length($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")
            || !preg_match('/\p{Lu}/u', $password) || !preg_match('/\p{Ll}/u', $password)
            || !preg_match('/[0-9]/', $password) || !preg_match('/[^\p{L}\p{N}\s]/u', $password)) {
            $errors['password'] = 'Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường, số, ký tự đặc biệt và tối đa 72 byte.';
        }
        if ($data['password_confirmation'] === '' || $data['password_confirmation'] !== $password) {
            $errors['password_confirmation'] = 'Mật khẩu nhập lại chưa khớp.';
        }
        if ($errors !== []) {
            return ['success' => false, 'message' => implode(' ', $errors), 'errors' => $errors];
        }

        // password_hash thêm salt tự động. Không lưu/echo mật khẩu nguyên văn.
        $data['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        unset($data['password'], $data['password_confirmation']);
        try {
            $this->users->create($data);
        } catch (mysqli_sql_exception $error) {
            // Procedure kiểm tra trùng trước; UNIQUE xử lý cả hai request đồng thời.
            if ($error->getCode() === 1644 && $error->getMessage() === 'USERNAME_EXISTS') {
                return ['success' => false, 'message' => 'Tên tài khoản đã được sử dụng.',
                    'errors' => ['username' => 'Tên tài khoản đã được sử dụng.']];
            }
            if ($error->getCode() === 1644 && $error->getMessage() === 'EMAIL_EXISTS') {
                return ['success' => false, 'message' => 'Email đã được sử dụng.',
                    'errors' => ['email' => 'Email đã được sử dụng.']];
            }
            if ($error->getCode() === 1062) {
                return ['success' => false, 'message' => 'Email hoặc tên tài khoản đã được sử dụng.'];
            }
            throw $error; // Lỗi kết nối/procedure khác do endpoint ghi log và trả 500.
        }

        // Không tự tạo session khi đăng ký; chuyển về đăng nhập để thử tài khoản mới.
        return ['success' => true, 'message' => 'Tạo tài khoản thành công. Bạn có thể đăng nhập ngay.'];
    }
}

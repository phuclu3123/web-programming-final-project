<?php
declare(strict_types=1);

// ---- KIỂM TRA CODE BẰNG DATABASE GIẢ: không kết nối hay INSERT vào MySQL ----
require_once __DIR__ . '/../app/bootstrap.php';

use App\Contracts\UserRepositoryInterface;
use App\Services\AuthService;

// Repository giả thực hiện cùng hợp đồng với UserRepository, không cần MySQL.
final class FakeUserRepository implements UserRepositoryInterface
{
    public array $calls = [];
    public ?array $user = null;
    public ?mysqli_sql_exception $failure = null;

    public function create(array $data): int
    {
        $parameters = [$data['full_name'], $data['username'], $data['email'], $data['phone'], $data['password_hash']];
        $this->calls[] = ['create', $parameters];
        if ($this->failure) throw $this->failure;
        $this->user = $data + ['id'=>42, 'role'=>'USER', 'status'=>'ACTIVE', 'deleted_at'=>null];
        return 42;
    }

    public function findByLogin(string $account): ?array
    {
        $this->calls[] = ['findByLogin', [$account]];
        return $this->user && in_array($account, [$this->user['email'], $this->user['username']], true)
            ? $this->user : null;
    }
}

function check(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
}

$db = new FakeUserRepository();
$service = new AuthService($db);
$valid = ['full_name' => ' Người dùng thử ', 'username' => ' demo_test ', 'email' => 'demo@example.com',
    'phone' => '+84901234567', 'password' => ' Demo123! ', 'password_confirmation' => ' Demo123! '];

// Dữ liệu sai phải bị chặn trước khi model gọi ch_create_user.
foreach (['full_name', 'username', 'email', 'phone', 'password', 'password_confirmation'] as $field) {
    $invalid = $valid;
    $invalid[$field] = '';
    check(!$service->register($invalid)['success'], 'required ' . $field);
}
foreach (['username' => 'demo@example.com', 'email' => 'invalid', 'phone' => 'abc',
    'password_confirmation' => 'Mismatch1!'] as $field => $value) {
    $invalid = $valid;
    $invalid[$field] = $value;
    check(!$service->register($invalid)['success'], 'invalid ' . $field);
}
foreach (['Ab1!', 'abcdef1!', 'ABCDEF1!', 'Abcdefg!', 'Abcdef12', str_repeat('A', 73) . 'b1!', "Abcd1!ef\0"] as $password) {
    $invalid = $valid;
    $invalid['password'] = $invalid['password_confirmation'] = $password;
    check(!$service->register($invalid)['success'], 'weak/long/null password');
}
foreach (['full_name' => str_repeat('a', 121), 'username' => str_repeat('a', 51),
    'phone' => str_repeat('1', 21), 'email' => str_repeat('a', 192) . '@example.com'] as $field => $value) {
    $invalid = $valid;
    $invalid[$field] = $value;
    check(!$service->register($invalid)['success'], 'field length ' . $field);
}
$invalid = $valid;
$invalid['username'] = ['array'];
check(!$service->register($invalid)['success'], 'array input');
check($db->calls === [], 'invalid data must never reach database');

// Các lỗi trùng do procedure/UNIQUE phải thành thông báo, không tạo tài khoản.
foreach ([['USERNAME_EXISTS', 1644], ['EMAIL_EXISTS', 1644], ['Duplicate entry', 1062]] as [$message, $code]) {
    $db->failure = new mysqli_sql_exception($message, $code);
    check(!$service->register($valid)['success'], 'duplicate handled');
}
$db->failure = null;
$valid['role'] = 'ADMIN';
check($service->register($valid)['success'], 'register success');
check($db->user['username'] === 'demo_test', 'username trimmed');
check($db->user['password_hash'] !== $valid['password'], 'password stored as hash');
check(password_verify($valid['password'], $db->user['password_hash']), 'password whitespace preserved');
check(count($db->calls[array_key_last($db->calls)][1]) === 5, 'only five allowed parameters');
check($db->user['role'] === 'USER', 'role cannot come from request');
check(session_status() !== PHP_SESSION_ACTIVE, 'register does not log user in');

// Tài khoản vừa tạo đăng nhập được bằng cả email và username; sai/khóa/xóa bị chặn.
check(!$service->login('demo_test', 'wrong')['success'], 'wrong login password');
$db->user['status'] = 'LOCKED';
check(!$service->login('demo_test', $valid['password'])['success'], 'locked login');
$db->user['status'] = 'ACTIVE';
$db->user['deleted_at'] = '2026-01-01';
check(!$service->login('demo_test', $valid['password'])['success'], 'deleted login');
$db->user['deleted_at'] = null;
session_save_path(sys_get_temp_dir());
check($service->login('demo_test', $valid['password'])['success'], 'username login');
$previousId = session_id();
check($service->login('demo@example.com', $valid['password'])['success'], 'email login');
check(session_id() !== $previousId, 'session ID rotated');
check($_SESSION['user_id'] === 42, 'session user ID');
session_destroy();
echo "PASS: registration validation, hash, duplicates, model parameters, login and session (fake DB only).\n";

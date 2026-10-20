<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\UserProfileRepositoryInterface;
use RuntimeException;

/** Account business rules independent from HTTP, templates and route ownership. */
final class AccountService
{
    public function __construct(private readonly UserProfileRepositoryInterface $users) {}

    /** @return array<string,string> */
    public static function validateProfile(mixed $fullName, mixed $phone): array
    {
        $errors = [];
        if (!is_string($fullName) || preg_match('//u', $fullName) !== 1
            || trim($fullName) === '' || (function_exists('mb_strlen') ? mb_strlen(trim($fullName), 'UTF-8') : preg_match_all('/./us', trim($fullName))) > 120) {
            $errors['full_name'] = 'Họ và tên bắt buộc, tối đa 120 ký tự.';
        }
        if (!is_string($phone) || preg_match('//u', $phone) !== 1
            || strlen(trim($phone)) > 20 || !preg_match('/\A\+?[0-9][0-9 ()-]*\z/', trim($phone))) {
            $errors['phone'] = 'Số điện thoại không hợp lệ.';
        } elseif (strlen(preg_replace('/\D/', '', $phone)) < 8 || strlen(preg_replace('/\D/', '', $phone)) > 15) {
            $errors['phone'] = 'Số điện thoại phải gồm 8–15 chữ số.';
        }
        return $errors;
    }

    public function updateProfile(int $userId, mixed $fullName, mixed $phone): void
    {
        $errors = self::validateProfile($fullName, $phone);
        if ($errors) throw new RuntimeException(implode(' ', $errors));
        if (!$this->users->updateProfile($userId, trim($fullName), trim($phone))) {
            throw new RuntimeException('Tài khoản không còn hoạt động.');
        }
    }

    public static function validateNewPassword(mixed $password): bool
    {
        if (!is_string($password) || preg_match('//u', $password) !== 1) return false;
        $length = function_exists('mb_strlen') ? mb_strlen($password, 'UTF-8') : preg_match_all('/./us', $password);
        return $length >= 8 && strlen($password) <= 72 && !str_contains($password, "\0")
            && preg_match('/\p{Lu}/u', $password) && preg_match('/\p{Ll}/u', $password)
            && preg_match('/[0-9]/', $password) && preg_match('/[^\p{L}\p{N}\s]/u', $password);
    }

    public function changePassword(int $userId, mixed $current, mixed $next, mixed $confirm): void
    {
        if (!is_string($current) || $current === '' || !is_string($next) || !is_string($confirm)) {
            throw new RuntimeException('Vui lòng nhập đầy đủ thông tin mật khẩu.');
        }
        $hash = $this->users->passwordHashForUser($userId);
        if (!$hash || !password_verify($current, $hash)) {
            throw new RuntimeException('Mật khẩu hiện tại không đúng.');
        }
        if (!self::validateNewPassword($next)) {
            throw new RuntimeException('Mật khẩu mới cần 8 ký tự trở lên, gồm chữ hoa, chữ thường, số và ký tự đặc biệt; tối đa 72 byte.');
        }
        if (!hash_equals($next, $confirm)) {
            throw new RuntimeException('Mật khẩu xác nhận không trùng khớp.');
        }
        if (password_verify($next, $hash)) {
            throw new RuntimeException('Mật khẩu mới phải khác mật khẩu cũ.');
        }
        if (!$this->users->changePassword($userId, $hash, password_hash($next, PASSWORD_DEFAULT))) {
            throw new RuntimeException('Mật khẩu vừa được cập nhật ở phiên khác. Vui lòng thử lại.');
        }
    }
}

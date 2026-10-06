<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\UserRepository;
use RuntimeException;

final class AuthService
{
    public function __construct(private UserRepository $users)
    {
    }

    /** @return array<string,mixed> */
    public function register(string $fullName, string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $fullName = trim($fullName);

        if ($this->users->emailExists($email)) {
            throw new RuntimeException('Email này đã được sử dụng.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException('Không thể tạo mật khẩu an toàn.');
        }

        $id = $this->users->createUser($email, $hash, $fullName);
        $user = $this->users->findById($id);
        if ($user === null) {
            throw new RuntimeException('Không thể tải tài khoản vừa tạo.');
        }

        return $user;
    }

    /** @return array<string,mixed> */
    public function login(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $user = $this->users->findByEmail($email);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            throw new RuntimeException('Email hoặc mật khẩu không chính xác.');
        }

        if ((string) $user['status'] !== 'ACTIVE') {
            throw new RuntimeException('Tài khoản đang bị khóa.');
        }

        Auth::login($user);
        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }
}

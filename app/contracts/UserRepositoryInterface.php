<?php
declare(strict_types=1);

namespace App\Contracts;

interface UserRepositoryInterface
{
    // ---- PHẦN ĐĂNG NHẬP: tìm tài khoản theo email hoặc username ----
    public function findByLogin(string $account): ?array;

    // ---- PHẦN ĐĂNG KÝ (BỔ SUNG): nhận dữ liệu đã kiểm tra và hash, trả ID mới ----
    public function create(array $data): int;
}

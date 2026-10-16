<?php
declare(strict_types=1);

namespace App\Contracts;

/**
 * Hợp đồng gọi stored procedure cho repository và guard.
 * Có thể thay bằng dữ liệu giả trong kiểm tra mà không mở MySQL.
 */
interface RoutineGateway
{
    /** @return array<int, array<int, array<string, mixed>>> */
    public function call(string $routine, array $parameters = []): array;
}

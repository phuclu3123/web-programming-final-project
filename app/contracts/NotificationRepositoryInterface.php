<?php
declare(strict_types=1);
namespace App\Contracts;

// Đọc thông báo theo actor; không cho client tự chọn người nhận.
// Đọc thông báo theo actor; không cho client tự chọn người nhận.
interface NotificationRepositoryInterface
{
    public function forActor(array $actor): array;
}

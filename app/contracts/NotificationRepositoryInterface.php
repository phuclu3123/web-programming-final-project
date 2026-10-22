<?php
declare(strict_types=1);
namespace App\Contracts;

interface NotificationRepositoryInterface
{
    public function forActor(array $actor): array;
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\AdminRepositoryInterface;
use App\Contracts\CampaignRepositoryInterface;
use RuntimeException;

final class AdminService
{
    public function __construct(private AdminRepositoryInterface $admin, private CampaignRepositoryInterface $campaigns) {}

    public function setUserStatus(int $actorId, int $userId, string $status): void
    {
        $this->admin->setUserStatus($actorId, $userId, strtoupper($status));
    }

    public function reviewMerchant(int $actorId, int $merchantId, string $action, ?string $note): void
    {
        $this->admin->reviewMerchant($actorId, $merchantId, strtoupper($action), $note);
    }

    /** @param array<string,mixed> $input */
    public function addCategory(int $actorId, array $input): void
    {
        $name = trim((string) ($input['name'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) throw new RuntimeException('Tên danh mục là bắt buộc và tối đa 100 ký tự.');
        $slug = slugify($name);
        if ($slug === '') throw new RuntimeException('Không thể tạo slug danh mục.');
        $this->admin->addCategory($actorId, $name, $slug, $description !== '' ? $description : null);
    }
}

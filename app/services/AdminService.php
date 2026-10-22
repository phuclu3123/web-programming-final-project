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
    /** @param array<string,mixed> $input */
    public function updateCategory(int $actorId, int $categoryId, array $input): void
    {
        if ($actorId <= 0 || $categoryId <= 0) throw new RuntimeException('Danh mục không hợp lệ.');
        foreach (['name','description','sort_order'] as $field) {
            if (isset($input[$field]) && (!is_scalar($input[$field]) || !mb_check_encoding((string)$input[$field], 'UTF-8'))) {
                throw new RuntimeException('Thông tin danh mục không hợp lệ.');
            }
        }
        $name = trim((string)($input['name'] ?? ''));
        $description = trim((string)($input['description'] ?? ''));
        $sortRaw = trim((string)($input['sort_order'] ?? '0'));
        if ($name === '' || mb_strlen($name) > 100) throw new RuntimeException('Tên danh mục cần từ 1 đến 100 ký tự.');
        if (mb_strlen($description) > 2000) throw new RuntimeException('Mô tả tối đa 2.000 ký tự.');
        if (!preg_match('/^\d{1,5}$/', $sortRaw)) throw new RuntimeException('Thứ tự danh mục phải từ 0 đến 99999.');
        $slug = slugify($name);
        if ($slug === '' || strlen($slug) > 191) throw new RuntimeException('Không thể tạo slug danh mục hợp lệ.');
        $this->admin->updateCategory($actorId, $categoryId, $name, $slug, $description !== '' ? $description : null, (int)$sortRaw);
    }

    public function deleteCategory(int $actorId, int $categoryId): void
    {
        if ($actorId <= 0 || $categoryId <= 0) throw new RuntimeException('Danh mục không hợp lệ.');
        $this->admin->deleteUnusedCategory($actorId, $categoryId);
    }

}

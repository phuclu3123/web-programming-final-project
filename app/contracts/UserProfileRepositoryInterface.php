<?php
declare(strict_types=1);
namespace App\Contracts;


/** Truy vấn hồ sơ/điểm và email; kế thừa hai thao tác Auth để dùng chung UserRepository. */
interface UserProfileRepositoryInterface extends UserRepositoryInterface
{
    public function findByEmail(string $email): ?array;
    public function updateProfile(int $userId, string $name, string $phone): bool;
    public function passwordHashForUser(int $userId): ?string;
    public function changePassword(int $userId, string $oldHash, string $newHash): bool;
    public function findById(int $id): ?array;
    public function emailExists(string $email): bool;
    public function rewardSummary(int $userId): array;
    public function pointHistory(int $userId, int $limit = 10): array;
}

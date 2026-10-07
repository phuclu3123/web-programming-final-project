<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Merchant;
use PDO;
use RuntimeException;

final class MerchantRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return Merchant[] */
    public function availableForCommunitySubmission(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, slug, status FROM merchants " .
            "WHERE deleted_at IS NULL AND status IN ('UNCLAIMED','PENDING','APPROVED') ORDER BY name ASC"
        );
        return array_map(static fn(array $row): Merchant => Merchant::fromArray($row), $stmt->fetchAll());
    }

    /** @return Merchant[] */
    public function publicFilterOptions(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT m.id, m.name, m.slug, m.status FROM merchants m " .
            "JOIN v_coupon_status c ON c.merchant_id = m.id " .
            "WHERE c.effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci ORDER BY m.name ASC"
        );
        return array_map(static fn(array $row): Merchant => Merchant::fromArray($row), $stmt->fetchAll());
    }

    /** @return array<int,array<string,mixed>> */
    public function claimable(): array
    {
        return $this->pdo->query(
            "SELECT id,name,slug,website_url FROM merchants WHERE owner_user_id IS NULL " .
            "AND status='UNCLAIMED' AND deleted_at IS NULL ORDER BY name ASC"
        )->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function applicationsByUser(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id,name,slug,status,review_note,created_at FROM merchants " .
            "WHERE owner_user_id=? AND deleted_at IS NULL ORDER BY created_at DESC,id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function ownedApproved(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id,name,slug,description,website_url,logo_path,status,created_at,updated_at " .
            "FROM merchants WHERE owner_user_id=? AND status='APPROVED' AND deleted_at IS NULL ORDER BY name"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findOwnedApproved(int $merchantId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id,name,slug,description,website_url,logo_path,status,created_at,updated_at " .
            "FROM merchants WHERE id=? AND owner_user_id=? AND status='APPROVED' AND deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$merchantId, $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function requestClaim(int $userId, int $merchantId): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT owner_user_id,status FROM merchants WHERE id=? AND deleted_at IS NULL FOR UPDATE');
            $stmt->execute([$merchantId]);
            $row = $stmt->fetch();
            if (!is_array($row) || $row['owner_user_id'] !== null || (string) $row['status'] !== 'UNCLAIMED') {
                throw new RuntimeException('Merchant này không còn ở trạng thái có thể yêu cầu sở hữu.');
            }
            $update = $this->pdo->prepare("UPDATE merchants SET owner_user_id=?,status='PENDING',reviewed_by=NULL,reviewed_at=NULL,review_note=NULL WHERE id=?");
            $update->execute([$userId, $merchantId]);
            $log = $this->pdo->prepare("INSERT INTO activity_logs(user_id,action,description) VALUES(?, 'MERCHANT_CLAIM_REQUEST', ?)");
            $log->execute([$userId, 'Merchant #' . $merchantId]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function requestNew(int $userId, string $name, string $slug, ?string $description, ?string $websiteUrl): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO merchants(owner_user_id,name,slug,description,website_url,status) VALUES(?,?,?,?,?,'PENDING')"
        );
        $stmt->execute([$userId, $name, $slug, $description, $websiteUrl]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateProfile(int $merchantId, int $userId, string $name, ?string $description, ?string $websiteUrl): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE merchants SET name=?,description=?,website_url=? WHERE id=? AND owner_user_id=? AND status='APPROVED' AND deleted_at IS NULL"
        );
        $stmt->execute([$name, $description, $websiteUrl, $merchantId, $userId]);
        if ($stmt->rowCount() === 0 && $this->findOwnedApproved($merchantId, $userId) === null) {
            throw new RuntimeException('Không tìm thấy Merchant thuộc quyền quản lý của bạn.');
        }
    }

    /** @return array<string,int> */
    public function stats(int $actorId, int $merchantId): array
    {
        $stmt = $this->pdo->prepare('CALL sp_merchant_stats(?, ?)');
        $stmt->execute([$actorId, $merchantId]);
        $coupon = $stmt->fetch() ?: [];
        $stmt->nextRowset();
        $events = $stmt->fetch() ?: [];
        $stmt->nextRowset();
        $favorites = $stmt->fetch() ?: [];
        while ($stmt->nextRowset()) {}
        $stmt->closeCursor();
        return [
            'total_coupons' => (int) ($coupon['total_coupons'] ?? 0),
            'active_coupons' => (int) ($coupon['active_coupons'] ?? 0),
            'expiring_soon' => (int) ($coupon['expiring_soon'] ?? 0),
            'total_views' => (int) ($events['total_views'] ?? 0),
            'total_copies' => (int) ($events['total_copies'] ?? 0),
            'total_shares' => (int) ($events['total_shares'] ?? 0),
            'current_favorites' => (int) ($favorites['current_favorites'] ?? 0),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function popular(int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));
        $sql = "SELECT m.id,m.name,m.slug,COUNT(*) AS active_coupon_count " .
            "FROM merchants m JOIN v_coupon_status c ON c.merchant_id=m.id " .
            "WHERE m.deleted_at IS NULL AND c.effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci " .
            "GROUP BY m.id,m.name,m.slug ORDER BY active_coupon_count DESC,m.name ASC LIMIT " . $limit;
        return $this->pdo->query($sql)->fetchAll();
    }
}

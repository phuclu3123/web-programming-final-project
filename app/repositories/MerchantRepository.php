<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Merchant;
use App\Core\Database;
use RuntimeException;

final class MerchantRepository implements \App\Contracts\MerchantRepositoryInterface
{
    public function __construct(private Database $db) {}

    /** @return Merchant[] */
    public function availableForCommunitySubmission(): array
    {
        $stmt = $this->db->query(
            "SELECT id, name, slug, status FROM merchants " .
                "WHERE deleted_at IS NULL AND status IN ('UNCLAIMED','PENDING','APPROVED') ORDER BY name ASC"
        );
        return array_map(static fn(array $row): Merchant => Merchant::fromArray($row), $stmt->fetchAll());
    }

    /** @return Merchant[] */
    public function publicFilterOptions(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT m.id, m.name, m.slug, m.status FROM merchants m " .
                "JOIN v_coupon_status c ON c.merchant_id = m.id " .
                "WHERE c.effective_status COLLATE utf8mb4_vietnamese_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci ORDER BY m.name ASC"
        );
        return array_map(static fn(array $row): Merchant => Merchant::fromArray($row), $stmt->fetchAll());
    }

    /** @return array<int,array<string,mixed>> */
    public function claimable(): array
    {
        return $this->db->query("SELECT id,name,slug,website_url,status FROM merchants WHERE status IN ('UNCLAIMED','APPROVED') AND deleted_at IS NULL ORDER BY name ASC")->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function applicationsByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT r.id,r.request_type,r.status,r.review_note,r.created_at,m.id AS merchant_id,m.name,m.slug FROM merchant_requests r JOIN merchants m ON m.id=r.merchant_id WHERE r.user_id=? ORDER BY r.created_at DESC,r.id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function ownedApproved(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT m.id,m.name,m.slug,m.description,m.website_url,m.logo_path,m.status,m.created_at,m.updated_at FROM merchants m JOIN merchant_memberships mm ON mm.merchant_id=m.id WHERE mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL ORDER BY m.name");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findOwnedApproved(int $merchantId, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT m.id,m.name,m.slug,m.description,m.website_url,m.logo_path,m.status,m.created_at,m.updated_at FROM merchants m JOIN merchant_memberships mm ON mm.merchant_id=m.id WHERE m.id=? AND mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$merchantId, $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** Reuses a registration transaction only for legacy callers. All new registrations use /merchant/apply. */
    public function requestClaim(int $userId, int $merchantId, ?string $evidence = null): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT id,status FROM merchants WHERE id=? AND deleted_at IS NULL FOR UPDATE");
            $stmt->execute([$merchantId]);
            $shop = $stmt->fetch();
            if (!is_array($shop) || !in_array($shop['status'], ['APPROVED', 'UNCLAIMED'], true)) throw new RuntimeException('Shop không thể nhận yêu cầu quản lý.');
            $check = $this->db->prepare("SELECT 1 FROM merchant_memberships WHERE merchant_id=? AND user_id=? AND status='ACTIVE' LIMIT 1");
            $check->execute([$merchantId, $userId]);
            if ($check->fetchColumn()) throw new RuntimeException('Bạn đang quản lý Shop này.');
            $check = $this->db->prepare("SELECT 1 FROM merchant_requests WHERE merchant_id=? AND user_id=? AND status='PENDING' LIMIT 1");
            $check->execute([$merchantId, $userId]);
            if ($check->fetchColumn()) throw new RuntimeException('Bạn đã gửi đơn đăng ký Shop này, vui lòng chờ duyệt.');
            $stmt = $this->db->prepare("INSERT INTO merchant_requests(user_id,merchant_id,request_type,evidence_reference) VALUES(?,?,'JOIN',?)");
            $stmt->execute([$userId, $merchantId, $evidence]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function requestNew(int $userId, string $name, string $slug, ?string $description, ?string $websiteUrl, ?string $evidence = null): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO merchants(owner_user_id,name,slug,description,website_url,status) VALUES(NULL,?,?,?,?,'PENDING')");
            $stmt->execute([$name, $slug, $description, $websiteUrl]);
            $id = (int)$this->db->lastInsertId();
            $request = $this->db->prepare("INSERT INTO merchant_requests(user_id,merchant_id,request_type,evidence_reference) VALUES(?,?,'CREATE',?)");
            $request->execute([$userId, $id, $evidence]);
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function updateProfile(int $merchantId, int $userId, string $name, ?string $description, ?string $websiteUrl): void
    {
        if ($this->findOwnedApproved($merchantId, $userId) === null) throw new RuntimeException('Bạn không có quyền quản lý Shop này.');
        $stmt = $this->db->prepare('UPDATE merchants SET name=?,description=?,website_url=? WHERE id=?');
        $stmt->execute([$name, $description, $websiteUrl, $merchantId]);
    }

    /** @return array<string,int> */
    public function stats(int $actorId, int $merchantId): array
    {
        $stmt = $this->db->prepare('CALL sp_merchant_stats(?, ?)');
        $stmt->execute([$actorId, $merchantId]);
        $coupon = $stmt->fetch() ?: [];
        $stmt->nextRowset();
        $events = $stmt->fetch() ?: [];
        $stmt->nextRowset();
        $favorites = $stmt->fetch() ?: [];
        while ($stmt->nextRowset()) {
        }
        $stmt->closeCursor();
        return [
            'total_coupons' => (int) ($coupon['total_coupons'] ?? 0),
            'active_coupons' => (int) ($coupon['active_coupons'] ?? 0),
            'expiring_soon' => (int) ($coupon['expiring_soon'] ?? 0),
            'total_views' => (int) ($events['total_views'] ?? 0),
            'total_copies' => (int) ($events['total_copies'] ?? 0),
            'total_shares' => (int) ($events['total_shares'] ?? 0),
            'current_favorites' => (int) ($favorites['current_favorites'] ?? 0),
            'qualified_favorites' => (int) ($favorites['qualified_favorites'] ?? 0),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function popular(int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));
        $sql = "SELECT m.id,m.name,m.slug,COUNT(*) AS active_coupon_count " .
            "FROM merchants m JOIN v_coupon_status c ON c.merchant_id=m.id " .
            "WHERE m.deleted_at IS NULL AND c.effective_status COLLATE utf8mb4_vietnamese_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci " .
            "GROUP BY m.id,m.name,m.slug ORDER BY active_coupon_count DESC,m.name ASC LIMIT " . $limit;
        return $this->db->query($sql)->fetchAll();
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;

final class CampaignRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int,array<string,mixed>> */
    public function publicCurrent(int $limit = 3): array
    {
        $limit = max(1, min(12, $limit));
        $sql = "SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id " .
            "WHERE c.status='PUBLISHED' AND c.starts_at<=UTC_TIMESTAMP() AND c.ends_at>UTC_TIMESTAMP() " .
            "ORDER BY c.starts_at DESC,c.id DESC LIMIT " . $limit;
        return $this->pdo->query($sql)->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function publicUpcoming(int $limit = 4): array
    {
        $limit = max(1, min(12, $limit));
        $sql = "SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id " .
            "WHERE c.status='PUBLISHED' AND c.starts_at>UTC_TIMESTAMP() " .
            "ORDER BY c.starts_at ASC,c.id ASC LIMIT " . $limit;
        return $this->pdo->query($sql)->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findPublic(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id " .
            "WHERE c.id=? AND c.status='PUBLISHED' AND c.ends_at>UTC_TIMESTAMP() LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int,array<string,mixed>> */
    public function publicCoupons(int $campaignId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id,c.title,c.code,c.discount_type,c.discount_value,c.cashback_unit,c.min_order_amount,c.max_discount_amount,c.usage_channel,c.ends_at, " .
            "m.name AS merchant_name,cat.name AS category_name,s.trust_score,s.total_votes, " .
            "(SELECT COUNT(*) FROM favorites f WHERE f.coupon_id=c.id) AS favorite_count " .
            "FROM campaign_coupons cc JOIN v_coupon_status c ON c.id=cc.coupon_id " .
            "JOIN merchants m ON m.id=c.merchant_id JOIN categories cat ON cat.id=c.category_id " .
            "JOIN v_coupon_vote_stats s ON s.coupon_id=c.id " .
            "WHERE cc.campaign_id=? AND c.effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci " .
            "ORDER BY c.created_at DESC,c.id DESC"
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findAdmin(int $campaignId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id WHERE c.id=? LIMIT 1"
        );
        $stmt->execute([$campaignId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int,array<string,mixed>> */
    public function allActiveCoupons(): array
    {
        return $this->pdo->query(
            "SELECT c.id,c.title,c.code,c.merchant_id,c.starts_at,c.ends_at,m.name AS merchant_name FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id " .
            "WHERE c.effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci ORDER BY m.name,c.title"
        )->fetchAll();
    }

    public function detachAdmin(int $campaignId, int $couponId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM campaign_coupons WHERE campaign_id=? AND coupon_id=?');
        $stmt->execute([$campaignId, $couponId]);
    }

    /** @return array<int,array<string,mixed>> */
    public function merchantCampaigns(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*,m.name AS merchant_name,(SELECT COUNT(*) FROM campaign_coupons cc WHERE cc.campaign_id=c.id) AS coupon_count " .
            "FROM campaigns c JOIN merchants m ON m.id=c.owner_merchant_id " .
            "WHERE m.owner_user_id=? AND m.status='APPROVED' AND m.deleted_at IS NULL ORDER BY c.created_at DESC,c.id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findOwned(int $campaignId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*,m.name AS merchant_name FROM campaigns c JOIN merchants m ON m.id=c.owner_merchant_id " .
            "WHERE c.id=? AND m.owner_user_id=? AND m.status='APPROVED' AND m.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$campaignId, $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(int $actorId, ?int $merchantId, string $name, string $slug, ?string $description, string $startsAtUtc, string $endsAtUtc, string $status): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO campaigns(owner_merchant_id,created_by,name,slug,description,starts_at,ends_at,status) VALUES(?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$merchantId, $actorId, $name, $slug, $description, $startsAtUtc, $endsAtUtc, $status]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateOwned(int $campaignId, int $userId, string $name, ?string $description, string $startsAtUtc, string $endsAtUtc, string $status): void
    {
        $campaign = $this->findOwned($campaignId, $userId);
        if ($campaign === null) throw new RuntimeException('Không tìm thấy Campaign thuộc Merchant của bạn.');

        // Every attached coupon must still overlap the edited Campaign window.
        $invalid = $this->pdo->prepare(
            "SELECT COUNT(*) FROM campaign_coupons cc JOIN coupons c ON c.id=cc.coupon_id " .
            "WHERE cc.campaign_id=? AND NOT (c.starts_at<? AND c.ends_at>?)"
        );
        $invalid->execute([$campaignId, $endsAtUtc, $startsAtUtc]);
        if ((int) $invalid->fetchColumn() > 0) {
            throw new RuntimeException('Khoảng thời gian mới không còn giao với một hoặc nhiều coupon đã gắn. Hãy gỡ coupon đó trước khi sửa thời gian Campaign.');
        }

        $stmt = $this->pdo->prepare('UPDATE campaigns SET name=?,description=?,starts_at=?,ends_at=?,status=? WHERE id=?');
        $stmt->execute([$name, $description, $startsAtUtc, $endsAtUtc, $status, $campaignId]);
    }

    public function attach(int $actorId, int $campaignId, int $couponId): void
    {
        $stmt = $this->pdo->prepare('CALL sp_attach_campaign_coupon(?, ?, ?)');
        $stmt->execute([$actorId, $campaignId, $couponId]);
        $stmt->closeCursor();
    }

    public function detachOwned(int $campaignId, int $couponId, int $userId): void
    {
        if ($this->findOwned($campaignId, $userId) === null) throw new RuntimeException('Bạn không có quyền sửa Campaign này.');
        $stmt = $this->pdo->prepare('DELETE FROM campaign_coupons WHERE campaign_id=? AND coupon_id=?');
        $stmt->execute([$campaignId, $couponId]);
    }

    /** @return array<int,array<string,mixed>> */
    public function attachedCoupons(int $campaignId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id,c.title,c.code,c.effective_status,m.name AS merchant_name,c.starts_at,c.ends_at " .
            "FROM campaign_coupons cc JOIN v_coupon_status c ON c.id=cc.coupon_id JOIN merchants m ON m.id=c.merchant_id " .
            "WHERE cc.campaign_id=? ORDER BY cc.added_at DESC"
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }
}

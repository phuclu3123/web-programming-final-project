<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use RuntimeException;

final class CampaignRepository implements \App\Contracts\CampaignRepositoryInterface
{
    public function __construct(private Database $db) {}

    private function publicEligibility(): string
    {
        return "c.status='PUBLISHED' AND c.ends_at>UTC_TIMESTAMP()
            AND (c.owner_merchant_id IS NULL OR (m.status='APPROVED' AND m.deleted_at IS NULL))";
    }

    /** Một điều kiện công khai dùng chung cho danh sách, đếm và chi tiết. */
    public function publicListing(string $phase, int $page, int $pageSize = 9): array
    {
        return $this->publicFilteredListing($phase, 0, 'ALL', $page, $pageSize);
    }

    public function publicFilteredListing(string $phase, int $categoryId, string $usageChannel, int $page, int $pageSize = 9): array
    {
        $where = $this->publicEligibility();
        if ($phase === 'ACTIVE') $where .= ' AND c.starts_at<=UTC_TIMESTAMP()';
        elseif ($phase === 'UPCOMING') $where .= ' AND c.starts_at>UTC_TIMESTAMP()';
        $conditions = ["vc.effective_status COLLATE utf8mb4_vietnamese_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci"];
        $params = [];
        if ($categoryId > 0) { $conditions[] = 'vc.category_id=?'; $params[] = $categoryId; }
        if (in_array($usageChannel, ['ONLINE','OFFLINE'], true)) {
            $conditions[] = "vc.usage_channel IN (?, 'BOTH')";
            $params[] = $usageChannel;
        } elseif ($usageChannel === 'BOTH') $conditions[] = "vc.usage_channel='BOTH'";
        $coupons = ' FROM campaign_coupons cc JOIN v_coupon_status vc ON vc.id=cc.coupon_id WHERE cc.campaign_id=c.id AND '.implode(' AND ', $conditions);
        if ($categoryId > 0 || in_array($usageChannel, ['ONLINE','OFFLINE','BOTH'], true)) $where .= ' AND EXISTS (SELECT 1'.$coupons.')';
        $from = ' FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id WHERE '.$where;
        $stmt = $this->db->prepare('SELECT COUNT(*)'.$from);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $pageSize = max(1,min(24,$pageSize));
        $pages = max(1,(int)ceil($total/$pageSize));
        $page = max(1,min($page,$pages));
        $stmt = $this->db->prepare('SELECT c.*,m.name AS merchant_name,(c.starts_at>UTC_TIMESTAMP()) AS upcoming,
            (SELECT COUNT(*)'.$coupons.') AS coupon_count'.$from.' ORDER BY (c.starts_at>UTC_TIMESTAMP()),c.starts_at DESC,c.id DESC LIMIT ? OFFSET ?');
        $stmt->execute(array_merge($params, $params, [$pageSize, ($page-1)*$pageSize]));
        return ['items'=>$stmt->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages];
    }

    /* @return array<int,array<string,mixed>> */
    public function publicCurrent(int $limit = 3): array
    {
        $limit = max(1, min(12, $limit));
        $sql = "SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id " .
            "WHERE " . $this->publicEligibility() . " AND c.starts_at<=UTC_TIMESTAMP() " .
            "ORDER BY c.starts_at DESC,c.id DESC LIMIT " . $limit;
        return $this->db->query($sql)->fetchAll();
    }

    /*@return array<int,array<string,mixed>> */
    public function publicUpcoming(int $limit = 4): array
    {
        $limit = max(1, min(12, $limit));
        $sql = "SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id " .
            "WHERE " . $this->publicEligibility() . " AND c.starts_at>UTC_TIMESTAMP() " .
            "ORDER BY c.starts_at ASC,c.id ASC LIMIT " . $limit;
        return $this->db->query($sql)->fetchAll();
    }

    /* @return array<string,mixed>|null */
    public function findPublic(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id " .
                "WHERE c.id=? AND " . $this->publicEligibility() . " LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /*@return array<int,array<string,mixed>> */
    /*Lấy coupon công khai thuộc chiến dịch.
 * @return array<int,array<string,mixed>>*/
    public function publicCoupons(int $campaignId, int $categoryId = 0, string $usageChannel = 'ALL'): array {
        $sql = " SELECT c.id, c.title, c.code, c.discount_type, c.discount_value, c.cashback_unit, c.min_order_amount, c.max_discount_amount, c.usage_channel, c.ends_at, m.name 
            AS merchant_name, cat.name 
            AS category_name, s.trust_score,
            s.total_votes,
            (
                SELECT total_favorites
                FROM v_coupon_favorite_stats fs
                WHERE fs.coupon_id = c.id
            ) AS favorite_count
        FROM campaign_coupons cc
        JOIN v_coupon_status c ON c.id = cc.coupon_id
        JOIN merchants m ON m.id = c.merchant_id
        JOIN categories cat ON cat.id = c.category_id
        JOIN v_coupon_vote_stats s ON s.coupon_id = c.id
        WHERE cc.campaign_id = ?
          AND EXISTS (SELECT 1 FROM campaigns campaign LEFT JOIN merchants owner ON owner.id=campaign.owner_merchant_id
              WHERE campaign.id=cc.campaign_id AND campaign.status='PUBLISHED' AND campaign.ends_at>UTC_TIMESTAMP()
              AND (campaign.owner_merchant_id IS NULL OR (owner.status='APPROVED' AND owner.deleted_at IS NULL)))
          AND c.effective_status COLLATE utf8mb4_vietnamese_ci
              = _utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci
    ";

    $params = [$campaignId];

    if ($categoryId > 0) {
        $sql .= " AND c.category_id = ?";
        $params[] = $categoryId;
    }

    if ($usageChannel === 'ONLINE' || $usageChannel === 'OFFLINE') {
        $sql .= " AND (
            c.usage_channel = ?
            OR c.usage_channel = 'BOTH'
        )";
        $params[] = $usageChannel;
    } elseif ($usageChannel === 'BOTH') {
        $sql .= " AND c.usage_channel = 'BOTH'";
    }

    $sql .= " ORDER BY c.created_at DESC, c.id DESC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/**Danh mục đang hoạt động để hiển thị trong bộ lọc.
 * @return array<int,array<string,mixed>>*/
    public function publicCategories(): array
    {
        $sql = "
        SELECT id, name
        FROM categories
        WHERE is_active = 1
        ORDER BY sort_order ASC, name ASC
    ";

    return $this->db->query($sql)->fetchAll();
    }

    public function publicCalendar(int $year, int $month = 0): array
    {
        $zone = new \DateTimeZone((string)config('app.timezone', 'Asia/Ho_Chi_Minh'));
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month ?: 1), $zone);
        $end = $start->modify($month ? '+1 month' : '+1 year');
        $stmt = $this->db->prepare('SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id
            WHERE '.$this->publicEligibility().' AND c.starts_at<? AND c.ends_at>? ORDER BY c.starts_at,c.id');
        $utc = new \DateTimeZone('UTC');
        $stmt->execute([$end->setTimezone($utc)->format('Y-m-d H:i:s'), $start->setTimezone($utc)->format('Y-m-d H:i:s')]);
        return $stmt->fetchAll();
    }

    /* @return array<string,mixed>|null */
    public function findAdmin(int $campaignId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,m.name AS merchant_name FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id WHERE c.id=? LIMIT 1"
        );
        $stmt->execute([$campaignId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /* @return array<int,array<string,mixed>> */
    public function allActiveCoupons(): array
    {
        return $this->db->query(
            "SELECT c.id,c.title,c.code,c.merchant_id,c.starts_at,c.ends_at,m.name AS merchant_name FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id " .
                "WHERE c.effective_status COLLATE utf8mb4_vietnamese_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci ORDER BY m.name,c.title"
        )->fetchAll();
    }

    public function detachAdmin(int $campaignId, int $couponId): void
    {
        $stmt = $this->db->prepare('DELETE FROM campaign_coupons WHERE campaign_id=? AND coupon_id=?');
        $stmt->execute([$campaignId, $couponId]);
    }

    /*@return array<int,array<string,mixed>> */
    public function merchantCampaigns(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,m.name AS merchant_name,(SELECT COUNT(*) FROM campaign_coupons cc WHERE cc.campaign_id=c.id) AS coupon_count " .
                "FROM campaigns c JOIN merchants m ON m.id=c.owner_merchant_id JOIN merchant_memberships mm ON mm.merchant_id=m.id " .
                "WHERE mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL ORDER BY c.created_at DESC,c.id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /* @return array<string,mixed>|null */
    public function findOwned(int $campaignId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,m.name AS merchant_name FROM campaigns c JOIN merchants m ON m.id=c.owner_merchant_id JOIN merchant_memberships mm ON mm.merchant_id=m.id " .
                "WHERE c.id=? AND mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$campaignId, $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function create(int $actorId, ?int $merchantId, string $name, string $slug, ?string $description, string $startsAtUtc, string $endsAtUtc, string $status): int
    {
        if ($merchantId !== null) {
            $check = $this->db->prepare("SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id WHERE mm.merchant_id=? AND mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL");
            $check->execute([$merchantId, $actorId]);
            if (!$check->fetchColumn()) throw new RuntimeException('Bạn không có quyền quản lý Shop này.');
        }
        $stmt = $this->db->prepare(
            'INSERT INTO campaigns(owner_merchant_id,created_by,name,slug,description,starts_at,ends_at,status) VALUES(?,?,?,?,?,?,?,?)'
        );
        $stmt->execute([$merchantId, $actorId, $name, $slug, $description, $startsAtUtc, $endsAtUtc, $status]);
        return (int) $this->db->lastInsertId();
    }

    public function updateOwned(int $campaignId, int $userId, string $name, ?string $description, string $startsAtUtc, string $endsAtUtc, string $status): void
    {
        $campaign = $this->findOwned($campaignId, $userId);
        if ($campaign === null) throw new RuntimeException('Không tìm thấy Campaign thuộc Merchant của bạn.');

        // Every attached coupon must still overlap the edited Campaign window.
        $invalid = $this->db->prepare(
            "SELECT COUNT(*) FROM campaign_coupons cc JOIN coupons c ON c.id=cc.coupon_id " .
                "WHERE cc.campaign_id=? AND NOT (c.starts_at<? AND c.ends_at>?)"
        );
        $invalid->execute([$campaignId, $endsAtUtc, $startsAtUtc]);
        if ((int) $invalid->fetchColumn() > 0) {
            throw new RuntimeException('Khoảng thời gian mới không còn giao với một hoặc nhiều coupon đã gắn. Hãy gỡ coupon đó trước khi sửa thời gian Campaign.');
        }

        $stmt = $this->db->prepare('UPDATE campaigns SET name=?,description=?,starts_at=?,ends_at=?,status=? WHERE id=?');
        $stmt->execute([$name, $description, $startsAtUtc, $endsAtUtc, $status, $campaignId]);
    }

    public function attach(int $actorId, int $campaignId, int $couponId): void
    {
        $stmt = $this->db->prepare('CALL sp_attach_campaign_coupon(?, ?, ?)');
        $stmt->execute([$actorId, $campaignId, $couponId]);
        $stmt->closeCursor();
    }

    public function detachOwned(int $campaignId, int $couponId, int $userId): void
    {
        if ($this->findOwned($campaignId, $userId) === null) throw new RuntimeException('Bạn không có quyền sửa Campaign này.');
        $stmt = $this->db->prepare('DELETE FROM campaign_coupons WHERE campaign_id=? AND coupon_id=?');
        $stmt->execute([$campaignId, $couponId]);
    }

    /* @return array<int,array<string,mixed>> */
    public function attachedCoupons(int $campaignId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id,c.title,c.code,c.effective_status,m.name AS merchant_name,c.starts_at,c.ends_at " .
                "FROM campaign_coupons cc JOIN v_coupon_status c ON c.id=cc.coupon_id JOIN merchants m ON m.id=c.merchant_id " .
                "WHERE cc.campaign_id=? ORDER BY cc.added_at DESC"
        );
        $stmt->execute([$campaignId]);
        return $stmt->fetchAll();
    }
}

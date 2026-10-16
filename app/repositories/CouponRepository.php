<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\MysqliConnection;

final class CouponRepository
{
    public function __construct(private MysqliConnection $db) {}

    /** @param array<string,mixed> $filters @return array<int,array<string,mixed>> */
    public function search(array $filters): array
    {
        $stmt = $this->db->prepare('CALL sp_search_coupons(?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $filters['keyword'],
            $filters['category_id'],
            $filters['merchant_id'],
            $filters['discount_type'],
            $filters['usage_channel'],
            $filters['ending_soon'] ? 1 : 0,
            $filters['sort'],
            $filters['page'],
            $filters['page_size'],
        ]);
        $rows = $stmt->fetchAll();
        $stmt->closeCursor();

        if ($rows === []) {
            return [];
        }

        // sp_search_coupons intentionally returns category_id. Enrich the result in
        // one extra query so both server-rendered cards and AJAX responses can show
        // the category badge without changing the database routine contract.
        $categoryMap = [];
        foreach ($this->db->query('SELECT id, name FROM categories')->fetchAll() as $category) {
            $categoryMap[(int) $category['id']] = (string) $category['name'];
        }

        foreach ($rows as &$row) {
            $row['category_name'] = $categoryMap[(int) ($row['category_id'] ?? 0)] ?? 'Khác';
        }
        unset($row);

        return $rows;
    }

    /**
     * Count using exactly the public-search eligibility/filter predicates. This is
     * deliberately independent of the paginated stored procedure's LIMIT/OFFSET.
     * @param array<string,mixed> $filters
     */
    public function countPublicSearch(array $filters): int
    {
        $sql = "SELECT COUNT(*) FROM v_coupon_status c " .
            "JOIN merchants m ON m.id=c.merchant_id " .
            "JOIN v_coupon_vote_stats vs ON vs.coupon_id=c.id " .
            "JOIN v_coupon_favorite_stats fs ON fs.coupon_id=c.id " .
            "WHERE c.effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci";
        $params = [];

        if ($filters['keyword'] !== null) {
            $sql .= " AND (c.title LIKE ? OR c.code LIKE ? OR c.description LIKE ? OR m.name LIKE ?)";
            $keyword = '%' . $filters['keyword'] . '%';
            array_push($params, $keyword, $keyword, $keyword, $keyword);
        }
        if ($filters['category_id'] !== null) {
            $sql .= ' AND c.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if ($filters['merchant_id'] !== null) {
            $sql .= ' AND c.merchant_id = ?';
            $params[] = $filters['merchant_id'];
        }
        if ($filters['discount_type'] !== null) {
            $sql .= ' AND c.discount_type = ?';
            $params[] = $filters['discount_type'];
        }
        if ($filters['usage_channel'] !== null) {
            $sql .= " AND (c.usage_channel = ? OR c.usage_channel = 'BOTH')";
            $params[] = $filters['usage_channel'];
        }
        if ($filters['ending_soon']) {
            $sql .= ' AND c.ends_at <= DATE_ADD(UTC_TIMESTAMP(), INTERVAL 3 DAY)';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<string,mixed>|null */
    public function findPublic(int $id): ?array
    {
        $sql = "SELECT c.*, m.name AS merchant_name, m.slug AS merchant_slug, cat.name AS category_name, " .
            "u.full_name AS submitter_name, u.email AS submitter_email, s.working_count, s.not_working_count, s.condition_changed_count, " .
            "s.total_votes, s.trust_score, s.last_verification_at, " .
            "(SELECT total_favorites FROM v_coupon_favorite_stats fs WHERE fs.coupon_id=c.id) AS favorite_count " .
            "FROM v_coupon_status c " .
            "JOIN merchants m ON m.id = c.merchant_id " .
            "JOIN categories cat ON cat.id = c.category_id " .
            "JOIN users u ON u.id = c.submitted_by " .
            "JOIN v_coupon_vote_stats s ON s.coupon_id = c.id " .
            "WHERE c.id = ? AND c.effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function isActive(int $couponId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM v_coupon_status WHERE id = ? AND effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci LIMIT 1");
        $stmt->execute([$couponId]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function findBySubmitter(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id, c.title, c.code, c.discount_type, c.discount_value, c.cashback_unit, " .
                "c.starts_at, c.ends_at, c.effective_status, c.review_note, c.usage_channel, m.name AS merchant_name, " .
                "cat.name AS category_name, c.created_at " .
                "FROM v_coupon_status c " .
                "JOIN merchants m ON m.id = c.merchant_id " .
                "JOIN categories cat ON cat.id = c.category_id " .
                "WHERE c.submitted_by = ? ORDER BY c.created_at DESC, c.id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @param array<string,mixed> $data */
    public function submitCommunity(int $actorId, array $data): int
    {
        $stmt = $this->db->prepare(
            'CALL sp_submit_coupon(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $actorId,
            $data['merchant_id'],
            $data['category_id'],
            0,
            $data['title'],
            $data['code'],
            $data['description'],
            $data['terms'],
            $data['discount_type'],
            $data['discount_value'],
            $data['cashback_unit'],
            $data['min_order_amount'],
            $data['max_discount_amount'],
            $data['usage_channel'],
            $data['promotion_url'],
            null,
            null,
            $data['starts_at_utc'],
            $data['ends_at_utc'],
        ]);
        $row = $stmt->fetch();
        $stmt->closeCursor();
        return (int) ($row['coupon_id'] ?? 0);
    }

    /** @return array<int,array<string,mixed>> */
    public function pendingForModeration(): array
    {
        $sql = "SELECT c.id, c.title, c.code, c.description, c.terms, c.discount_type, c.discount_value, c.cashback_unit, " .
            "c.min_order_amount, c.max_discount_amount, c.usage_channel, c.promotion_url, c.starts_at, c.ends_at, " .
            "c.created_at, c.source, c.effective_status, m.name AS merchant_name, cat.name AS category_name, " .
            "u.full_name AS submitter_name, u.email AS submitter_email " .
            "FROM v_coupon_status c " .
            "JOIN merchants m ON m.id = c.merchant_id " .
            "JOIN categories cat ON cat.id = c.category_id " .
            "JOIN users u ON u.id = c.submitted_by " .
            "WHERE c.moderation_status = 'PENDING' AND c.deleted_at IS NULL " .
            "ORDER BY c.created_at ASC, c.id ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /** @param array<string,mixed> $data */
    public function submitOfficial(int $actorId, array $data): int
    {
        $stmt = $this->db->prepare(
            'CALL sp_submit_coupon(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $actorId,
            $data['merchant_id'],
            $data['category_id'],
            1,
            $data['title'],
            $data['code'],
            $data['description'],
            $data['terms'],
            $data['discount_type'],
            $data['discount_value'],
            $data['cashback_unit'],
            $data['min_order_amount'],
            $data['max_discount_amount'],
            $data['usage_channel'],
            $data['promotion_url'],
            null,
            null,
            $data['starts_at_utc'],
            $data['ends_at_utc'],
        ]);
        $row = $stmt->fetch();
        $stmt->closeCursor();
        return (int) ($row['coupon_id'] ?? 0);
    }

    /** @return array<int,array<string,mixed>> */
    public function officialByOwner(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id,c.merchant_id,c.title,c.code,c.discount_type,c.discount_value,c.cashback_unit,c.usage_channel,c.starts_at,c.ends_at, " .
                "c.effective_status,c.moderation_status,c.publication_status,c.review_note,m.name AS merchant_name,cat.name AS category_name,c.created_at " .
                "FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id JOIN merchant_memberships mm ON mm.merchant_id=m.id JOIN categories cat ON cat.id=c.category_id " .
                "WHERE c.source='OFFICIAL' AND mm.user_id=? AND mm.status='ACTIVE' AND m.deleted_at IS NULL ORDER BY c.created_at DESC,c.id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed>|null */
    public function findOwnedOfficialForEdit(int $couponId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,m.name AS merchant_name,cat.name AS category_name FROM coupons c " .
                "JOIN merchants m ON m.id=c.merchant_id JOIN merchant_memberships mm ON mm.merchant_id=m.id JOIN categories cat ON cat.id=c.category_id " .
                "WHERE c.id=? AND c.source='OFFICIAL' AND mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND c.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$couponId, $userId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @param array<string,mixed> $data */
    public function updateOwnedOfficial(int $actorId, int $couponId, array $data): void
    {
        $this->db->beginTransaction();
        try {
            $merchant = $this->db->prepare('SELECT id,status FROM merchants WHERE id=? FOR UPDATE');
            $merchant->execute([(int) $data['merchant_id']]);
            $m = $merchant->fetch();
            if (!is_array($m) || !$this->activeManager($actorId, (int)$data['merchant_id']) || (string) $m['status'] !== 'APPROVED') {
                throw new \RuntimeException('Merchant không thuộc quyền quản lý của bạn.');
            }

            $lock = $this->db->prepare(
                "SELECT moderation_status,publication_status,merchant_id,source FROM coupons WHERE id=? AND deleted_at IS NULL FOR UPDATE"
            );
            $lock->execute([$couponId]);
            $old = $lock->fetch();
            if (!is_array($old) || (string) $old['source'] !== 'OFFICIAL' || (int) $old['merchant_id'] !== (int) $data['merchant_id']) {
                throw new \RuntimeException('Không tìm thấy coupon Official phù hợp.');
            }

            if (!empty($data['code'])) {
                $dup = $this->db->prepare(
                    "SELECT COUNT(*) FROM coupons WHERE id<>? AND merchant_id=? AND code_normalized=UPPER(TRIM(?)) AND deleted_at IS NULL " .
                        "AND moderation_status IN ('PENDING','APPROVED') AND publication_status='ENABLED' AND starts_at<? AND ends_at>?"
                );
                $dup->execute([$couponId, $data['merchant_id'], $data['code'], $data['ends_at_utc'], $data['starts_at_utc']]);
                if ((int) $dup->fetchColumn() > 0) throw new \RuntimeException('Mã coupon bị trùng khoảng hiệu lực với coupon khác của Merchant.');
            }

            $update = $this->db->prepare(
                "UPDATE coupons SET category_id=?,title=?,code=?,description=?,terms=?,discount_type=?,discount_value=?,cashback_unit=?,min_order_amount=?,max_discount_amount=?,usage_channel=?,promotion_url=?,starts_at=?,ends_at=?, " .
                    "moderation_status='PENDING',publication_status='ENABLED',reviewed_by=NULL,reviewed_at=NULL,review_note=NULL WHERE id=?"
            );
            $update->execute([
                $data['category_id'],
                $data['title'],
                $data['code'],
                $data['description'],
                $data['terms'],
                $data['discount_type'],
                $data['discount_value'],
                $data['cashback_unit'],
                $data['min_order_amount'],
                $data['max_discount_amount'],
                $data['usage_channel'],
                $data['promotion_url'],
                $data['starts_at_utc'],
                $data['ends_at_utc'],
                $couponId,
            ]);

            $this->db->prepare('DELETE FROM votes WHERE coupon_id=?')->execute([$couponId]);
            $history = $this->db->prepare(
                "INSERT INTO coupon_status_history(coupon_id,actor_id,old_moderation,new_moderation,old_publication,new_publication,note) VALUES(?,?,?,?,?,?,?)"
            );
            $history->execute([$couponId, $actorId, $old['moderation_status'], 'PENDING', $old['publication_status'], 'ENABLED', 'Merchant cập nhật nội dung và gửi duyệt lại']);
            $this->db->prepare("INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(?,?,'COUPON_EDIT','Official coupon updated; re-moderation required')")
                ->execute([$actorId, $couponId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function disableOwnedOfficial(int $actorId, int $couponId): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT c.moderation_status,c.publication_status,m.id AS merchant_id FROM coupons c JOIN merchants m ON m.id=c.merchant_id JOIN merchant_memberships mm ON mm.merchant_id=m.id " .
                    "WHERE c.id=? AND c.source='OFFICIAL' AND mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND c.deleted_at IS NULL FOR UPDATE"
            );
            $stmt->execute([$couponId, $actorId]);
            $row = $stmt->fetch();
            if (!is_array($row)) throw new \RuntimeException('Không tìm thấy coupon Official thuộc Merchant của bạn.');
            if ((string) $row['publication_status'] === 'DISABLED') {
                $this->db->commit();
                return;
            }
            $this->db->prepare("UPDATE coupons SET publication_status='DISABLED' WHERE id=?")->execute([$couponId]);
            $this->db->prepare(
                "INSERT INTO coupon_status_history(coupon_id,actor_id,old_moderation,new_moderation,old_publication,new_publication,note) VALUES(?,?,?,?,?,'DISABLED','Merchant ngừng coupon')"
            )->execute([$couponId, $actorId, $row['moderation_status'], $row['moderation_status'], $row['publication_status']]);
            $this->db->prepare("INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(?,?,'COUPON_DISABLE','Merchant disabled official coupon')")
                ->execute([$actorId, $couponId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function eligibleForMerchantCampaign(int $merchantId, int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT c.id,c.title,c.code,c.starts_at,c.ends_at,c.effective_status FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id JOIN merchant_memberships mm ON mm.merchant_id=m.id " .
                "WHERE c.merchant_id=? AND mm.user_id=? AND mm.status='ACTIVE' AND c.source='OFFICIAL' AND c.moderation_status='APPROVED' AND c.publication_status='ENABLED' AND c.deleted_at IS NULL " .
                "ORDER BY c.ends_at ASC,c.id DESC"
        );
        $stmt->execute([$merchantId, $userId]);
        return $stmt->fetchAll();
    }

    /** @return array<int,array<string,mixed>> */
    public function hotDeals(int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));
        $sql = "SELECT c.id,c.title,c.code,c.discount_type,c.discount_value,c.cashback_unit,c.usage_channel,c.ends_at,c.is_official,m.name AS merchant_name,cat.name AS category_name, " .
            "s.trust_score,s.total_votes,(SELECT total_favorites FROM v_coupon_favorite_stats fs WHERE fs.coupon_id=c.id) AS favorite_count " .
            "FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id JOIN categories cat ON cat.id=c.category_id JOIN v_coupon_vote_stats s ON s.coupon_id=c.id " .
            "WHERE c.effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci " .
            "ORDER BY (SELECT qualified_favorites FROM v_coupon_favorite_stats fs WHERE fs.coupon_id=c.id) DESC,s.total_votes DESC,c.created_at DESC LIMIT " . $limit;
        return $this->db->query($sql)->fetchAll();
    }

    public function moderate(int $actorId, int $couponId, string $moderation, string $publication, ?string $note): void
    {
        $stmt = $this->db->prepare('CALL sp_moderate_coupon(?, ?, ?, ?, ?)');
        $stmt->execute([$actorId, $couponId, $moderation, $publication, $note]);
        $stmt->closeCursor();
    }
    private function activeManager(int $userId, int $merchantId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id WHERE mm.user_id=? AND mm.merchant_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL LIMIT 1");
        $stmt->execute([$userId, $merchantId]);
        return (bool)$stmt->fetchColumn();
    }
}

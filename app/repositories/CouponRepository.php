<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CouponRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @param array<string,mixed> $filters @return array<int,array<string,mixed>> */
    public function search(array $filters): array
    {
        $stmt = $this->pdo->prepare('CALL sp_search_coupons(?, ?, ?, ?, ?, ?, ?, ?, ?)');
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
        foreach ($this->pdo->query('SELECT id, name FROM categories')->fetchAll() as $category) {
            $categoryMap[(int) $category['id']] = (string) $category['name'];
        }

        foreach ($rows as &$row) {
            $row['category_name'] = $categoryMap[(int) ($row['category_id'] ?? 0)] ?? 'Khác';
        }
        unset($row);

        return $rows;
    }

    /** @return array<string,mixed>|null */
    public function findPublic(int $id): ?array
    {
        $sql = "SELECT c.*, m.name AS merchant_name, m.slug AS merchant_slug, cat.name AS category_name, " .
            "u.full_name AS submitter_name, u.email AS submitter_email, s.working_count, s.not_working_count, s.condition_changed_count, " .
            "s.total_votes, s.trust_score, s.last_verification_at, " .
            "(SELECT COUNT(*) FROM favorites f WHERE f.coupon_id = c.id) AS favorite_count " .
            "FROM v_coupon_status c " .
            "JOIN merchants m ON m.id = c.merchant_id " .
            "JOIN categories cat ON cat.id = c.category_id " .
            "JOIN users u ON u.id = c.submitted_by " .
            "JOIN v_coupon_vote_stats s ON s.coupon_id = c.id " .
            "WHERE c.id = ? AND c.effective_status = 'ACTIVE' LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function isActive(int $couponId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM v_coupon_status WHERE id = ? AND effective_status = 'ACTIVE' LIMIT 1");
        $stmt->execute([$couponId]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function findBySubmitter(int $userId): array
    {
        $stmt = $this->pdo->prepare(
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
        $stmt = $this->pdo->prepare(
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
        return $this->pdo->query($sql)->fetchAll();
    }

    public function moderate(int $actorId, int $couponId, string $moderation, string $publication, ?string $note): void
    {
        $stmt = $this->pdo->prepare('CALL sp_moderate_coupon(?, ?, ?, ?, ?)');
        $stmt->execute([$actorId, $couponId, $moderation, $publication, $note]);
        $stmt->closeCursor();
    }
}

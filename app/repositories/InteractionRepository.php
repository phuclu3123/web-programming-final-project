<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;
use Throwable;

final class InteractionRepository
{
    public function __construct(private PDO $pdo) {}

    public function setFavorite(int $actorId, int $couponId, bool $saved): void
    {
        $stmt = $this->pdo->prepare('CALL sp_set_favorite(?, ?, ?)');
        $stmt->execute([$actorId, $couponId, $saved ? 1 : 0]);
        $stmt->closeCursor();
    }

    public function setVote(int $actorId, int $couponId, string $vote): void
    {
        $stmt = $this->pdo->prepare('CALL sp_set_vote(?, ?, ?)');
        $stmt->execute([$actorId, $couponId, $vote]);
        $stmt->closeCursor();
    }

    /**
     * UI hint only; sp_set_vote is still the authoritative authorization check.
     * An active Shop manager must not confirm any coupon of that Shop.
     */
    public function voteRestriction(int $actorId, int $couponId): ?string
    {
        $stmt = $this->pdo->prepare(
            "SELECT CASE " .
                "WHEN c.submitted_by = ? THEN 'AUTHOR' " .
                "WHEN EXISTS (" .
                    "SELECT 1 FROM merchant_memberships mm " .
                    "WHERE mm.user_id = ? AND mm.merchant_id = c.merchant_id AND mm.status = 'ACTIVE'" .
                ") THEN 'SHOP_MANAGER' " .
                "ELSE NULL END AS restriction_reason " .
                "FROM coupons c WHERE c.id = ? LIMIT 1"
        );
        $stmt->execute([$actorId, $actorId, $couponId]);
        $value = $stmt->fetchColumn();
        return is_string($value) ? $value : null;
    }

    public function isFavorite(int $actorId, int $couponId): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND coupon_id = ? LIMIT 1');
        $stmt->execute([$actorId, $couponId]);
        return (bool) $stmt->fetchColumn();
    }

    public function currentVote(int $actorId, int $couponId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT vote_type FROM votes WHERE user_id = ? AND coupon_id = ? LIMIT 1');
        $stmt->execute([$actorId, $couponId]);
        $value = $stmt->fetchColumn();
        return is_string($value) ? $value : null;
    }

    /** @return array<string,mixed> */
    public function stats(int $couponId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT s.working_count, s.not_working_count, s.condition_changed_count, s.total_votes, s.trust_score, s.last_verification_at, ' .
                'fs.total_favorites AS favorite_count,fs.qualified_favorites ' .
                'FROM v_coupon_vote_stats s JOIN v_coupon_favorite_stats fs ON fs.coupon_id=s.coupon_id WHERE s.coupon_id = ? LIMIT 1'
        );
        $stmt->execute([$couponId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : [
            'working_count' => 0,
            'not_working_count' => 0,
            'condition_changed_count' => 0,
            'total_votes' => 0,
            'trust_score' => null,
            'last_verification_at' => null,
            'favorite_count' => 0,
            'qualified_favorites' => 0,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function comments(int $couponId, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->pdo->prepare(
            'SELECT c.id, c.body, c.created_at, c.updated_at, u.id AS user_id, u.full_name ' .
                'FROM comments c JOIN users u ON u.id = c.user_id ' .
                "WHERE c.coupon_id = ? AND c.status = 'VISIBLE' AND u.deleted_at IS NULL " .
                'ORDER BY c.created_at DESC, c.id DESC LIMIT ' . $limit
        );
        $stmt->execute([$couponId]);
        return $stmt->fetchAll();
    }

    /** @return array<string,mixed> */
    public function addComment(int $actorId, int $couponId, string $body): array
    {
        try {
            $this->pdo->beginTransaction();

            if (!$this->activeUserExists($actorId)) {
                throw new RuntimeException('Tài khoản không còn hoạt động.');
            }
            if (!$this->activeCouponExists($couponId)) {
                throw new RuntimeException('Coupon phải đang hoạt động để bình luận.');
            }

            $stmt = $this->pdo->prepare('INSERT INTO comments(coupon_id, user_id, body) VALUES(?, ?, ?)');
            $stmt->execute([$couponId, $actorId, $body]);
            $commentId = (int) $this->pdo->lastInsertId();

            $log = $this->pdo->prepare("INSERT INTO activity_logs(user_id, coupon_id, action) VALUES(?, ?, 'COMMENT_ADD')");
            $log->execute([$actorId, $couponId]);

            $this->pdo->commit();

            $fetch = $this->pdo->prepare(
                'SELECT c.id, c.body, c.created_at, c.updated_at, u.id AS user_id, u.full_name ' .
                    'FROM comments c JOIN users u ON u.id = c.user_id WHERE c.id = ? LIMIT 1'
            );
            $fetch->execute([$commentId]);
            $row = $fetch->fetch();
            if (!is_array($row)) {
                throw new RuntimeException('Không thể tải bình luận vừa tạo.');
            }
            return $row;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function createReport(int $actorId, int $couponId, string $reason, ?string $details): int
    {
        try {
            $this->pdo->beginTransaction();

            // Lock the coupon row first so duplicate/open-report checks are stable.
            $lock = $this->pdo->prepare('SELECT id FROM coupons WHERE id = ? AND deleted_at IS NULL FOR UPDATE');
            $lock->execute([$couponId]);
            if (!$lock->fetchColumn()) {
                throw new RuntimeException('Không tìm thấy coupon để báo cáo.');
            }
            if (!$this->activeUserExists($actorId)) {
                throw new RuntimeException('Tài khoản không còn hoạt động.');
            }
            if (!$this->activeCouponExists($couponId)) {
                throw new RuntimeException('Coupon hiện không ở trạng thái có thể báo cáo.');
            }

            $duplicate = $this->pdo->prepare(
                "SELECT id FROM reports WHERE coupon_id = ? AND reported_by = ? AND status = 'OPEN' LIMIT 1"
            );
            $duplicate->execute([$couponId, $actorId]);
            if ($duplicate->fetchColumn()) {
                throw new RuntimeException('Bạn đã có một báo cáo đang chờ xử lý cho coupon này.');
            }

            $stmt = $this->pdo->prepare(
                'INSERT INTO reports(coupon_id, reported_by, reason, details) VALUES(?, ?, ?, ?)'
            );
            $stmt->execute([$couponId, $actorId, $reason, $details]);
            $reportId = (int) $this->pdo->lastInsertId();

            $log = $this->pdo->prepare(
                "INSERT INTO activity_logs(user_id, coupon_id, action, description) VALUES(?, ?, 'REPORT_CREATE', ?)"
            );
            $log->execute([$actorId, $couponId, $reason]);

            $this->pdo->commit();
            return $reportId;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function pendingReports(): array
    {
        $sql = "SELECT r.id, r.reason, r.details, r.created_at, c.id AS coupon_id, c.title AS coupon_title, c.code, " .
            "m.name AS merchant_name, u.id AS reporter_id, u.full_name AS reporter_name, u.email AS reporter_email " .
            "FROM reports r " .
            "JOIN coupons c ON c.id = r.coupon_id " .
            "JOIN merchants m ON m.id = c.merchant_id " .
            "JOIN users u ON u.id = r.reported_by " .
            "WHERE r.status = 'OPEN' ORDER BY r.created_at ASC, r.id ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function resolveReport(int $actorId, int $reportId, string $status, string $note): void
    {
        $stmt = $this->pdo->prepare('CALL sp_resolve_report(?, ?, ?, ?)');
        $stmt->execute([$actorId, $reportId, $status, $note]);
        $stmt->closeCursor();
    }

    private function activeUserExists(int $userId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM users WHERE id = ? AND status = 'ACTIVE' AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$userId]);
        return (bool) $stmt->fetchColumn();
    }

    private function activeCouponExists(int $couponId): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM v_coupon_status WHERE id = ? AND effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci LIMIT 1");
        $stmt->execute([$couponId]);
        return (bool) $stmt->fetchColumn();
    }
}

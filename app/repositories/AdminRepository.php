<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use RuntimeException;

final class AdminRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<string,int> */
    public function dashboardStats(): array
    {
        $queries = [
            'users' => "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL",
            'locked_users' => "SELECT COUNT(*) FROM users WHERE status='LOCKED' AND deleted_at IS NULL",
            'pending_merchants' => "SELECT COUNT(*) FROM merchants WHERE status='PENDING' AND deleted_at IS NULL",
            'approved_merchants' => "SELECT COUNT(*) FROM merchants WHERE status='APPROVED' AND deleted_at IS NULL",
            'pending_coupons' => "SELECT COUNT(*) FROM coupons WHERE moderation_status='PENDING' AND deleted_at IS NULL",
            'active_coupons' => "SELECT COUNT(*) FROM v_coupon_status WHERE effective_status COLLATE utf8mb4_unicode_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci",
            'open_reports' => "SELECT COUNT(*) FROM reports WHERE status='OPEN'",
            'published_campaigns' => "SELECT COUNT(*) FROM campaigns WHERE status='PUBLISHED' AND ends_at>UTC_TIMESTAMP()",
        ];
        $out = [];
        foreach ($queries as $key => $sql) $out[$key] = (int) $this->pdo->query($sql)->fetchColumn();
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    public function users(): array
    {
        return $this->pdo->query(
            "SELECT u.id,u.full_name,u.email,u.role,u.status,u.created_at,COALESCE(v.earned_points,0) AS points " .
            "FROM users u LEFT JOIN v_user_points v ON v.user_id=u.id WHERE u.deleted_at IS NULL ORDER BY u.created_at DESC,u.id DESC"
        )->fetchAll();
    }

    public function setUserStatus(int $actorId, int $userId, string $status): void
    {
        if ($actorId === $userId) throw new RuntimeException('Admin không thể tự khóa chính mình.');
        if (!in_array($status, ['ACTIVE','LOCKED'], true)) throw new RuntimeException('Trạng thái User không hợp lệ.');
        $stmt = $this->pdo->prepare('UPDATE users SET status=? WHERE id=? AND deleted_at IS NULL');
        $stmt->execute([$status, $userId]);
        if ($stmt->rowCount() === 0) throw new RuntimeException('Không tìm thấy User.');
    }

    /** @return array<int,array<string,mixed>> */
    public function merchants(): array
    {
        return $this->pdo->query(
            "SELECT m.id,m.name,m.slug,m.website_url,m.status,m.review_note,m.created_at,u.id AS owner_id,u.full_name AS owner_name,u.email AS owner_email " .
            "FROM merchants m LEFT JOIN users u ON u.id=m.owner_user_id WHERE m.deleted_at IS NULL ORDER BY FIELD(m.status,'PENDING','APPROVED','UNCLAIMED','REJECTED','LOCKED'),m.created_at DESC"
        )->fetchAll();
    }

    public function reviewMerchant(int $actorId, int $merchantId, string $action, ?string $note): void
    {
        $action = strtoupper($action);
        if (!in_array($action, ['APPROVE','REJECT','LOCK'], true)) throw new RuntimeException('Hành động Merchant không hợp lệ.');
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT owner_user_id,status FROM merchants WHERE id=? AND deleted_at IS NULL FOR UPDATE');
            $stmt->execute([$merchantId]);
            $merchant = $stmt->fetch();
            if (!is_array($merchant)) throw new RuntimeException('Không tìm thấy Merchant.');
            $ownerId = $merchant['owner_user_id'] !== null ? (int) $merchant['owner_user_id'] : null;
            if ($action === 'APPROVE' && $ownerId === null) throw new RuntimeException('Merchant chưa có chủ sở hữu để duyệt.');
            $status = ['APPROVE'=>'APPROVED','REJECT'=>'REJECTED','LOCK'=>'LOCKED'][$action];
            $update = $this->pdo->prepare('UPDATE merchants SET status=?,reviewed_by=?,reviewed_at=UTC_TIMESTAMP(),review_note=? WHERE id=?');
            $update->execute([$status, $actorId, $note, $merchantId]);
            if ($status === 'APPROVED' && $ownerId !== null) {
                $role = $this->pdo->prepare("UPDATE users SET role='MERCHANT' WHERE id=? AND role='USER' AND deleted_at IS NULL");
                $role->execute([$ownerId]);
            }
            if (in_array($status, ['REJECTED','LOCKED'], true) && $ownerId !== null) {
                $other = $this->pdo->prepare("SELECT COUNT(*) FROM merchants WHERE owner_user_id=? AND status='APPROVED' AND id<>? AND deleted_at IS NULL");
                $other->execute([$ownerId, $merchantId]);
                if ((int) $other->fetchColumn() === 0) {
                    $this->pdo->prepare("UPDATE users SET role='USER' WHERE id=? AND role='MERCHANT'")->execute([$ownerId]);
                }
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function categories(): array
    {
        return $this->pdo->query('SELECT id,name,slug,description,is_active,sort_order,created_at FROM categories ORDER BY sort_order,name')->fetchAll();
    }

    public function addCategory(int $actorId, string $name, string $slug, ?string $description): void
    {
        $stmt = $this->pdo->prepare('CALL sp_add_category(?, ?, ?, ?)');
        $stmt->execute([$actorId, $name, $slug, $description]);
        $stmt->closeCursor();
    }

    public function toggleCategory(int $categoryId, bool $active): void
    {
        $stmt = $this->pdo->prepare('UPDATE categories SET is_active=? WHERE id=?');
        $stmt->execute([$active ? 1 : 0, $categoryId]);
    }

    /** @return array<int,array<string,mixed>> */
    public function campaigns(): array
    {
        return $this->pdo->query(
            "SELECT c.*,m.name AS merchant_name,u.full_name AS creator_name,(SELECT COUNT(*) FROM campaign_coupons cc WHERE cc.campaign_id=c.id) AS coupon_count " .
            "FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id JOIN users u ON u.id=c.created_by ORDER BY c.created_at DESC,c.id DESC"
        )->fetchAll();
    }

    public function setCampaignStatus(int $campaignId, string $status): void
    {
        if (!in_array($status, ['DRAFT','PUBLISHED','DISABLED','ARCHIVED'], true)) throw new RuntimeException('Trạng thái Campaign không hợp lệ.');
        $stmt = $this->pdo->prepare('UPDATE campaigns SET status=? WHERE id=?');
        $stmt->execute([$status, $campaignId]);
    }
}

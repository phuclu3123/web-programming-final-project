<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use RuntimeException;

final class AdminRepository implements \App\Contracts\AdminRepositoryInterface
{
    use AdminWriteGuard;
    public function __construct(private Database $db) {}

    /** @return array<string,int> */
    public function dashboardStats(): array
    {
        $queries = [
            'users' => "SELECT COUNT(*) FROM users WHERE deleted_at IS NULL",
            'locked_users' => "SELECT COUNT(*) FROM users WHERE status='LOCKED' AND deleted_at IS NULL",
            'pending_merchants' => "SELECT COUNT(*) FROM merchants WHERE status='PENDING' AND deleted_at IS NULL",
            'approved_merchants' => "SELECT COUNT(*) FROM merchants WHERE status='APPROVED' AND deleted_at IS NULL",
            'pending_manager_requests' => "SELECT COUNT(*) FROM merchant_requests WHERE status='PENDING'",
            'pending_coupons' => "SELECT COUNT(*) FROM coupons WHERE moderation_status='PENDING' AND deleted_at IS NULL",
            'active_coupons' => "SELECT COUNT(*) FROM v_coupon_status WHERE effective_status COLLATE utf8mb4_vietnamese_ci = _utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci",
            'open_reports' => "SELECT COUNT(*) FROM reports WHERE status='OPEN'",
            'published_campaigns' => "SELECT COUNT(*) FROM campaigns WHERE status='PUBLISHED' AND ends_at>UTC_TIMESTAMP()",
        ];
        $out = [];
        foreach ($queries as $key => $sql) $out[$key] = (int) $this->db->query($sql)->fetchColumn();
        return $out;
    }

    /** @return array<int,array<string,mixed>> */
    public function users(): array
    {
        return $this->db->query(
            "SELECT u.id,u.full_name,u.email,u.role,u.status,u.created_at,COALESCE(v.earned_points,0) AS points " .
                "FROM users u LEFT JOIN v_user_points v ON v.user_id=u.id WHERE u.deleted_at IS NULL ORDER BY u.created_at DESC,u.id DESC"
        )->fetchAll();
    }

    public function setUserStatus(int $actorId, int $userId, string $status): void
    {
        if ($actorId === $userId) throw new RuntimeException('Admin không thể tự khóa chính mình.');
        if (!in_array($status, ['ACTIVE', 'LOCKED'], true)) throw new RuntimeException('Trạng thái User không hợp lệ.');
        $stmt = $this->db->prepare('UPDATE users SET status=? WHERE id=? AND deleted_at IS NULL');
        $stmt->execute([$status, $userId]);
        if ($stmt->rowCount() === 0) throw new RuntimeException('Không tìm thấy User.');
    }

    /** @return array<int,array<string,mixed>> */
    public function merchants(): array
    {
        return $this->db->query(
            "SELECT m.id,m.name,m.slug,m.website_url,m.status,m.review_note,m.created_at,u.id AS owner_id,u.full_name AS owner_name,u.email AS owner_email " .
                "FROM merchants m LEFT JOIN users u ON u.id=m.owner_user_id WHERE m.deleted_at IS NULL ORDER BY FIELD(m.status,'PENDING','APPROVED','UNCLAIMED','REJECTED','LOCKED'),m.created_at DESC"
        )->fetchAll();
    }

    public function reviewMerchant(int $actorId, int $merchantId, string $action, ?string $note): void
    {
        throw new RuntimeException('Đã chuyển sang xét duyệt đơn riêng cho từng User–Shop tại /admin/merchant-requests.');
    }

    public function setMerchantStatus(int $merchantId, string $action, int $actorId): void
    {
        if (!in_array($action, ['LOCK', 'UNLOCK'], true)) throw new RuntimeException('Hành động không hợp lệ.');
        $current = $action === 'LOCK' ? 'APPROVED' : 'LOCKED';
        $next = $action === 'LOCK' ? 'LOCKED' : 'APPROVED';
        $stmt = $this->db->prepare('UPDATE merchants SET status=?,reviewed_by=?,reviewed_at=UTC_TIMESTAMP() WHERE id=? AND status=? AND deleted_at IS NULL');
        $stmt->execute([$next, $actorId, $merchantId, $current]);
        if ($stmt->rowCount() !== 1) throw new RuntimeException('Trạng thái Shop không phù hợp hoặc không tìm thấy Shop.');
    }

    /** @return array<int,array<string,mixed>> */
    public function categories(): array
    {
        return $this->db->query('SELECT id,name,slug,description,is_active,sort_order,created_at FROM categories ORDER BY sort_order,name')->fetchAll();
    }

    public function addCategory(int $actorId, string $name, string $slug, ?string $description): void
    {
        $stmt = $this->db->prepare('CALL sp_add_category(?, ?, ?, ?)');
        $stmt->execute([$actorId, $name, $slug, $description]);
        $stmt->closeCursor();
    }

    public function toggleCategory(int $categoryId, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE categories SET is_active=? WHERE id=?');
        $stmt->execute([$active ? 1 : 0, $categoryId]);
    }

    public function updateCategory(int $actorId, int $categoryId, string $name, string $slug, ?string $description, int $sortOrder): void
    {
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actorId);
            $lock = $this->db->prepare('SELECT id FROM categories WHERE id=? FOR UPDATE');
            $lock->execute([$categoryId]);
            if (!$lock->fetch()) throw new RuntimeException('Không tìm thấy danh mục.');
            $duplicate = $this->db->prepare('SELECT id FROM categories WHERE (slug=? OR name=?) AND id<>? LIMIT 1');
            $duplicate->execute([$slug, $name, $categoryId]);
            if ($duplicate->fetch()) throw new RuntimeException('Danh mục có tên hoặc slug này đã tồn tại.');
            $stmt = $this->db->prepare('UPDATE categories SET name=?,slug=?,description=?,sort_order=? WHERE id=?');
            $stmt->execute([$name, $slug, $description, $sortOrder, $categoryId]);
            $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')
                ->execute([$actorId, 'CATEGORY_EDIT', 'Updated category #' . $categoryId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** Chỉ xóa danh mục trống; danh mục đã dùng nên tắt thay vì phá quan hệ dữ liệu. */
    public function deleteUnusedCategory(int $actorId, int $categoryId): void
    {
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actorId);
            $lock = $this->db->prepare('SELECT id FROM categories WHERE id=? FOR UPDATE');
            $lock->execute([$categoryId]);
            if (!$lock->fetch()) throw new RuntimeException('Không tìm thấy danh mục.');
            foreach (['coupons', 'articles'] as $table) {
                $count = $this->db->prepare("SELECT COUNT(*) FROM {$table} WHERE category_id=?");
                $count->execute([$categoryId]);
                if ((int)$count->fetchColumn() > 0) {
                    throw new RuntimeException('Danh mục đã được sử dụng. Hãy tắt danh mục thay vì xóa.');
                }
            }
            $stmt = $this->db->prepare('DELETE FROM categories WHERE id=?');
            $stmt->execute([$categoryId]);
            if ($stmt->rowCount() !== 1) throw new RuntimeException('Không thể xóa danh mục.');
            $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')
                ->execute([$actorId, 'CATEGORY_DELETE', 'Deleted unused category #' . $categoryId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function campaigns(): array
    {
        return $this->db->query(
            "SELECT c.*,m.name AS merchant_name,u.full_name AS creator_name,(SELECT COUNT(*) FROM campaign_coupons cc WHERE cc.campaign_id=c.id) AS coupon_count " .
                "FROM campaigns c LEFT JOIN merchants m ON m.id=c.owner_merchant_id JOIN users u ON u.id=c.created_by ORDER BY c.created_at DESC,c.id DESC"
        )->fetchAll();
    }

    public function setCampaignStatus(int $campaignId, string $status): void
    {
        if (!in_array($status, ['DRAFT', 'PUBLISHED', 'DISABLED', 'ARCHIVED'], true)) throw new RuntimeException('Trạng thái Campaign không hợp lệ.');
        $stmt = $this->db->prepare('UPDATE campaigns SET status=? WHERE id=?');
        $stmt->execute([$status, $campaignId]);
    }
}

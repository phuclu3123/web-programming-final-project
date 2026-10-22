<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use RuntimeException;
use Throwable;

/** Admin-only data operations. All destructive changes preserve audit history. */
final class AdminCouponRepository
{
    use AdminWriteGuard;

    private const ADMIN_STATUS = "CASE WHEN c.moderation_status IN ('PENDING','REJECTED') THEN c.moderation_status ELSE c.effective_status END";
    public function __construct(private Database $db) {}

    /** @return array<int,array<string,mixed>> */
    public function all(?string $status = null, int $page = 1, int $pageSize = 25): array
    {
        $sql = "SELECT c.id,c.title,c.code,c.source,c.moderation_status,c.publication_status,c.effective_status, " .
            self::ADMIN_STATUS . " AS admin_status,c.starts_at,c.ends_at,c.created_at,c.deleted_at,m.name AS merchant_name,cat.name AS category_name " .
            "FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id " .
            "JOIN categories cat ON cat.id=c.category_id WHERE c.deleted_at IS NULL";
        $params = [];
        if ($status !== null) {
            $sql .= ' AND (' . self::ADMIN_STATUS . ') = ?';
            $params[] = $status;
        }
        $pageSize = max(1, min(100, $pageSize));
        $offset = (max(1, min(100000, $page)) - 1) * $pageSize;
        $sql .= ' ORDER BY c.created_at DESC,c.id DESC LIMIT ' . $pageSize . ' OFFSET ' . $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count(?string $status = null): int
    {
        $sql = 'SELECT COUNT(*) FROM v_coupon_status c WHERE c.deleted_at IS NULL';
        if ($status !== null) $sql .= ' AND (' . self::ADMIN_STATUS . ') = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($status !== null ? [$status] : []);
        return (int)$stmt->fetchColumn();
    }

    /** @return array<string,mixed>|null */
    public function find(int $couponId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,m.name AS merchant_name,cat.name AS category_name " .
            "FROM coupons c JOIN merchants m ON m.id=c.merchant_id " .
            "JOIN categories cat ON cat.id=c.category_id WHERE c.id=? AND c.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$couponId]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<int,array<string,mixed>> */
    public function activeCategories(): array
    {
        return $this->db->query('SELECT id,name FROM categories WHERE is_active=1 ORDER BY sort_order,name')->fetchAll();
    }

    /** @param array<string,mixed> $data */
    public function update(int $actorId, int $couponId, array $data): void
    {
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actorId);
            // Keep the same merchant -> coupon lock ordering as submit/edit/moderate.
            $merchantQuery = $this->db->prepare('SELECT merchant_id FROM coupons WHERE id=? AND deleted_at IS NULL');
            $merchantQuery->execute([$couponId]);
            $merchantId = (int)$merchantQuery->fetchColumn();
            if ($merchantId <= 0 || $merchantId !== (int)$data['merchant_id']) throw new RuntimeException('Coupon đã thay đổi hoặc không tồn tại.');
            $merchantLock = $this->db->prepare('SELECT id FROM merchants WHERE id=? FOR UPDATE');
            $merchantLock->execute([$merchantId]);
            if (!$merchantLock->fetch()) throw new RuntimeException('Không tìm thấy Merchant.');
            $couponLock = $this->db->prepare('SELECT * FROM coupons WHERE id=? AND deleted_at IS NULL FOR UPDATE');
            $couponLock->execute([$couponId]);
            $old = $couponLock->fetch();
            if (!is_array($old) || (int)$old['merchant_id'] !== $merchantId || in_array((string)$old['publication_status'], ['ARCHIVED','EXPIRED'], true)) {
                throw new RuntimeException('Coupon không tồn tại hoặc đã lưu trữ/hết hạn thủ công.');
            }
            $category = $this->db->prepare('SELECT id FROM categories WHERE id=? AND is_active=1 LOCK IN SHARE MODE');
            $category->execute([(int)$data['category_id']]);
            if (!$category->fetch()) throw new RuntimeException('Danh mục đã bị tắt hoặc không tồn tại.');
            $changes = [];
            foreach (['category_id','title','code','description','terms','discount_type','discount_value','cashback_unit','min_order_amount','max_discount_amount','usage_channel','promotion_url','starts_at','ends_at'] as $field) {
                $value = $data[in_array($field, ['starts_at','ends_at'], true) ? $field . '_utc' : $field];
                $equal = $old[$field] === null || $value === null
                    ? $old[$field] === $value
                    : (in_array($field, ['discount_value','min_order_amount','max_discount_amount'], true)
                        ? (float)$old[$field] === (float)$value : (string)$old[$field] === (string)$value);
                if (!$equal) $changes[] = $field;
            }
            if ($changes === []) {
                $this->db->commit();
                return;
            }
            if ($data['code'] !== null) {
                $duplicate = $this->db->prepare(
                    "SELECT COUNT(*) FROM coupons WHERE id<>? AND merchant_id=? AND code_normalized=UPPER(TRIM(?)) " .
                    "AND deleted_at IS NULL AND moderation_status IN ('PENDING','APPROVED') " .
                    "AND publication_status='ENABLED' AND starts_at<? AND ends_at>?"
                );
                $duplicate->execute([$couponId, $merchantId, $data['code'], $data['ends_at_utc'], $data['starts_at_utc']]);
                if ((int)$duplicate->fetchColumn() !== 0) throw new RuntimeException('Mã coupon trùng thời gian với coupon khác của Merchant.');
            }
            $sql = 'UPDATE coupons SET category_id=?,title=?,code=?,description=?,terms=?,discount_type=?,discount_value=?,cashback_unit=?,min_order_amount=?,max_discount_amount=?,usage_channel=?,promotion_url=?,starts_at=?,ends_at=? WHERE id=?';
            $this->db->prepare($sql)->execute([
                $data['category_id'], $data['title'], $data['code'], $data['description'], $data['terms'],
                $data['discount_type'], $data['discount_value'], $data['cashback_unit'], $data['min_order_amount'],
                $data['max_discount_amount'], $data['usage_channel'], $data['promotion_url'],
                $data['starts_at_utc'], $data['ends_at_utc'], $couponId,
            ]);
            // A title/category correction does not change whether the coupon works.
            $resetVotes = array_diff($changes, ['title','category_id']) !== [];
            if ($resetVotes) $this->db->prepare('DELETE FROM votes WHERE coupon_id=?')->execute([$couponId]);
            $note = 'Admin chỉnh sửa coupon' . ($resetVotes ? '; đặt lại xác minh do điều kiện thay đổi' : '; giữ xác minh hiện có');
            $this->recordHistory($couponId, $actorId, $old, (string)$old['moderation_status'], (string)$old['publication_status'], $note);
            $this->recordActivity($actorId, $couponId, 'COUPON_EDIT', $note);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function expire(int $actorId, int $couponId, string $reason): void
    {
        $this->changePublication($actorId, $couponId, 'EXPIRED', $reason, 'COUPON_EXPIRE');
    }

    public function delete(int $actorId, int $couponId, string $reason): void
    {
        $this->changePublication($actorId, $couponId, 'ARCHIVED', $reason, 'COUPON_DELETE');
    }

    /** @param array<string,mixed> $old */
    private function recordHistory(int $couponId, int $actorId, array $old, string $newModeration, string $newPublication, string $note): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO coupon_status_history(coupon_id,actor_id,old_moderation,new_moderation,old_publication,new_publication,note) VALUES(?,?,?,?,?,?,?)'
        );
        $stmt->execute([$couponId, $actorId, $old['moderation_status'], $newModeration, $old['publication_status'], $newPublication, $note]);
    }

    private function recordActivity(int $actorId, int $couponId, string $action, string $note): void
    {
        $stmt = $this->db->prepare('INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(?,?,?,?)');
        $stmt->execute([$actorId, $couponId, $action, mb_substr($note, 0, 500)]);
    }

    private function changePublication(int $actorId, int $couponId, string $state, string $reason, string $action): void
    {
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actorId);
            $merchantQuery = $this->db->prepare('SELECT merchant_id FROM coupons WHERE id=? AND deleted_at IS NULL');
            $merchantQuery->execute([$couponId]);
            $merchantId = (int)$merchantQuery->fetchColumn();
            if ($merchantId <= 0) throw new RuntimeException('Không tìm thấy coupon hoặc đã xóa.');
            $merchantLock = $this->db->prepare('SELECT id FROM merchants WHERE id=? FOR UPDATE');
            $merchantLock->execute([$merchantId]);
            if (!$merchantLock->fetch()) throw new RuntimeException('Không tìm thấy Merchant.');
            $lock = $this->db->prepare('SELECT moderation_status,publication_status FROM coupons WHERE id=? AND deleted_at IS NULL FOR UPDATE');
            $lock->execute([$couponId]);
            $old = $lock->fetch();
            if (!is_array($old)) throw new RuntimeException('Không tìm thấy coupon hoặc đã xóa.');
            if ((string)$old['publication_status'] === 'ARCHIVED') throw new RuntimeException('Coupon đã bị xóa.');
            if ($state === 'EXPIRED' && ((string)$old['moderation_status'] !== 'APPROVED' || (string)$old['publication_status'] !== 'ENABLED')) {
                throw new RuntimeException('Chỉ có thể buộc hết hạn coupon đã được duyệt và đang bật.');
            }
            if ($state === 'ARCHIVED') {
                $this->db->prepare("UPDATE coupons SET publication_status='ARCHIVED',deleted_at=UTC_TIMESTAMP(),reviewed_by=?,reviewed_at=UTC_TIMESTAMP(),review_note=? WHERE id=?")
                    ->execute([$actorId, $reason, $couponId]);
            } else {
                $this->db->prepare("UPDATE coupons SET publication_status='EXPIRED',reviewed_by=?,reviewed_at=UTC_TIMESTAMP(),review_note=? WHERE id=?")
                    ->execute([$actorId, $reason, $couponId]);
            }
            $this->recordHistory($couponId, $actorId, $old, (string)$old['moderation_status'], $state, $reason);
            $this->recordActivity($actorId, $couponId, $action, $reason);
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}

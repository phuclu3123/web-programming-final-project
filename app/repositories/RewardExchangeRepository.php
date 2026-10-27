<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Services\RewardExchangePolicy;
use DomainException;
use Throwable;

/** All points and fulfilment mutations are transactional and server-authoritative. */
final class RewardExchangeRepository
{
    public function __construct(private Database $db) {}

    public function catalog(): array
    {
        return $this->db->query('SELECT id,code,title,description,cost_points FROM reward_items '
            . 'WHERE is_active=1 AND cost_points>0 ORDER BY cost_points,id')->fetchAll();
    }

    public function wallet(int $userId): array
    {
        $earned = $this->db->prepare('SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE user_id=?');
        $earned->execute([$userId]);
        $reserved = $this->db->prepare("SELECT COALESCE(SUM(points_spent),0) FROM reward_redemptions "
            . "WHERE user_id=? AND status IN ('PENDING','FULFILLED')");
        $reserved->execute([$userId]);
        $lifetime = (int)$earned->fetchColumn();
        $committed = (int)$reserved->fetchColumn();
        return [
            'earned' => $lifetime,
            'committed' => $committed,
            'available' => RewardExchangePolicy::balance($lifetime, $committed)
        ];
    }

    public function history(int $userId, int $limit = 30): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare('SELECT r.id,r.points_spent,r.status,r.admin_note,r.created_at,r.processed_at, '
            . 'i.title AS reward_title FROM reward_redemptions r '
            . 'JOIN reward_items i ON i.id=r.reward_id WHERE r.user_id=? '
            . 'ORDER BY r.id DESC LIMIT ' . $limit);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function redeem(int $userId, int $rewardId, string $key): int
    {
        if ($userId <= 0 || $rewardId <= 0) throw new DomainException('Yêu cầu đổi thưởng không hợp lệ.');
        $key = RewardExchangePolicy::idempotencyKey($key);
        $this->db->beginTransaction();
        try {
            // This row is a per-user mutex. Two concurrent requests cannot both spend the same balance.
            $owner = $this->db->prepare("SELECT id FROM users WHERE id=? AND status='ACTIVE' "
                . 'AND deleted_at IS NULL AND role IN (\'USER\',\'MERCHANT\') FOR UPDATE');
            $owner->execute([$userId]);
            if ($owner->fetch() === false) throw new DomainException('Tài khoản không thể đổi thưởng.');

            $prior = $this->db->prepare('SELECT id,reward_id FROM reward_redemptions '
                . 'WHERE user_id=? AND idempotency_key=? LIMIT 1');
            $prior->execute([$userId, $key]);
            $previous = $prior->fetch();
            if (is_array($previous)) {
                if ((int)$previous['reward_id'] !== $rewardId) {
                    throw new DomainException('Mã yêu cầu đã được dùng cho phần thưởng khác.');
                }
                $this->db->commit();
                return (int)$previous['id'];
            }

            $itemStmt = $this->db->prepare('SELECT cost_points FROM reward_items '
                . 'WHERE id=? AND is_active=1 AND cost_points>0 LIMIT 1');
            $itemStmt->execute([$rewardId]);
            $item = $itemStmt->fetch();
            if (!is_array($item)) throw new DomainException('Phần thưởng không còn khả dụng.');
            $cost = (int)$item['cost_points'];

            $wallet = $this->wallet($userId);
            RewardExchangePolicy::assertCanRedeem($wallet['earned'], $wallet['committed'], $cost);

            $insert = $this->db->prepare('INSERT INTO reward_redemptions '
                . '(user_id,reward_id,points_spent,idempotency_key,status) VALUES (?,?,?,?,\'PENDING\')');
            $insert->execute([$userId, $rewardId, $cost, $key]);
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    public function adminQueue(): array
    {
        return $this->db->query("SELECT r.id,r.user_id,r.points_spent,r.status,r.created_at,r.admin_note, "
            . 'r.processed_at,i.title AS reward_title,u.full_name AS user_name,u.email AS user_email '
            . 'FROM reward_redemptions r JOIN reward_items i ON i.id=r.reward_id '
            . 'JOIN users u ON u.id=r.user_id '
            . "ORDER BY CASE WHEN r.status='PENDING' THEN 0 ELSE 1 END,r.id DESC LIMIT 80")->fetchAll();
    }

    /** FULFILLED requires proof/notes of manual delivery; CANCELLED automatically returns spendable points. */
    public function review(int $id, int $adminId, string $action, string $note): bool
    {
        if ($id <= 0 || $adminId <= 0 || !in_array($action, ['FULFILLED', 'CANCELLED'], true)) {
            throw new DomainException('Thao tác xử lý không hợp lệ.');
        }
        $note = RewardExchangePolicy::adminNote($note);
        // Read owner before transaction to maintain identical locking order to redeem().
        $ownerStmt = $this->db->prepare('SELECT user_id FROM reward_redemptions WHERE id=? LIMIT 1');
        $ownerStmt->execute([$id]);
        $ownerId = $ownerStmt->fetchColumn();
        if ($ownerId === false) return false;

        $this->db->beginTransaction();
        try {
            $lockUser = $this->db->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');
            $lockUser->execute([(int)$ownerId]);
            if ($lockUser->fetch() === false) throw new DomainException('Không tìm thấy chủ sở hữu giao dịch.');
            $locked = $this->db->prepare('SELECT user_id,status FROM reward_redemptions WHERE id=? FOR UPDATE');
            $locked->execute([$id]);
            $row = $locked->fetch();
            if (!is_array($row) || (int)$row['user_id'] !== (int)$ownerId || $row['status'] !== 'PENDING') {
                $this->db->rollBack();
                return false;
            }
            $update = $this->db->prepare('UPDATE reward_redemptions '
                . 'SET status=?,admin_note=?,processed_by=?,processed_at=UTC_TIMESTAMP() '
                . "WHERE id=? AND status='PENDING'");
            $update->execute([$action, $note, $adminId, $id]);
            if ($update->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }
            $this->db->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }
}

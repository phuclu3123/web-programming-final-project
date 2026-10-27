<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use DomainException;

/** The database enforces ownership and duplicate-answer constraints together with server-side checks. */
final class DealRequestRepository
{
    public function __construct(private Database $db) {}

    public function page(string $status, int $page, int $perPage = 10): array
    {
        $perPage = max(1, min(30, $perPage));
        $where = $status === 'ALL' ? '' : ' WHERE d.status=?';
        $params = $status === 'ALL' ? [] : [$status];
        $count = $this->db->prepare('SELECT COUNT(*) FROM deal_requests d' . $where);
        $count->execute($params);
        $total = (int)$count->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $stmt = $this->db->prepare('SELECT d.id,d.title,d.body,d.status,d.user_id,d.created_at,u.full_name AS author_name, '
            . '(SELECT COUNT(*) FROM deal_request_replies r WHERE r.request_id=d.id) AS reply_count '
            . 'FROM deal_requests d JOIN users u ON u.id=d.user_id' . $where
            . ' ORDER BY CASE WHEN d.status=\'OPEN\' THEN 0 ELSE 1 END,d.created_at DESC,d.id DESC LIMIT ? OFFSET ?');
        $stmt->execute([...$params, $perPage, ($page - 1) * $perPage]);
        return ['items' => $stmt->fetchAll(), 'total' => $total, 'pages' => $pages, 'page' => $page];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT d.*,u.full_name AS author_name FROM deal_requests d '
            . 'JOIN users u ON u.id=d.user_id WHERE d.id=? LIMIT 1');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        return is_array($item) ? $item : null;
    }

    public function replies(int $id): array
    {
        $stmt = $this->db->prepare('SELECT r.id,r.user_id,r.coupon_id,r.note,r.created_at, '
            . 'u.full_name AS responder_name,c.title AS coupon_title,m.name AS merchant_name,s.effective_status '
            . 'FROM deal_request_replies r JOIN users u ON u.id=r.user_id '
            . 'JOIN coupons c ON c.id=r.coupon_id JOIN merchants m ON m.id=c.merchant_id '
            . 'JOIN v_coupon_status s ON s.id=c.id WHERE r.request_id=? ORDER BY r.created_at ASC,r.id ASC');
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function create(int $actorId, string $title, string $body): int
    {
        // Lightweight abuse limit: no more than five open requests per account.
        $count = $this->db->prepare("SELECT COUNT(*) FROM deal_requests WHERE user_id=? AND status='OPEN'");
        $count->execute([$actorId]);
        if ((int)$count->fetchColumn() >= 5) throw new DomainException('Bạn chỉ có thể mở tối đa 5 yêu cầu cùng lúc.');
        $stmt = $this->db->prepare('INSERT INTO deal_requests(user_id,title,body) VALUES(?,?,?)');
        $stmt->execute([$actorId, $title, $body]);
        return (int)$this->db->lastInsertId();
    }

    public function addReply(int $requestId, int $actorId, int $couponId, string $note): void
    {
        // Sử dụng INSERT ... SELECT để kiểm tra điều kiện trước khi thêm phản hồi.
        // Chỉ cho phép trả lời yêu cầu đang mở, không tự trả lời và coupon phải có trạng thái ACTIVE.
        $stmt = $this->db->prepare("INSERT IGNORE INTO deal_request_replies(request_id,user_id,coupon_id,note) "
            . "SELECT d.id,?,s.id,? FROM deal_requests d "
            . "JOIN v_coupon_status s ON s.id=? AND s.effective_status='ACTIVE' "
            . "WHERE d.id=? AND d.status='OPEN' AND d.user_id<>?");
        $stmt->execute([$actorId, $note, $couponId, $requestId, $actorId]);
        if ($stmt->rowCount() !== 1) throw new DomainException('Không thể trả lời: yêu cầu đã đóng, coupon không hợp lệ, hoặc coupon đã được đề xuất.');
    }

    public function close(int $id, int $actorId): bool
    {
        $stmt = $this->db->prepare("UPDATE deal_requests SET status='CLOSED', closed_at=UTC_TIMESTAMP() WHERE id=? AND user_id=? AND status='OPEN'");
        $stmt->execute([$id, $actorId]);
        return $stmt->rowCount() === 1;
    }
}

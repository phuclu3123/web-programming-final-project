<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\Database;

/** Notifications are derived from actual workflow records, scoped to the actor. */
final class NotificationRepository implements \App\Contracts\NotificationRepositoryInterface
{
    public function __construct(private Database $db) {}

    public function forActor(array $actor): array
    {
        $items=$this->workflowEvents($actor);
        $q=$this->db->prepare("SELECT n.event_key,CONCAT(CASE WHEN sender.deleted_at IS NULL THEN sender.full_name ELSE 'Một thành viên' END,
            CASE n.kind WHEN 'ARTICLE_REPLY' THEN ' đã trả lời bình luận của bạn' ELSE ' đã thích bình luận của bạn' END) AS title,
            n.created_at AS occurred_at,CONCAT('/articles/',a.slug,'?comment=',c.id,'#article-comment-',c.id) AS path
            FROM notifications n JOIN users sender ON sender.id=n.actor_id JOIN comments c ON c.id=n.comment_id
            JOIN comments subject ON subject.id=n.subject_comment_id JOIN articles a ON a.id=c.article_id
            WHERE n.user_id=? AND n.kind IS NOT NULL AND n.active=1 AND c.status='VISIBLE' AND subject.status='VISIBLE'
            AND a.status='PUBLISHED' AND a.published_at<=UTC_TIMESTAMP() ORDER BY n.created_at DESC,n.id DESC LIMIT 30");
        $q->execute([(int)$actor['id']]);$items=[...$items,...$q->fetchAll()];
        usort($items,static fn(array $a,array $b):int=>strcmp($b['occurred_at'],$a['occurred_at'])?:strcmp($b['event_key'],$a['event_key']));$items=array_slice($items,0,30);
        if(!$items)return [];
        $keys=array_column($items,'event_key');$q=$this->db->prepare('SELECT event_key,read_at FROM notifications WHERE user_id=? AND event_key IN ('.implode(',',array_fill(0,count($keys),'?')).')');
        $q->execute([(int)$actor['id'],...$keys]);$read=array_column($q->fetchAll(),'read_at','event_key');
        foreach($items as &$item)$item['read_at']=$read[$item['event_key']]??null;
        unset($item);return $items;
    }

    public function markRead(array $actor,array $eventKeys,bool $all=false):void
    {
        if(!$eventKeys && !$all)return;
        $this->db->beginTransaction();
        try{
            $q=$this->db->prepare("SELECT id FROM users WHERE id=? AND status='ACTIVE' AND deleted_at IS NULL FOR UPDATE");$q->execute([(int)$actor['id']]);
            if(!$q->fetch())throw new \App\Core\HttpError(403,'Tài khoản không được phép thao tác.');
            if($all)$this->db->prepare('UPDATE notifications SET read_at=UTC_TIMESTAMP() WHERE user_id=? AND kind IS NOT NULL AND read_at IS NULL')->execute([(int)$actor['id']]);
            $q=$this->db->prepare('INSERT INTO notifications(user_id,event_key,read_at) VALUES(?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE read_at=VALUES(read_at)');
            foreach(array_unique($eventKeys) as $key)$q->execute([(int)$actor['id'],$key]);
            $this->db->commit();
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    private function workflowEvents(array $actor): array
    {
        if ($actor['role'] === 'ADMIN') {
            $sql = "SELECT CONCAT('coupon-',id) AS event_key,CONCAT('Coupon chờ duyệt: ',title) AS title,
                created_at AS occurred_at,'/admin/coupons' AS path FROM coupons
                WHERE moderation_status='PENDING' AND deleted_at IS NULL
                UNION ALL SELECT CONCAT('request-',id), 'Yêu cầu quyền quản lý shop đang chờ duyệt',created_at,'/admin/merchant-requests'
                FROM merchant_requests WHERE status='PENDING'
                UNION ALL SELECT CONCAT('report-',id), 'Báo cáo coupon cần xử lý',created_at,'/admin/reports'
                FROM reports WHERE status='OPEN'
                ORDER BY occurred_at DESC,event_key DESC LIMIT 30";
            return $this->db->query($sql)->fetchAll();
        }
        $stmt = $this->db->prepare("SELECT CONCAT('review-',h.id) AS event_key,
            CONCAT(CASE h.new_moderation WHEN 'APPROVED' THEN 'Coupon đã được duyệt: ' ELSE 'Coupon bị từ chối: ' END,c.title) AS title,
            h.created_at AS occurred_at,'/my-coupons' AS path
            FROM activity_logs h JOIN coupons c ON c.id=h.coupon_id
            WHERE c.submitted_by=? AND c.deleted_at IS NULL AND h.action='COUPON_STATUS_CHANGE' AND h.new_moderation IN ('APPROVED','REJECTED') AND h.old_moderation<>h.new_moderation
            UNION ALL SELECT CONCAT('request-',r.id,'-',r.status),
            CONCAT('Yêu cầu shop ',m.name,CASE r.status WHEN 'APPROVED' THEN ' đã được duyệt' ELSE ' bị từ chối' END),
            r.reviewed_at,'/merchant/apply'
            FROM merchant_requests r JOIN merchants m ON m.id=r.merchant_id
            WHERE r.user_id=? AND r.status IN ('APPROVED','REJECTED') AND r.reviewed_at IS NOT NULL
            ORDER BY occurred_at DESC,event_key DESC LIMIT 30");
        $stmt->execute([(int)$actor['id'], (int)$actor['id']]);
        return $stmt->fetchAll();
    }
}

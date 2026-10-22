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
            FROM coupon_status_history h JOIN coupons c ON c.id=h.coupon_id
            WHERE c.submitted_by=? AND c.deleted_at IS NULL AND h.new_moderation IN ('APPROVED','REJECTED')
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

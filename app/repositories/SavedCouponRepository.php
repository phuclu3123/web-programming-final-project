<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\MysqliConnection;

/** Personalized saved-coupon reads. Every query is scoped to the authenticated user. */
final class SavedCouponRepository
{
    public function __construct(private MysqliConnection $db) {}

    public function countForUser(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function categoriesForUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT cat.id, cat.name, COUNT(*) AS saved_count ' .
            'FROM favorites f JOIN coupons c ON c.id=f.coupon_id ' .
            'JOIN categories cat ON cat.id=c.category_id ' .
            'WHERE f.user_id=? GROUP BY cat.id,cat.name ORDER BY cat.name ASC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /** @param array{keyword:string,category_id:int,status:string,sort:string} $filters
     *  @return array{items:array<int,array<string,mixed>>,total:int,page:int,pages:int}
     */
    public function paginated(int $userId, array $filters, int $requestedPage, int $pageSize = 9): array
    {
        $pageSize = max(1, min(30, $pageSize));
        $where = ['f.user_id = ?'];
        $params = [$userId];
        if ($filters['keyword'] !== '') {
            $where[] = '(c.title LIKE ? OR m.name LIKE ? OR COALESCE(c.code, \'\') LIKE ?)';
            $like = '%' . $filters['keyword'] . '%';
            array_push($params, $like, $like, $like);
        }
        if ($filters['category_id'] > 0) {
            $where[] = 'c.category_id = ?';
            $params[] = $filters['category_id'];
        }

        $status = "s.effective_status COLLATE utf8mb4_unicode_ci";
        $active = "_utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci";
        $scheduled = "_utf8mb4'SCHEDULED' COLLATE utf8mb4_unicode_ci";
        if ($filters['status'] === 'ACTIVE') {
            $where[] = "$status = $active";
        } elseif ($filters['status'] === 'SCHEDULED') {
            $where[] = "$status = $scheduled";
        } elseif ($filters['status'] === 'UNAVAILABLE') {
            $where[] = "$status NOT IN ($active,$scheduled)";
        }
        $whereSql = implode(' AND ', $where);
        $fromSql = ' FROM favorites f JOIN v_coupon_status s ON s.id=f.coupon_id ' .
            'JOIN coupons c ON c.id=f.coupon_id ' .
            'JOIN merchants m ON m.id=c.merchant_id ' .
            'JOIN categories cat ON cat.id=c.category_id ';

        $count = $this->db->prepare('SELECT COUNT(*)' . $fromSql . 'WHERE ' . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $pageSize));
        $page = max(1, min($requestedPage, $pages));
        $offset = ($page - 1) * $pageSize;

        $order = $filters['sort'] === 'EXPIRING'
            ? "CASE WHEN $status = $active THEN 0 ELSE 1 END, c.ends_at ASC, f.created_at DESC, c.id DESC"
            : 'f.created_at DESC, c.id DESC';
        $sql = 'SELECT c.id,c.title,c.code,c.discount_type,c.discount_value,c.cashback_unit, ' .
            'c.usage_channel,c.starts_at,c.ends_at,c.submitted_by,m.name AS merchant_name, ' .
            'cat.name AS category_name, f.created_at AS saved_at, f.is_eligible_for_rewards, ' .
            's.effective_status, ' .
            'CASE WHEN f.is_eligible_for_rewards = 1 AND f.user_id <> c.submitted_by ' .
            "AND NOT EXISTS (SELECT 1 FROM merchant_memberships mm WHERE mm.user_id=f.user_id AND mm.merchant_id=c.merchant_id AND mm.status='ACTIVE') " .
            'THEN 1 ELSE 0 END AS qualified_favorite ' .
            $fromSql . 'WHERE ' . $whereSql . ' ORDER BY ' . $order . ' LIMIT ? OFFSET ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($params, [$pageSize, $offset]));

        return ['items' => $stmt->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Core\Database;

final class CategoryRepository implements \App\Contracts\CategoryRepositoryInterface
{
    public function __construct(private Database $db) {}

    /** Danh mục thật và số coupon hiện có thể xem; không đếm mã chờ duyệt/hết hạn. */
    public function publicCounts(): array
    {
        return $this->db->query("SELECT cat.id,cat.name,cat.slug,cat.description,COUNT(c.id) AS coupon_count
            FROM categories cat LEFT JOIN v_coupon_status c ON c.category_id=cat.id
                AND c.effective_status COLLATE utf8mb4_vietnamese_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci
            WHERE cat.is_active=1 GROUP BY cat.id,cat.name,cat.slug,cat.description,cat.sort_order
            ORDER BY cat.sort_order,cat.name")->fetchAll();
    }

    /** @return Category[] */
    public function activeAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
        );
        return array_map(static fn(array $row): Category => Category::fromArray($row), $stmt->fetchAll());
    }
}

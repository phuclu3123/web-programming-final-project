<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Category;
use App\Core\MysqliConnection;

final class CategoryRepository
{
    public function __construct(private MysqliConnection $db) {}

    /** @return Category[] */
    public function activeAll(): array
    {
        $stmt = $this->db->query(
            'SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC'
        );
        return array_map(static fn(array $row): Category => Category::fromArray($row), $stmt->fetchAll());
    }
}

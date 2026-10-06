<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Merchant;
use PDO;

final class MerchantRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return Merchant[] */
    public function availableForCommunitySubmission(): array
    {
        $stmt = $this->pdo->query(
            "SELECT id, name, slug, status FROM merchants " .
            "WHERE deleted_at IS NULL AND status IN ('UNCLAIMED','PENDING','APPROVED') ORDER BY name ASC"
        );
        return array_map(static fn(array $row): Merchant => Merchant::fromArray($row), $stmt->fetchAll());
    }

    /** @return Merchant[] */
    public function publicFilterOptions(): array
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT m.id, m.name, m.slug, m.status FROM merchants m " .
            "JOIN v_coupon_status c ON c.merchant_id = m.id " .
            "WHERE c.effective_status = 'ACTIVE' ORDER BY m.name ASC"
        );
        return array_map(static fn(array $row): Merchant => Merchant::fromArray($row), $stmt->fetchAll());
    }
}

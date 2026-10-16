<?php
declare(strict_types=1);
namespace App\Contracts;

use RuntimeException;

/** Repository contract: services do not depend on a specific SQL driver. */
interface ArticleRepositoryInterface
{
    public function published(int $limit = 20): array;
    public function findPublished(string $slug): ?array;
    public function all(): array;
    public function find(int $id): ?array;
    public function save(int $actorId, ?int $id, array $values): int;
}

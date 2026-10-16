<?php
declare(strict_types=1);
namespace App\Contracts;

use RuntimeException;
use Throwable;

/** Repository contract: services do not depend on a specific SQL driver. */
interface InteractionRepositoryInterface
{
    public function setFavorite(int $actorId, int $couponId, bool $saved): void;
    public function setVote(int $actorId, int $couponId, string $vote): void;
    public function voteRestriction(int $actorId, int $couponId): ?string;
    public function isFavorite(int $actorId, int $couponId): bool;
    public function currentVote(int $actorId, int $couponId): ?string;
    public function stats(int $couponId): array;
    public function comments(int $couponId, int $limit = 50): array;
    public function addComment(int $actorId, int $couponId, string $body): array;
    public function createReport(int $actorId, int $couponId, string $reason, ?string $details): int;
    public function pendingReports(): array;
    public function resolveReport(int $actorId, int $reportId, string $status, string $note): void;
}

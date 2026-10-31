<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Transparent quality indicator for community coupon contributions.
 * Approval rate counts only reviewed submissions (PENDING is excluded).
 * It is not a ranking or a reputation badge: the brief gives no badge thresholds.
 */
final class ContributorQualityService
{
    /** @param array<string,mixed> $summary @return array{reviewed:int,pending:int,approvalRate:?int} */
    public static function fromSummary(array $summary): array
    {
        $approved = max(0, (int) ($summary['approved_count'] ?? 0));
        $rejected = max(0, (int) ($summary['rejected_count'] ?? 0));
        $submitted = max(0, (int) ($summary['submitted_count'] ?? 0));
        $reviewed = $approved + $rejected;

        return [
            'reviewed' => $reviewed,
            'pending' => max(0, $submitted - $reviewed),
            'approvalRate' => $reviewed === 0 ? null : (int) round(100 * $approved / $reviewed),
        ];
    }
}

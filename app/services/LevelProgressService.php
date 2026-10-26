<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Level progress is relative to the current level, not zero points.
 * Thresholds come from the database's levels table, not hard-coded levels.
 */
final class LevelProgressService
{
    public static function percentage(int $earnedPoints, int $currentLevelMin, ?int $nextLevelMin): int
    {
        // No next level means the user has reached the highest configured level.
        if ($nextLevelMin === null) {
            return 100;
        }

        $currentLevelMin = max(0, $currentLevelMin);
        $earnedPoints = max(0, $earnedPoints);

        // Defensive fallback if the database level thresholds are invalid.
        if ($nextLevelMin <= $currentLevelMin) {
            return 100;
        }

        $currentPoints = max(0, min($earnedPoints - $currentLevelMin, $nextLevelMin - $currentLevelMin));
        return (int) floor(100 * $currentPoints / ($nextLevelMin - $currentLevelMin));
    }
}

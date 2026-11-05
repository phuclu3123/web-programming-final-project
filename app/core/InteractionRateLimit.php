<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight limits for authenticated coupon interactions.
 * Reuses AuthRateLimit's file-lock storage, shared by PHP browser sessions
 * on a single server. Do not trust forwarded IP headers from clients.
 */
final class InteractionRateLimit
{
    /** @var array<string,list<array{int,int}>> [max requests, window seconds] */
    private const USER_RULES = [
        'favorite' => [[60, 60]],
        'vote' => [[40, 60]],
        'comment' => [[6, 60], [30, 3600]],
        'report' => [[3, 900], [10, 86400]],
    ];

    /** @var array<string,array{int,int}> High shared-IP ceilings, avoiding typical NAT collisions. */
    private const IP_RULES = [
        'comment' => [80, 60],
        'report' => [50, 900],
    ];

    public static function check(string $action, int $actorId): void
    {
        if ($actorId <= 0 || !isset(self::USER_RULES[$action])) {
            throw new \InvalidArgumentException('Invalid interaction rate-limit scope.');
        }

        // Use a verified backend actor ID, never a user-supplied account identifier.
        foreach (self::USER_RULES[$action] as [$maximum, $seconds]) {
            AuthRateLimit::hit('interaction-' . $action . '-user-' . $seconds, (string) $actorId, $maximum, $seconds);
        }

        if (isset(self::IP_RULES[$action])) {
            [$maximum, $seconds] = self::IP_RULES[$action];
            $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'local');
            AuthRateLimit::hit('interaction-' . $action . '-ip-' . $seconds, $ip, $maximum, $seconds);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;

/** Pure calculation for SQL LIMIT/OFFSET pagination; no HTML or database access. */
final class Pagination
{
    /**
     * @return array{total:int,page:int,pages:int,pageSize:int,offset:int}
     */
    public static function fromTotal(int $total, int $requestedPage, int $pageSize): array
    {
        if ($pageSize < 1) {
            throw new InvalidArgumentException('pageSize must be positive.');
        }

        $total = max(0, $total);
        $pages = max(1, intdiv($total, $pageSize) + (int) ($total % $pageSize !== 0));
        $page = min(max(1, $requestedPage), $pages);

        return [
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'pageSize' => $pageSize,
            'offset' => ($page - 1) * $pageSize,
        ];
    }
}

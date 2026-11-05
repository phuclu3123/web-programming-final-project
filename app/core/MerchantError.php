<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Preserve approved business messages without exposing arbitrary DB/Throwable text.
 * Unexpected errors are logged with a reference and location, not user input or SQL.
 */
final class MerchantError
{
    public static function message(Throwable $error, string $fallback, string $action): string
    {
        if ($error instanceof UserFacingException) {
            return $error->getMessage();
        }

        $reference = bin2hex(random_bytes(6));
        error_log(sprintf(
            '[CouponHub][merchant:%s][ref:%s] unexpected %s at %s:%d',
            $action,
            $reference,
            get_class($error),
            basename($error->getFile()),
            $error->getLine()
        ));

        return $fallback;
    }
}

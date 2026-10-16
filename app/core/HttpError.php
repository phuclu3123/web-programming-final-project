<?php
declare(strict_types=1);

namespace App\Core;

/** Lỗi HTTP có chủ đích; Router chuyển thành HTML hoặc JSON phù hợp request. */
final class HttpError extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }
}

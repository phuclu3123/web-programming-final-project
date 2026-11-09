<?php

declare(strict_types=1);

namespace App\Core;

// Thống nhất status, JSON và chuyển hướng khi kết thúc request.
final class Response
{
    /** @param array<string,mixed> $payload */
    public static function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function redirect(string $location, int $status = 302): void
    {
        header('Location: ' . $location, true, $status);
        exit;
    }
}

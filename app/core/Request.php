<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @var array<string,mixed>|null */
    private ?array $json = null;

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $scriptDir = rtrim($scriptDir, '/');

        if ($scriptDir !== '' && $scriptDir !== '/' && strpos($uri, $scriptDir) === 0) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $uri = '/' . ltrim((string) $uri, '/');
        return $uri === '//' ? '/' : (rtrim($uri, '/') ?: '/');
    }

    public function input(string $key, $default = null)
    {
        $data = $this->all();
        return $data[$key] ?? $default;
    }

    /** @return array<string,mixed> */
    public function all(): array
    {
        if ($this->isJson()) {
            if ($this->json === null) {
                $raw = file_get_contents('php://input') ?: '';
                $decoded = json_decode($raw, true);
                $this->json = is_array($decoded) ? $decoded : [];
            }
            return array_merge($_GET, $this->json);
        }

        return array_merge($_GET, $_POST);
    }

    public function isJson(): bool
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        return stripos($contentType, 'application/json') !== false;
    }

    public function expectsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return stripos($accept, 'application/json') !== false || strtolower($xhr) === 'xmlhttprequest';
    }
}

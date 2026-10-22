<?php
declare(strict_types=1);
namespace App\Contracts;

interface HttpClientInterface
{
    public function json(string $method, string $url, array $headers = [], ?string $body = null): array;
}

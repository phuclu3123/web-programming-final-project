<?php
declare(strict_types=1);
namespace App\Contracts;

// Hợp đồng HTTP JSON để service kiểm tra provider qua transport được cấp vào.
// Hợp đồng HTTP JSON để service kiểm tra provider qua transport được cấp vào.
interface HttpClientInterface
{
    public function json(string $method, string $url, array $headers = [], ?string $body = null): array;
}

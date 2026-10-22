<?php
declare(strict_types=1);
namespace App\Contracts;

interface OAuthRepositoryInterface
{
    public function resolve(array $identity, ?array $linkActor = null): array;
}

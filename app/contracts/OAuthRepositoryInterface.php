<?php
declare(strict_types=1);
namespace App\Contracts;

interface OAuthRepositoryInterface
{
    public function resolve(array $identity, ?array $linkActor = null): array;
    /** Both identities have been authenticated in the same server-side flow. */
    public function resolveLinked(array $google, array $facebook): array;
}

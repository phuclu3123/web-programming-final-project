<?php
declare(strict_types=1);
namespace App\Contracts;

use RuntimeException;
use Throwable;

/** Repository contract: services do not depend on a specific SQL driver. */
interface MerchantAccessRepositoryInterface
{
    public function requests(): array;
    public function memberships(): array;
    public function review(int $adminId,int $requestId,string $action,?string $note,bool $duplicateConfirmed = false): void;
    public function revoke(int $adminId,int $userId,int $merchantId,string $note): void;
}

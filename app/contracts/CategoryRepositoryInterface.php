<?php
declare(strict_types=1);
namespace App\Contracts;
use App\Models\Category;


/** Repository contract: services do not depend on a specific SQL driver. */
interface CategoryRepositoryInterface
{
    public function activeAll(): array;
    public function publicCounts(): array;
}

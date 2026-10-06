<?php

declare(strict_types=1);

namespace App\Models;

final class Category
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug
    ) {
    }

    /** @param array<string,mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self((int) $row['id'], (string) $row['name'], (string) $row['slug']);
    }
}

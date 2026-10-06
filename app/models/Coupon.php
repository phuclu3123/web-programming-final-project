<?php

declare(strict_types=1);

namespace App\Models;

final class Coupon
{
    /** @param array<string,mixed> $data */
    public function __construct(public array $data)
    {
    }

    public function id(): int
    {
        return (int) ($this->data['id'] ?? 0);
    }

    public function title(): string
    {
        return (string) ($this->data['title'] ?? '');
    }

    public function status(): string
    {
        return (string) ($this->data['effective_status'] ?? '');
    }

    public function toArray(): array
    {
        return $this->data;
    }
}

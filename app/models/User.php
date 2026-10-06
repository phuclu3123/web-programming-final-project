<?php

namespace App\Models;

final class User
{
    public function __construct(private array $data) {}
    public function id(): int
    {
        return (int)$this->data['id'];
    }
    public function role(): string
    {
        return $this->data['role'];
    }
    public function get(string $key): mixed
    {
        return $this->data[$key] ?? null;
    }
    public function isAdmin(): bool
    {
        return $this->role() === 'ADMIN';
    }
}

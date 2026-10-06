<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    public int $id;
    public string $email;
    public string $fullName;
    public string $role;
    public string $status;

    /** @param array<string,mixed> $row */
    public static function fromArray(array $row): self
    {
        $user = new self();
        $user->id = (int) $row['id'];
        $user->email = (string) $row['email'];
        $user->fullName = (string) $row['full_name'];
        $user->role = (string) $row['role'];
        $user->status = (string) $row['status'];
        return $user;
    }
}

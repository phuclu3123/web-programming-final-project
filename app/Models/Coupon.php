<?php

namespace App\Models;

final class Coupon
{
    public const TYPES = ['PERCENTAGE', 'FIXED_AMOUNT', 'FREE_SHIPPING', 'BUY_X_GET_Y', 'GIFT', 'CASHBACK'];
    public function __construct(private array $data) {}
    public function isPublic(): bool
    {
        return in_array($this->data['effective_status'] ?? '', ['ACTIVE', 'SCHEDULED', 'EXPIRED'], true) && $this->data['moderation_status'] === 'APPROVED';
    }
    public function isEditableBy(User $user): bool
    {
        return $user->isAdmin() || ($user->id() === (int)$this->data['submitted_by'] && $this->data['publication_status'] !== 'ARCHIVED');
    }
    public function toArray(): array
    {
        return $this->data;
    }
}

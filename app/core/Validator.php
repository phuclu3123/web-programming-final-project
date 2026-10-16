<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    public function required(string $field, $value, string $label): self
    {
        if ($value === null || trim((string) $value) === '') {
            $this->errors[$field] = $label . ' là bắt buộc.';
        }
        return $this;
    }

    public function email(string $field, $value, string $label): self
    {
        if ($value !== null && trim((string) $value) !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $label . ' không đúng định dạng.';
        }
        return $this;
    }

    public function minLength(string $field, $value, int $length, string $label): self
    {
        if ($value !== null && mb_strlen((string) $value) < $length) {
            $this->errors[$field] = sprintf('%s phải có ít nhất %d ký tự.', $label, $length);
        }
        return $this;
    }

    public function maxLength(string $field, $value, int $length, string $label): self
    {
        if ($value !== null && mb_strlen((string) $value) > $length) {
            $this->errors[$field] = sprintf('%s không được vượt quá %d ký tự.', $label, $length);
        }
        return $this;
    }

    public function same(string $field, $value, $other, string $label): self
    {
        if ((string) $value !== (string) $other) {
            $this->errors[$field] = $label . ' không khớp.';
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }
}

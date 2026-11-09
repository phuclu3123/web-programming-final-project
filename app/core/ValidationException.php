<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

// Giữ lỗi theo từng trường để form HTML và AJAX dùng cùng kết quả validation.
final class ValidationException extends RuntimeException
{
    /** @param array<string,string> $errors */
    public function __construct(private array $errors)
    {
        parent::__construct('Dữ liệu không hợp lệ.');
    }

    /** @return array<string,string> */
    public function errors(): array
    {
        return $this->errors;
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\MysqliConnection;

/** All public registrations are standard USER accounts. */
final class RegistrationService
{
    public function __construct(private MysqliConnection $db, private AuthService $auth, private MerchantService $merchants) {}
    public function register(array $input): array
    {
        return $this->auth->register((string)($input['full_name'] ?? ''), (string)($input['email'] ?? ''), (string)($input['password'] ?? ''));
    }
}

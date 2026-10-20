<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';
use App\Services\AccountService;

function assertValid(bool $got, string $label): void {
    if (!$got) throw new RuntimeException('FAIL: ' . $label);
}
assertValid(AccountService::validateProfile('Nguyễn Văn A', '+84901234567') === [], 'valid profile');
assertValid(isset(AccountService::validateProfile(' ', '+84901234567')['full_name']), 'no empty name');
assertValid(isset(AccountService::validateProfile(['fake'], '+84901234567')['full_name']), 'reject injected arrays');
assertValid(isset(AccountService::validateProfile('Demo', 'abc')['phone']), 'phone validation');
assertValid(AccountService::validateNewPassword('TestDemo123!'), 'strong password');
foreach (['testdemo123!', 'TESTDEMO123!', 'TestDemo!', 'TestDemo123', str_repeat('x', 80), ['TestDemo123!']] as $bad) {
    assertValid(!AccountService::validateNewPassword($bad), 'reject weak passwords');
}
echo "PASS: account validation and password policy (no database)\n";

<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
use App\Services\PasswordResetEmail;
$checks = 0;
function emailCheck(bool $ok, string $label): void {
    global $checks;
    if (!$ok) throw new RuntimeException('FAIL ' . $label);
    ++$checks; echo 'PASS ' . $label . PHP_EOL;
}
$otp = PasswordResetEmail::otp('012345');
emailCheck(str_contains($otp['text'], '012345') && str_contains($otp['html'], '>012345<'), 'leading-zero code remains intact in both formats');
emailCheck(str_contains($otp['text'], '10 phút') && str_contains($otp['html'], '10 phút'), 'OTP expiry matches the ten-minute recovery flow');
emailCheck(!str_contains($otp['subject'], '012345'), 'mail subject does not disclose recovery code');
foreach (['', '12345', '1234567', '<img src=x>', "123456\n"] as $bad) {
    try { PasswordResetEmail::otp($bad); throw new LogicException('Accepted bad code'); }
    catch (InvalidArgumentException) { emailCheck(true, 'invalid recovery code is rejected'); }
}
$url = 'https://couponhub.example/reset-password?token=abc&next=%22test%22';
$link = PasswordResetEmail::link($url);
emailCheck(str_contains($link['text'], $url), 'plain-text fallback preserves full recovery URL');
emailCheck(str_contains($link['html'], 'token=abc&amp;next=') && !str_contains($link['html'], 'token=abc&next='), 'HTML recovery link escapes query delimiters');
emailCheck(str_contains($link['text'], '30 phút') && str_contains($link['html'], '30 phút'), 'link expiry matches the thirty-minute recovery flow');
foreach (['javascript:alert(1)', 'data:text/html,test', 'file:///tmp/reset', 'invalid'] as $bad) {
    try { PasswordResetEmail::link($bad); throw new LogicException('Accepted bad URL'); }
    catch (InvalidArgumentException) { emailCheck(true, 'unsafe recovery URL is rejected'); }
}
foreach ([$otp, $link] as $mail) {
    emailCheck(!preg_match('/<script|<iframe|<img|@import|url\(/i', $mail['html']), 'mail needs no remote assets, scripts or tracking pixels');
    emailCheck(str_contains($mail['html'], 'lang="vi"') && str_contains($mail['html'], 'role="presentation"'), 'Vietnamese document and decorative-table semantics are provided');
    emailCheck(str_contains($mail['text'], 'Mật khẩu của bạn sẽ không thay đổi.'), 'unexpected requests have clear guidance without alarming claims');
}
echo "PASS $checks email checks; no messages sent and no database writes." . PHP_EOL;

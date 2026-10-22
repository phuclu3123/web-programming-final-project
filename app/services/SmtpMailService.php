<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use RuntimeException;

/** Small SMTP transport for local Mailpit and authenticated TLS/SSL SMTP. */
final class SmtpMailService implements \App\Contracts\AuthMailerInterface
{
    public function sendPasswordReset(string $recipient, string $resetUrl): void
    {
        $this->sendText($recipient, 'Đặt lại mật khẩu CouponHub', "Mở liên kết để đặt mật khẩu mới (30 phút, chỉ dùng một lần):\n" . $resetUrl . "\nNếu không yêu cầu, hãy bỏ qua email này.");
    }

    public function sendOtp(string $recipient, string $code): void
    {
        $this->sendText($recipient, 'Mã khôi phục mật khẩu CouponHub', "Mã xác minh của bạn: " . $code . "\nMã có hiệu lực 10 phút, chỉ sử dụng một lần. Không cung cấp mã cho bất kỳ ai. Nếu không yêu cầu, hãy bỏ qua email này.");
    }

    private function sendText(string $recipient, string $subjectText, string $body): void
    {
        $cfg = (array) App::config('mail', []);
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (int) ($cfg['port'] ?? 1025);
        $encryption = strtolower((string) ($cfg['encryption'] ?? 'none'));
        $user = (string) ($cfg['username'] ?? '');
        $password = (string) ($cfg['password'] ?? '');
        $from = (string) ($cfg['from_email'] ?? '');
        $timeout = max(3, min(20, (int) ($cfg['timeout'] ?? 8)));

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid SMTP email address');
        }
        if ($host === '' || $port < 1 || $port > 65535 || !in_array($encryption, ['none', 'tls', 'ssl'], true)) {
            throw new RuntimeException('Invalid SMTP configuration');
        }
        $local = in_array(strtolower($host), ['localhost', '127.0.0.1', '::1'], true);
        if (!$local && $encryption === 'none') {
            throw new RuntimeException('Remote SMTP must use TLS');
        }

        $context = stream_context_create(['ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
            'allow_self_signed' => false,
        ]]);
        $target = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $errorNo = 0;
        $errorText = '';
        $socket = @stream_socket_client($target, $errorNo, $errorText, $timeout, STREAM_CLIENT_CONNECT, $context);
        if (!is_resource($socket)) {
            throw new RuntimeException('SMTP server not available');
        }

        stream_set_timeout($socket, $timeout);
        try {
            $this->expect($socket, [220]);
            $this->command($socket, 'EHLO couponhub.local', [250]);
            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if ($crypto !== true) {
                    throw new RuntimeException('SMTP STARTTLS handshake failed');
                }
                $this->command($socket, 'EHLO couponhub.local', [250]);
            }
            if ($user !== '') {
                if ($encryption === 'none' && !$local) {
                    throw new RuntimeException('SMTP auth requires TLS');
                }
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode($user), [334]);
                $this->command($socket, base64_encode($password), [235]);
            }
            $this->command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $recipient . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);

            $subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';
            $mime = 'From: CouponHub <' . $from . ">\r\n"
                . 'To: <' . $recipient . ">\r\n"
                . 'Subject: ' . $subject . "\r\n"
                . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n"
                . "Content-Transfer-Encoding: base64\r\n"
                . "Auto-Submitted: auto-generated\r\n"
                . "\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n")) . "\r\n";
            // Base64 body avoids SMTP dot-stuffing and Unicode transfer issues.
            $this->writeAll($socket, $mime . ".\r\n");
            $this->expect($socket, [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function writeAll($socket, string $data): void
    {
        $length = strlen($data);
        for ($offset = 0; $offset < $length;) {
            $sent = fwrite($socket, substr($data, $offset));
            if (!is_int($sent) || $sent < 1) {
                throw new RuntimeException('SMTP write failed');
            }
            $offset += $sent;
        }
    }

    /** @param resource $socket @param int[] $expected */
    private function command($socket, string $command, array $expected): void
    {
        $this->writeAll($socket, $command . "\r\n");
        $this->expect($socket, $expected);
    }

    /** @param resource $socket @param int[] $expected */
    private function expect($socket, array $expected): void
    {
        do {
            $line = fgets($socket, 2048);
            if (!is_string($line) || !preg_match('/^([0-9]{3})([ -])/', $line, $matches)) {
                throw new RuntimeException('Invalid SMTP response or timeout');
            }
            $code = (int) $matches[1];
        } while ($matches[2] === '-');
        if (!in_array($code, $expected, true)) {
            // Do not log the full response; it may contain provider details.
            throw new RuntimeException('SMTP rejected request (' . $code . ')');
        }
    }
}

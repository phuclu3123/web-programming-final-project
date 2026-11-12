<?php
declare(strict_types=1);
namespace App\Services;

use App\Contracts\HttpClientInterface;
use App\Core\HttpError;

/** Same switch on the form and server; partial configuration never bypasses verification. */
final class TurnstileService
{
    public function __construct(private HttpClientInterface $http, private array $settings) {}

    public function enabled(): bool
    {
        $mode = $this->settings['mode'] ?? 'auto';
        if (!in_array($mode, ['auto', 'off', 'required'], true)) throw new HttpError(503, 'Cấu hình chống bot không hợp lệ.');
        return $mode === 'required' || ($mode === 'auto' && (($this->settings['site_key'] ?? '') !== '' || ($this->settings['secret_key'] ?? '') !== ''));
    }

    public function siteKey(): string
    {
        $key = $this->settings['site_key'] ?? '';
        return is_string($key) && !preg_match('/[\x00-\x20]/', $key) ? $key : '';
    }

    public function hostnames(): array
    {
        $value = $this->settings['hostnames'] ?? '';
        if ($value === '') $value = parse_url((string)config('app.base_url', ''), PHP_URL_HOST) ?: '';
        return array_values(array_filter(array_map(static fn($host) => strtolower(trim($host)), explode(',', $value)), static fn($host) => (bool)preg_match('/\A[a-z0-9.-]+\z/D', $host)));
    }

    // Gửi token tới Siteverify rồi kiểm tra success, hostname và action; lỗi xác minh phải chặn form.
    public function verify(mixed $token, string $action, ?string $ip = null): void
    {
        if (!$this->enabled()) return;
        $secret = $this->settings['secret_key'] ?? '';
        if ($this->siteKey() === '' || !is_string($secret) || $secret === '' || preg_match('/[\x00-\x20]/', $secret) || !$this->hostnames()) {
            throw new HttpError(503, 'Chưa cấu hình đầy đủ xác minh chống bot.');
        }
        if (!is_string($token) || trim($token) === '' || strlen($token) > 2048) throw new HttpError(422, 'Vui lòng hoàn tất xác minh chống bot rồi thử lại.');
        $body = ['secret' => $secret, 'response' => $token];
        if ($ip !== null && filter_var($ip, FILTER_VALIDATE_IP)) $body['remoteip'] = $ip;
        try {
            $result = $this->http->json('POST', 'https://challenges.cloudflare.com/turnstile/v0/siteverify', ['Content-Type: application/json'], json_encode($body, JSON_THROW_ON_ERROR));
        } catch (\Throwable $error) {
            // Do not log provider response, secret or submitted token.
            throw new HttpError(503, 'Chưa thể xác minh chống bot. Vui lòng thử lại sau.');
        }
        if (($result['success'] ?? null) !== true || ($result['action'] ?? null) !== $action
            || !is_string($result['hostname'] ?? null) || !in_array(strtolower($result['hostname']), $this->hostnames(), true)) {
            throw new HttpError(403, 'Xác minh chống bot không hợp lệ hoặc đã hết hạn. Vui lòng thử lại.');
        }
    }
}

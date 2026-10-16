<?php
declare(strict_types=1);

namespace App\Core;



/** ---- CHUẨN BỊ OAUTH: state/PKCE/callback; chưa đổi code lấy token/tạo user ---- */
final class OAuthPreparation
{
    public function __construct(private readonly array $config) {}

    public function provider(string $name): array
    {
        if (!in_array($name, ['github', 'google'], true)) throw new HttpError(404, 'Nhà cung cấp không hợp lệ.');
        return $this->config['providers'][$name];
    }

    public function status(string $name): array
    {
        $provider = $this->provider($name);
        $missing = [];
        foreach (['client_id', 'client_secret'] as $key) if ($provider[$key] === '') $missing[] = $key;
        $url = parse_url($this->config['base_url']);
        if (!$url || empty($url['host']) || isset($url['user']) || isset($url['query']) || isset($url['fragment'])
            || !in_array($url['scheme'] ?? '', ['http', 'https'], true)
            || (($url['scheme'] ?? '') === 'http' && !in_array($url['host'], ['localhost', '127.0.0.1'], true))) {
            $missing[] = 'base_url';
        }
        // Trạng thái không bao giờ chứa app secret, token hay mã xác thực.
        return ['provider' => $name, 'callback' => $provider['callback'], 'ready' => $missing === [], 'missing' => $missing];
    }

    /** Probe thử callback nội bộ; provider mode tạo URL thật để tự kiểm tra App. */
    public function begin(string $name, bool $probe = false): array
    {
        $provider = $this->provider($name);
        if (!$probe && !$this->status($name)['ready']) throw new HttpError(503, 'Chưa đủ cấu hình OAuth. Xem trang chuẩn bị OAuth.');
        Session::start();
        $pending = $_SESSION['oauth_pending'] ?? [];
        foreach ($pending as $key => $entry) if (($entry['expires'] ?? 0) < time()) unset($pending[$key]);
        if (count($pending) >= 5) array_shift($pending);
        $state = bin2hex(random_bytes(32));
        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $pending[$state] = ['provider' => $name, 'expires' => time() + 600, 'probe' => $probe, 'verifier' => $verifier];
        // ---- GOOGLE OIDC: lưu nonce để bước xác minh ID token sau này đối chiếu ----
        // Nonce khác state: state đối chiếu callback; nonce đối chiếu claim trong ID token.
        if ($name === 'google') $pending[$state]['nonce'] = bin2hex(random_bytes(32));
        $_SESSION['oauth_pending'] = $pending;
        if ($probe) {
            // Relative URL để chạy thử dù base URL cấu hình chưa khớp server local.
            return ['url' => 'api/oauth/' . $name . '-callback.php?state=' . $state . '&code=LOCAL_CALLBACK_PROBE'];
        }
        $params = ['client_id' => $provider['client_id'], 'redirect_uri' => $provider['callback'],
            'state' => $state, 'scope' => $provider['scope'], 'response_type' => 'code'];
        // Cả Google và GitHub dùng PKCE S256; verifier chỉ giữ trong session server.
        $params['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $params['code_challenge_method'] = 'S256';
        if ($name === 'google') $params['nonce'] = $pending[$state]['nonce'];
        $endpoint = $provider['authorize_url'];
        return ['url' => $endpoint . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986)];
    }

    public function consume(string $name, array $query): array
    {
        $this->provider($name);
        Session::start();
        $state = $query['state'] ?? '';
        if (!is_string($state) || !preg_match('/\A[a-f0-9]{64}\z/', $state)) throw new HttpError(403, 'State không hợp lệ.');
        $entry = $_SESSION['oauth_pending'][$state] ?? null;
        unset($_SESSION['oauth_pending'][$state]); // Single-use, kể cả provider trả lỗi.
        if (!$entry || $entry['provider'] !== $name || $entry['expires'] < time()) {
            throw new HttpError(403, 'Callback không khớp phiên hoặc đã hết hạn.');
        }
        if (isset($query['error'])) throw new HttpError(400, 'Nhà cung cấp đã hủy hoặc từ chối yêu cầu.');
        if (!is_string($query['code'] ?? null) || $query['code'] === '') throw new HttpError(400, 'Callback thiếu code.');
        // Không coi state hợp lệ là bằng chứng user đã xác thực. Chưa có session user.
        return ['success' => true, 'prepared' => true, 'authenticated' => false, 'provider' => $name,
            'message' => 'Callback đã tới đúng phiên. Chưa bật bước đổi token và đăng nhập OAuth.'];
    }

    /** Trang công cụ chỉ dành cho localhost trực tiếp hoặc ADMIN đã xác thực. */
    public static function requireSetupAccess(): void
    {
        $host = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? '')[0]);
        if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
            && in_array($host, ['localhost', '127.0.0.1'], true) && !isset($_SERVER['HTTP_X_FORWARDED_FOR'])) return;
        Auth::requireRole(['ADMIN']);
    }
}

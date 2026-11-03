<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\{HttpClientInterface, OAuthRepositoryInterface};
use App\Core\{Session, HttpError, OAuthPreparation};

/** Authorization code + PKCE; no access/refresh tokens are stored or sent to JS. */
final class OAuthService
{
    public function __construct(private array $config, private HttpClientInterface $http, private OAuthRepositoryInterface $users) {}

    public function begin(string $provider, string $returnUrl = '', ?array $linkActor = null): string
    {
        $ready = (new OAuthPreparation($this->config))->status($provider);
        if (!$ready['ready']) throw new HttpError(503, 'Chưa cấu hình đăng nhập ' . $provider . '.');
        Session::start();
        $pending = array_filter($_SESSION['oauth_login_pending'] ?? [], static fn($p) => $p['expires'] > time());
        if (count($pending) >= 5) unset($pending[array_key_first($pending)]);
        $state = bin2hex(random_bytes(32));
        $verifier = self::base64url(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));
        $pending[$state] = [
            'provider' => $provider,
            'expires' => time() + 600,
            'verifier' => $verifier,
            'nonce' => $nonce,
            'return' => $returnUrl,
            'link_actor' => $linkActor ? ['id' => (int)$linkActor['id'], 'auth_version' => (int)$linkActor['auth_version']] : null
        ];
        $_SESSION['oauth_login_pending'] = $pending;
        $p = $this->config['providers'][$provider];
        $query = [
            'client_id' => $p['client_id'],
            'redirect_uri' => $p['callback'],
            'response_type' => 'code',
            'scope' => $p['scope'],
            'state' => $state,
            'code_challenge' => self::base64url(hash('sha256', $verifier, true)),
            'code_challenge_method' => 'S256'
        ];
        if ($provider === 'google') {
            $query['nonce'] = $nonce;
            $query['prompt'] = 'select_account';
        } else $query['prompt'] = 'select_account';
        if ($provider === 'facebook') unset($query['code_challenge'], $query['code_challenge_method'], $query['prompt']);
        return $p['authorize_url'] . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function complete(string $provider, array $query, ?array $currentActor = null): array
    {
        if (!in_array($provider, ['google', 'github', 'facebook'], true)) throw new HttpError(404, 'Nhà cung cấp không hợp lệ.');
        Session::start();
        $state = $query['state'] ?? null;
        if (!is_string($state) || !preg_match('/^[a-f0-9]{64}$/D', $state)) throw new HttpError(403, 'Phiên OAuth không hợp lệ.');
        $pending = $_SESSION['oauth_login_pending'][$state] ?? null;
        unset($_SESSION['oauth_login_pending'][$state]); // Consume before any network request, including failures.
        if (!$pending || $pending['provider'] !== $provider || $pending['expires'] <= time()) throw new HttpError(403, 'Phiên OAuth đã hết hạn hoặc đã sử dụng.');
        $actor = $pending['link_actor'];
        if ($actor && (!$currentActor || $actor['id'] !== (int)$currentActor['id'] || $actor['auth_version'] !== (int)$currentActor['auth_version'])) throw new HttpError(401, 'Hãy đăng nhập lại trước khi liên kết.');
        if (isset($query['error'])) throw new HttpError(400, 'Bạn đã hủy hoặc nhà cung cấp từ chối đăng nhập.');
        $code = $query['code'] ?? null;
        if (!is_string($code) || $code === '' || strlen($code) > 4096 || preg_match('/[\x00-\x20]/', $code)) throw new HttpError(400, 'Mã OAuth không hợp lệ.');
        $p = $this->config['providers'][$provider];
        if ($provider === 'facebook') {
            [$identity, $ttl] = $this->facebookIdentity($p, $code);
            $user = $this->users->resolve($identity, $actor);
            $user['session_ttl'] = min((int)config('auth.session_lifetime', 2592000), $ttl);
            return ['user' => $user, 'linked' => $actor !== null, 'return' => $pending['return']];
        }
        $token = $this->http->json(
            'POST',
            $provider === 'github' ? 'https://github.com/login/oauth/access_token' : 'https://oauth2.googleapis.com/token',
            ['Content-Type: application/x-www-form-urlencoded'],
            http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $p['client_id'],
                'client_secret' => $p['client_secret'],
                'redirect_uri' => $p['callback'],
                'code_verifier' => $pending['verifier']
            ], '', '&', PHP_QUERY_RFC3986)
        );
        $ttl = (int)config('auth.session_lifetime', 2592000);
        if ($provider === 'google') {
            if (!is_string($token['id_token'] ?? null)) throw new HttpError(502, 'Google không trả thông tin xác thực hợp lệ.');
            $claims = $this->verifyGoogle($token['id_token'], $p['client_id'], $pending['nonce']);
            $identity = ['provider' => 'google', 'subject' => $claims['sub'], 'email' => $claims['email'], 'name' => $claims['name'] ?? $claims['email']];
            // Like WEB_TCC's Firebase exchange, do not outlive the verified token.
            $ttl = min($ttl, $claims['exp'] - time());
        } else {
            $access = $token['access_token'] ?? null;
            if (!is_string($access) || !preg_match('/^[A-Za-z0-9_\-.]+$/D', $access) || strcasecmp((string)($token['token_type'] ?? ''), 'bearer') !== 0) throw new HttpError(502, 'GitHub không trả thông tin xác thực hợp lệ.');
            $headers = ['Authorization: Bearer ' . $access, 'X-GitHub-Api-Version: 2022-11-28'];
            $profile = $this->http->json('GET', 'https://api.github.com/user', $headers);
            $emails = $this->http->json('GET', 'https://api.github.com/user/emails', $headers);
            if (!array_is_list($emails)) throw new HttpError(502, 'GitHub không trả danh sách email hợp lệ.');
            $email = null;
            foreach ($emails as $row) {
                if (
                    !is_array($row) || ($row['verified'] ?? null) !== true || !is_string($row['email'] ?? null)
                    || strlen($row['email']) > 191 || !filter_var($row['email'], FILTER_VALIDATE_EMAIL)
                ) continue;
                if ($email === null || ($row['primary'] ?? false) === true) $email = $row['email'] ?? null;
                if (($row['primary'] ?? false) === true) break;
            }
            $subject = is_int($profile['id'] ?? null) || is_string($profile['id'] ?? null) ? (string)$profile['id'] : '';
            if (!ctype_digit($subject) || $subject === '0') throw new HttpError(502, 'GitHub không trả danh tính hợp lệ.');
            $identity = ['provider' => 'github', 'subject' => $subject, 'email' => $email, 'name' => $profile['name'] ?? $profile['login'] ?? $email];
            if (isset($token['expires_in'])) $ttl = min($ttl, max(1, (int)$token['expires_in']));
        }
        if (!is_string($identity['email']) || strlen($identity['email']) > 191 || !filter_var($identity['email'], FILTER_VALIDATE_EMAIL)) throw new HttpError(422, 'Cần email đã xác minh tại nhà cung cấp để tiếp tục.');
        if (!is_string($identity['name']) || trim($identity['name']) === '' || !mb_check_encoding($identity['name'], 'UTF-8')) $identity['name'] = $identity['email'];
        $identity['email'] = mb_strtolower($identity['email']);
        $user = $this->users->resolve($identity, $actor);
        $user['session_ttl'] = max(1, $ttl);
        return ['user' => $user, 'linked' => $actor !== null, 'return' => $pending['return']];
    }

    public function verifyGoogle(string $jwt, string $clientId, string $nonce): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3 || strlen($jwt) > 20000) throw new HttpError(403, 'Google ID token không hợp lệ.');
        $header = json_decode(self::decode($parts[0]), true);
        $claims = json_decode(self::decode($parts[1]), true);
        if (!is_array($header) || !is_array($claims) || ($header['alg'] ?? '') !== 'RS256' || !is_string($header['kid'] ?? null)) throw new HttpError(403, 'Google ID token không hợp lệ.');
        $certs = $this->http->json('GET', 'https://www.googleapis.com/oauth2/v1/certs');
        $cert = $certs[$header['kid']] ?? null;
        if (!is_string($cert) || openssl_verify($parts[0] . '.' . $parts[1], self::decode($parts[2]), $cert, OPENSSL_ALGO_SHA256) !== 1) throw new HttpError(403, 'Chữ ký Google không hợp lệ.');
        if (($claims['aud'] ?? null) !== $clientId || !in_array($claims['iss'] ?? '', ['https://accounts.google.com', 'accounts.google.com'], true)
            || !is_int($claims['exp'] ?? null) || $claims['exp'] <= time() || !is_int($claims['iat'] ?? null) || $claims['iat'] > time() + 60
            || !is_string($claims['nonce'] ?? null) || !hash_equals($nonce, $claims['nonce']) || ($claims['email_verified'] ?? null) !== true
            || !is_string($claims['sub'] ?? null) || $claims['sub'] === '' || strlen($claims['sub']) > 255 || !is_string($claims['email'] ?? null)
        ) throw new HttpError(403, 'Thông tin Google chưa được xác minh hoặc đã hết hạn.');
        return $claims;
    }

    /** Facebook proves the app-scoped identity, not ownership of an email address.
     * First use requires explicit linking from an authenticated local account.
     */
    private function facebookIdentity(array $provider, string $code): array
    {
        $version = $provider['graph_version'] ?? '';
        if (!preg_match('/\Av[0-9]{1,3}\.0\z/D', $version)) throw new HttpError(503, 'Chưa cấu hình phiên bản Facebook API.');
        $base = 'https://graph.facebook.com/' . $version;
        $token = $this->http->json('POST', $base . '/oauth/access_token', ['Content-Type: application/x-www-form-urlencoded'], http_build_query([
            'client_id' => $provider['client_id'], 'client_secret' => $provider['client_secret'],
            'redirect_uri' => $provider['callback'], 'code' => $code,
        ], '', '&', PHP_QUERY_RFC3986));
        $access = $token['access_token'] ?? null;
        if (!is_string($access) || strlen($access) > 8192 || !preg_match('/\A[A-Za-z0-9_\.\-]+\z/D', $access)
            || strcasecmp((string)($token['token_type'] ?? ''), 'bearer') !== 0) throw new HttpError(502, 'Facebook không trả token hợp lệ.');
        // The app token stays in an HTTPS Authorization header. Never forward it to the browser.
        $debug = $this->http->json('GET', $base . '/debug_token?' . http_build_query(['input_token' => $access], '', '&', PHP_QUERY_RFC3986),
            ['Authorization: Bearer ' . $provider['client_id'] . '|' . $provider['client_secret']]);
        $data = $debug['data'] ?? null;
        $now = time();
        if (!is_array($data) || ($data['is_valid'] ?? null) !== true || ($data['type'] ?? '') !== 'USER'
            || !(is_string($data['app_id'] ?? null) || is_int($data['app_id'] ?? null)) || (string)$data['app_id'] !== $provider['client_id']
            || !is_string($data['user_id'] ?? null) || !ctype_digit($data['user_id']) || (int)$data['user_id'] <= 0
            || !is_int($data['expires_at'] ?? null) || $data['expires_at'] <= $now
            || (isset($data['data_access_expires_at']) && (!is_int($data['data_access_expires_at']) || ($data['data_access_expires_at'] !== 0 && $data['data_access_expires_at'] <= $now)))) {
            throw new HttpError(403, 'Danh tính Facebook không hợp lệ hoặc đã hết hạn.');
        }
        $profile = $this->http->json('GET', $base . '/me?' . http_build_query([
            'fields' => 'id,name', 'appsecret_proof' => hash_hmac('sha256', $access, $provider['client_secret']),
        ], '', '&', PHP_QUERY_RFC3986), ['Authorization: Bearer ' . $access]);
        if (($profile['id'] ?? null) !== $data['user_id']) throw new HttpError(403, 'Danh tính Facebook không khớp token.');
        $now = time(); // Profile lookup may have consumed part of the token lifetime.
        $ttl = $data['expires_at'] - $now;
        if (($data['data_access_expires_at'] ?? 0) > 0) $ttl = min($ttl, $data['data_access_expires_at'] - $now);
        if ($ttl <= 0) throw new HttpError(403, 'Phiên Facebook đã hết hạn. Vui lòng đăng nhập lại.');
        return [['provider' => 'facebook', 'subject' => $data['user_id'], 'email' => null, 'email_verified' => false,
            'name' => is_string($profile['name'] ?? null) ? mb_substr($profile['name'], 0, 120) : 'Facebook'], $ttl];
    }

    private static function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
    private static function decode(string $value): string
    {
        if (!preg_match('/^[A-Za-z0-9_-]+$/D', $value)) throw new HttpError(403, 'Token không hợp lệ.');
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) throw new HttpError(403, 'Token không hợp lệ.');
        return $decoded;
    }
}

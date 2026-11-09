<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\{HttpClientInterface, OAuthRepositoryInterface};
use App\Core\{Session, HttpError, OAuthPreparation, OAuthAccountLinkRequired};

/** Authorization code + PKCE; no access/refresh tokens are stored or sent to JS. */
final class OAuthService
{
    public function __construct(private array $config, private HttpClientInterface $http, private OAuthRepositoryInterface $users) {}

    // Sinh nonce gắn phiên để credential One Tap không dùng lại ở request khác.
    public function beginOneTap(string $returnUrl = ''): array
    {
        if (!(new OAuthPreparation($this->config))->oneTapReady()) throw new HttpError(503, 'Chưa cấu hình Google One Tap.');
        if (strlen($returnUrl) > 2000) throw new HttpError(422, 'Đường dẫn quay lại không hợp lệ.');
        Session::start();
        $pending = array_filter($_SESSION['google_one_tap_pending'] ?? [], static fn($p) => $p['expires'] > time());
        if (count($pending) >= 5) unset($pending[array_key_first($pending)]);
        $state = bin2hex(random_bytes(32));
        $nonce = bin2hex(random_bytes(32));
        $expires = time() + 600;
        $pending[$state] = ['nonce' => $nonce, 'expires' => $expires, 'return' => $returnUrl];
        $_SESSION['google_one_tap_pending'] = $pending;
        return ['state' => $state, 'nonce' => $nonce, 'expires' => $expires,
            'client_id' => $this->config['providers']['google']['client_id']];
    }

    // Kiểm tra credential Google cùng nonce của phiên trước khi tạo tài khoản hoặc đăng nhập.
    public function completeOneTap(array $input): array
    {
        if (!(new OAuthPreparation($this->config))->oneTapReady()) throw new HttpError(503, 'Chưa cấu hình Google One Tap.');
        Session::start();
        $state = $input['state'] ?? null;
        if (!is_string($state) || !preg_match('/\A[a-f0-9]{64}\z/D', $state)) throw new HttpError(403, 'Phiên One Tap không hợp lệ.');
        $pending = $_SESSION['google_one_tap_pending'][$state] ?? null;
        unset($_SESSION['google_one_tap_pending'][$state]);
        if (!$pending || $pending['expires'] <= time()) throw new HttpError(403, 'Phiên One Tap đã hết hạn hoặc đã sử dụng.');
        $return = $input['return_url'] ?? $pending['return'];
        if (!is_string($return) || strlen($return) > 2000) throw new HttpError(422, 'Đường dẫn quay lại không hợp lệ.');
        $credential = $input['credential'] ?? null;
        if (!is_string($credential) || $credential === '') throw new HttpError(422, 'Thiếu thông tin xác thực Google.');
        $claims = $this->verifyGoogle($credential, $this->config['providers']['google']['client_id'], $pending['nonce']);
        if (strlen($claims['email']) > 191 || !filter_var($claims['email'], FILTER_VALIDATE_EMAIL)) throw new HttpError(422, 'Email Google không hợp lệ.');
        $name = $claims['name'] ?? $claims['email'];
        if (!is_string($name) || trim($name) === '' || !mb_check_encoding($name, 'UTF-8')) $name = $claims['email'];
        return $this->resolveIdentity(['provider' => 'google', 'subject' => $claims['sub'],
            'email' => mb_strtolower($claims['email']), 'email_verified' => self::googleOwnsEmail($claims), 'name' => $name],
            null, $return, max(1, min((int)config('auth.session_lifetime', 2592000), $claims['exp'] - time())));
    }

    // Tạo ý định đăng nhập/liên kết trong session trước khi chuyển sang provider.
    public function begin(string $provider, string $returnUrl = '', ?array $linkActor = null): string
    {
        return $this->beginIntent($provider, $returnUrl, $linkActor);
    }

    /** A second OAuth intent owns the already-proven Facebook identity; no bearer token is retained. */
    private function beginIntent(string $provider, string $returnUrl, ?array $linkActor, ?array $facebook = null): string
    {
        if (strlen($returnUrl) > 2000) throw new HttpError(422, 'Đường dẫn quay lại không hợp lệ.');
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
            'expires' => $facebook !== null ? min(time() + 600, $facebook['expires']) : time() + 600,
            'verifier' => $verifier,
            'nonce' => $nonce,
            'return' => $returnUrl,
            'link_actor' => $linkActor ? ['id' => (int)$linkActor['id'], 'auth_version' => (int)$linkActor['auth_version']] : null,
            'confirmed_facebook' => $facebook,
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

    // Tiêu thụ state một lần, xác minh danh tính rồi mới resolve tài khoản hoặc tiếp tục liên kết.
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
        $facebook = $pending['confirmed_facebook'] ?? null;
        if ($facebook !== null && ($provider !== 'google' || $currentActor !== null || $actor !== null)) throw new HttpError(401, 'Phiên xác nhận Google đã thay đổi. Vui lòng đăng nhập lại.');
        if ($actor && (!$currentActor || $actor['id'] !== (int)$currentActor['id'] || $actor['auth_version'] !== (int)$currentActor['auth_version'])) throw new HttpError(401, 'Hãy đăng nhập lại trước khi liên kết.');
        if (isset($query['error'])) throw new HttpError(400, 'Bạn đã hủy hoặc nhà cung cấp từ chối đăng nhập.');
        $code = $query['code'] ?? null;
        if (!is_string($code) || $code === '' || strlen($code) > 4096 || preg_match('/[\x00-\x20]/', $code)) throw new HttpError(400, 'Mã OAuth không hợp lệ.');
        $p = $this->config['providers'][$provider];
        if ($provider === 'facebook') {
            [$identity, $ttl] = $this->facebookIdentity($p, $code);
            if ($actor === null && $currentActor !== null) throw new HttpError(409, 'Bạn đã đăng nhập. Hãy liên kết Facebook từ tài khoản hiện tại.');
            return $this->resolveIdentity($identity, $actor, $pending['return'], min((int)config('auth.session_lifetime', 2592000), $ttl));
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
            $identity = ['provider' => 'google', 'subject' => $claims['sub'], 'email' => $claims['email'], 'email_verified' => self::googleOwnsEmail($claims), 'name' => $claims['name'] ?? $claims['email']];
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
            $identity = ['provider' => 'github', 'subject' => $subject, 'email' => $email, 'email_verified' => $email !== null, 'name' => $profile['name'] ?? $profile['login'] ?? $email];
            if (isset($token['expires_in'])) $ttl = min($ttl, max(1, (int)$token['expires_in']));
        }
        if ($identity['email'] !== null && (!is_string($identity['email']) || strlen($identity['email']) > 191 || !filter_var($identity['email'], FILTER_VALIDATE_EMAIL))) throw new HttpError(422, 'Email nhà cung cấp không hợp lệ.');
        if (!is_string($identity['name']) || trim($identity['name']) === '' || !mb_check_encoding($identity['name'], 'UTF-8')) $identity['name'] = $identity['email'] ?? 'Người dùng';
        if ($identity['email'] !== null) $identity['email'] = mb_strtolower($identity['email']);
        if ($facebook !== null) {
            // Recheck after provider I/O. A completed Google exchange cannot
            // revive an expired Facebook proof or extend its lifetime.
            if ($facebook['expires'] <= time()) throw new HttpError(403, 'Phiên xác nhận Facebook đã hết hạn. Vui lòng thử lại.');
            try { $user = $this->users->resolveLinked($identity, $facebook['identity']); }
            catch (OAuthAccountLinkRequired $error) { throw new HttpError(422, 'Hãy chọn tài khoản Google đã liên kết với CouponHub hoặc có email Gmail/Workspace đã xác minh.'); }
            $user['session_ttl'] = max(1, min($ttl, $facebook['session_expires'] - time()));
            return ['user' => $user, 'linked' => false, 'facebook_linked' => true, 'return' => $pending['return']];
        }
        return $this->resolveIdentity($identity, $actor, $pending['return'], max(1, $ttl));
    }

    private static function googleOwnsEmail(array $claims): bool
    {
        // Google explicitly warns that email_verified alone is insufficient for
        // a non-Gmail/non-Workspace email whose ownership might have changed.
        return str_ends_with(mb_strtolower($claims['email']), '@gmail.com')
            || (is_string($claims['hd'] ?? null) && $claims['hd'] !== '');
    }

    private function resolveIdentity(array $identity, ?array $actor, string $return, int $ttl): array
    {
        try {
            $user = $this->users->resolve($identity, $actor);
        } catch (OAuthAccountLinkRequired $error) {
            if ($actor !== null || $identity['provider'] !== 'facebook') throw new HttpError(422, 'Cần email đã xác minh từ nhà cung cấp hoặc một phương thức đã liên kết để đăng nhập.');
            $proof = ['identity' => $identity, 'expires' => time() + min(600, $ttl), 'session_expires' => time() + $ttl];
            return ['redirect' => $this->beginIntent('google', $return, null, $proof), 'linked' => false, 'return' => $return];
        }
        $user['session_ttl'] = $ttl;
        return ['user' => $user, 'linked' => $actor !== null, 'return' => $return];
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

    /** Facebook proves the app-scoped identity; an email is a hint, not a verified claim. */
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
            'fields' => in_array('email', preg_split('/[ ,]+/', $provider['scope']), true) ? 'id,name,email' : 'id,name',
            'appsecret_proof' => hash_hmac('sha256', $access, $provider['client_secret']),
        ], '', '&', PHP_QUERY_RFC3986), ['Authorization: Bearer ' . $access]);
        if (($profile['id'] ?? null) !== $data['user_id']) throw new HttpError(403, 'Danh tính Facebook không khớp token.');
        $now = time(); // Profile lookup may have consumed part of the token lifetime.
        $ttl = $data['expires_at'] - $now;
        if (($data['data_access_expires_at'] ?? 0) > 0) $ttl = min($ttl, $data['data_access_expires_at'] - $now);
        if ($ttl <= 0) throw new HttpError(403, 'Phiên Facebook đã hết hạn. Vui lòng đăng nhập lại.');
        $email = is_string($profile['email'] ?? null) && strlen($profile['email']) <= 191 && filter_var($profile['email'], FILTER_VALIDATE_EMAIL)
            ? mb_strtolower($profile['email']) : null;
        return [['provider' => 'facebook', 'subject' => $data['user_id'], 'email' => $email, 'email_verified' => false,
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

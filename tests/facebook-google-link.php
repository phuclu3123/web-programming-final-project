<?php
declare(strict_types=1);
// OAuth must succeed even when every email transport is unavailable.
putenv('COUPONHUB_MAIL_DRIVER=disabled-for-oauth-test');
putenv('RESEND_API_KEY='); putenv('RESEND_FROM_EMAIL='); putenv('COUPONHUB_OTP_SECRET=');
require __DIR__ . '/../app/bootstrap.php';
use App\Core\{Session, HttpError, OAuthAccountLinkRequired};
use App\Services\OAuthService;
ob_start(); session_save_path(sys_get_temp_dir()); Session::start();
function bridgeCheck(bool $ok, string $label): void { if (!$ok) throw new RuntimeException('FAIL: ' . $label); echo 'PASS: ' . $label . "\n"; }
function bridgeReject(callable $action, string $label): void { try { $action(); } catch (HttpError $error) { bridgeCheck(true, $label); return; } throw new RuntimeException('FAIL: accepted ' . $label); }
final class BridgeHttp implements \App\Contracts\HttpClientInterface {
    public string $jwt = ''; public string $cert = ''; public array $calls = []; public array $metadata = []; public bool $offline = false; public bool $slowCerts = false;
    public function json(string $method, string $url, array $headers = [], ?string $body = null): array {
        $this->calls[] = [$method, $url];
        if ($this->offline) throw new HttpError(502, 'Provider offline');
        if (str_ends_with($url, '/oauth/access_token')) return ['access_token' => 'private-facebook-token', 'token_type' => 'bearer'];
        if (str_contains($url, '/debug_token?')) return ['data' => array_replace(['is_valid' => true, 'type' => 'USER', 'app_id' => '12345', 'user_id' => '991001', 'expires_at' => time() + 1800, 'data_access_expires_at' => time() + 900], $this->metadata)];
        if (str_contains($url, '/me?')) return ['id' => '991001', 'name' => 'Facebook Member', 'email' => 'untrusted@example.test'];
        if ($url === 'https://oauth2.googleapis.com/token') return ['id_token' => $this->jwt];
        if ($url === 'https://www.googleapis.com/oauth2/v1/certs') { if ($this->slowCerts) usleep(1100000); return ['bridge-key' => $this->cert]; }
        throw new LogicException('Unexpected provider endpoint');
    }
}
final class BridgeUsers implements \App\Contracts\OAuthRepositoryInterface {
    public bool $linked = false; public array $pairs = [];
    public function resolve(array $identity, ?array $linkActor = null): array {
        if ($identity['provider'] === 'facebook' && !$this->linked && $linkActor === null) throw new OAuthAccountLinkRequired();
        return ['id' => 42, 'email' => 'member@gmail.com', 'full_name' => 'Original profile', 'role' => 'USER', 'auth_version' => 0];
    }
    public function resolveLinked(array $google, array $facebook): array {
        $this->pairs[] = [$google, $facebook]; $this->linked = true;
        return $this->resolve($google);
    }
}
$cfg = ['base_url' => 'http://localhost:8000', 'providers' => [
    'facebook' => ['client_id' => '12345', 'client_secret' => 'private-facebook-secret', 'graph_version' => 'v24.0', 'scope' => 'public_profile', 'callback' => 'http://localhost:8000/api/oauth/facebook-callback.php', 'authorize_url' => 'https://www.facebook.com/v24.0/dialog/oauth'],
    'google' => ['client_id' => 'web-client.apps.googleusercontent.com', 'client_secret' => 'private-google-secret', 'scope' => 'openid email profile', 'callback' => 'http://localhost:8000/api/oauth/google-callback.php', 'authorize_url' => 'https://accounts.google.com/o/oauth2/v2/auth']
]];
$options = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
foreach (['C:/xampp/apache/conf/openssl.cnf', 'C:/xampp/php/extras/ssl/openssl.cnf'] as $path) if (is_file($path)) { $options['config'] = $path; break; }
$key = openssl_pkey_new($options); if (!$key) throw new RuntimeException('RSA fixture unavailable');
$http = new BridgeHttp(); $http->cert = openssl_pkey_get_details($key)['key'];
$users = new BridgeUsers(); $service = new OAuthService($cfg, $http, $users);
$params = static function(string $url): array { parse_str((string)parse_url($url, PHP_URL_QUERY), $query); return $query; };
$start = static function(string $return = '/saved-coupons') use ($service, $params, $users): array {
    $users->linked = false;
    $facebook = $params($service->begin('facebook', $return));
    $result = $service->complete('facebook', ['state' => $facebook['state'], 'code' => 'facebook-code']);
    bridgeCheck(isset($result['redirect']) && !isset($result['user']), 'unlinked Facebook starts Google confirmation without authenticating or creating an account');
    bridgeCheck(parse_url($result['redirect'], PHP_URL_HOST) === 'accounts.google.com', 'confirmation uses fixed Google OAuth redirect, no app popup or email page');
    return $params($result['redirect']);
};
$sign = static function(array $pending, array $changes = []) use ($key, $http): void {
    $claims = array_replace(['iss' => 'https://accounts.google.com', 'aud' => 'web-client.apps.googleusercontent.com', 'sub' => 'google-member', 'email' => 'member@gmail.com', 'email_verified' => true, 'name' => 'Google Member', 'nonce' => $pending['nonce'], 'iat' => time(), 'exp' => time() + 3600], $changes);
    $encode = static fn(string $s): string => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    $body = $encode(json_encode(['alg' => 'RS256', 'kid' => 'bridge-key'])) . '.' . $encode(json_encode($claims));
    openssl_sign($body, $signature, $key, OPENSSL_ALGO_SHA256); $http->jwt = $body . '.' . $encode($signature);
};
$google = $start(); $pending = $_SESSION['oauth_login_pending'][$google['state']];
bridgeCheck($google['prompt'] === 'select_account' && $google['code_challenge_method'] === 'S256' && isset($google['nonce']), 'Google account selection uses fresh state, nonce and PKCE');
bridgeCheck(($pending['confirmed_facebook']['identity']['subject'] ?? '') === '991001' && $pending['expires'] <= time() + 600, 'Facebook proof is server-bound to this Google intent with finite expiry');
bridgeCheck(!str_contains(json_encode($_SESSION), 'private-facebook-token') && !str_contains(json_encode($_SESSION), 'private-facebook-secret'), 'pending confirmation contains no provider bearer token or secret');
$sign($pending); $result = $service->complete('google', ['state' => $google['state'], 'code' => 'google-code', 'user_id' => 999, 'facebook_id' => 'attacker']);
bridgeCheck($result['facebook_linked'] && $result['user']['id'] === 42 && $result['return'] === '/saved-coupons' && $result['user']['session_ttl'] <= 900, 'signed Google confirmation logs into one account with original destination and bounded TTL');
bridgeCheck($users->pairs[0][0]['subject'] === 'google-member' && $users->pairs[0][1]['subject'] === '991001' && $users->pairs[0][1]['email_verified'] === false, 'browser cannot substitute identity or upgrade untrusted Facebook email');
bridgeReject(fn() => $service->complete('google', ['state' => $google['state'], 'code' => 'replay']), 'confirmation cannot be replayed');
$facebook = $params($service->begin('facebook'));
$result = $service->complete('facebook', ['state' => $facebook['state'], 'code' => 'facebook-code']);
bridgeCheck(isset($result['user']) && !isset($result['redirect']) && $result['user']['id'] === 42, 'subsequent Facebook login goes directly to same account');
foreach (['cancel', 'expired', 'expired-during-provider', 'nonce', 'switched-user', 'other-session', 'offline'] as $case) {
    $google = $start(); $pending = $_SESSION['oauth_login_pending'][$google['state']]; $sign($pending);
    if ($case === 'expired') $_SESSION['oauth_login_pending'][$google['state']]['expires'] = time() - 1;
    if ($case === 'expired-during-provider') $_SESSION['oauth_login_pending'][$google['state']]['confirmed_facebook']['expires'] = time() + 1;
    if ($case === 'nonce') $sign($pending, ['nonce' => 'wrong']);
    if ($case === 'other-session') $_SESSION['oauth_login_pending'] = [];
    $http->offline = $case === 'offline'; $http->slowCerts = $case === 'expired-during-provider'; $before = count($users->pairs);
    bridgeReject(fn() => $service->complete('google', ['state' => $google['state'], 'code' => 'google-code'] + ($case === 'cancel' ? ['error' => 'access_denied'] : []), $case === 'switched-user' ? ['id' => 77, 'auth_version' => 0] : null), 'reject Google confirmation: ' . $case);
    bridgeCheck(count($users->pairs) === $before && !isset($_SESSION['oauth_login_pending'][$google['state']]), 'failed confirmation consumes proof and creates no association');
    $http->offline = false; $http->slowCerts = false;
}
$cfg['providers']['google']['client_secret'] = '';
$unready = new OAuthService($cfg, $http, $users); $users->linked = false;
$facebook = $params($unready->begin('facebook'));
bridgeReject(fn() => $unready->complete('facebook', ['state' => $facebook['state'], 'code' => 'facebook-code']), 'missing Google configuration fails clearly without email fallback');
bridgeCheck(count(array_filter($http->calls, static fn(array $call): bool => str_contains($call[1], 'resend') || str_contains($call[1], '/emails'))) === 0, 'OAuth flow never invokes an email service');
Session::logout();
echo "PASS: Facebook-to-Google confirmation protocol with generated signed tokens; real browser acceptance remains manual.\n";

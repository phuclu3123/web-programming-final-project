<?php
declare(strict_types=1);

use App\Contracts\RoutineGateway;
use App\Core\Auth;
use App\Core\Session;
use App\Core\Request;
use App\Core\Database;
use App\Core\HttpError;
use App\Core\OAuthPreparation;
use App\Repositories\CatalogRepository;
use App\Services\AuthService;

require __DIR__ . '/../app/bootstrap.php';

// ---- KIỂM TRA SESSION/GUARD/OAUTH: database giả, không đọc/ghi MySQL thật ----
final class GuardGateway implements RoutineGateway
{
    public ?array $user = null;
    public ?array $merchant = null;
    public array $calls = [];
    public function call(string $routine, array $parameters = []): array
    {
        $this->calls[] = [$routine, $parameters];
        return match ($routine) {
            'ch_auth_user' => [$this->user ? [$this->user] : []],
            'ch_owned_merchant' => [$this->merchant ? [$this->merchant] : []],
            'ch_admin_dashboard_v2' => [[['merchants'=>2, 'pending'=>3, 'approved'=>4, 'rejected'=>1, 'total'=>8]], [], []],
            default => throw new RuntimeException('Unexpected routine'),
        };
    }
}
function check(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
}
function forbidden(callable $action, int $status): void
{
    try { $action(); } catch (HttpError $error) { check($error->status === $status, 'HTTP status'); return; }
    throw new RuntimeException('Expected HTTP error');
}
session_save_path(sys_get_temp_dir());
Session::start();
$db = new GuardGateway();
forbidden(fn () => Auth::requireRole(['ADMIN'], $db), 401);
check($db->calls === [], 'guest guard never asks DB for demo actor');


foreach (['USER', 'MERCHANT', 'ADMIN'] as $role) {
    $db->user = ['id'=>9, 'full_name'=>'Demo', 'role'=>$role, 'status'=>'ACTIVE', 'deleted_at'=>null];
    Session::establish($db->user);
    check(Auth::requireRole([$role], $db)['id'] === 9, 'matching role');
    foreach (array_diff(['USER','MERCHANT','ADMIN'], [$role]) as $wrongRole) {
        forbidden(fn () => Auth::requireRole([$wrongRole], $db), 403);
    }
}
$_SESSION['user_role'] = 'USER';
check(Auth::requireRole(['ADMIN'], $db)['role'] === 'ADMIN', 'DB role overrides session');
$db->user['role'] = 'USER';
forbidden(fn () => Auth::requireRole(['ADMIN'], $db), 403);
$db->user['status'] = 'LOCKED';
check(Auth::user($db) === null && !isset($_SESSION['user_id']), 'locked clears auth');
$db->user['status'] = 'ACTIVE';
Session::establish($db->user);
$db->user['deleted_at'] = '2026-01-01';
check(Auth::user($db) === null, 'deleted clears auth');
$db->user['deleted_at'] = null;
Session::establish($db->user);
$_SESSION['last_activity'] = time() - 1801;
check(Session::userId() === 0, 'idle timeout');
Session::establish($db->user);
$_SESSION['authenticated_at'] = time() - 28801;
check(Session::userId() === 0, 'absolute timeout');
Session::establish($db->user);
unset($_SESSION['auth_schema']);
check(Session::userId() === 0, 'pre-integration sessions must log in again');

foreach (['https://evil.example','//evil.example','index.php/evil','../admin.php','admin.php', "index.php\r\nX: evil", 'index.php\\evil'] as $url) {
    check(Auth::safeReturn($url, 'USER') === 'index.php', 'unsafe return');
}
check(Auth::safeReturn('admin.php?section=reports','ADMIN') === 'admin.php?section=reports', 'admin return');
check(Auth::safeReturn('', 'MERCHANT') === 'shop.php', 'merchant home');
check(Auth::safeReturn('', 'ADMIN') === 'admin.php?section=dashboard', 'admin home');

$db->user['role'] = 'MERCHANT';
Session::establish($db->user);
$db->merchant = ['id'=>11, 'owner_user_id'=>9];
Auth::requireMerchantOwner($db, $db->user, 11);
check($db->calls[array_key_last($db->calls)] === ['ch_owned_merchant',[11,9]], 'owner parameters');
$db->merchant['owner_user_id'] = 10;
forbidden(fn () => Auth::requireMerchantOwner($db, $db->user, 11), 403);
$db->merchant = null;
forbidden(fn () => Auth::requireMerchantOwner($db, $db->user, 999), 403);
forbidden(fn () => Session::checkCsrf('bad'), 403);
$token = Session::token();
Session::checkCsrf($token);
Session::logout();
check(session_status() !== PHP_SESSION_ACTIVE && $_SESSION === [], 'logout destroys session');
Session::start();
forbidden(fn () => Auth::requireRole(['MERCHANT'], $db), 401);

// Callback preparation phải có state đúng, single-use, không tạo session user.
$config = require __DIR__ . '/../config/oauth.php';
$oauth = new OAuthPreparation($config);
$probe = $oauth->begin('github', true);
parse_str(parse_url($probe['url'], PHP_URL_QUERY), $query);
$result = $oauth->consume('github', $query);
check($result['prepared'] && !$result['authenticated'] && !isset($_SESSION['user_id']), 'probe is not login');
forbidden(fn () => $oauth->consume('github', $query), 403);
$probe = $oauth->begin('github', true);
parse_str(parse_url($probe['url'], PHP_URL_QUERY), $query);
forbidden(fn () => $oauth->consume('google', $query), 403);
forbidden(fn () => $oauth->provider('facebook'), 404);
$probe = $oauth->begin('google', true);
check(str_starts_with($probe['url'], 'api/oauth/google-callback.php?'), 'Google probe callback');
parse_str(parse_url($probe['url'], PHP_URL_QUERY), $query);
$result = $oauth->consume('google', $query);
check($result['provider'] === 'google' && !$result['authenticated'] && !isset($_SESSION['user_id']), 'Google probe is not login');
forbidden(fn () => $oauth->consume('google', $query), 403);
$probe = $oauth->begin('google', true);
parse_str(parse_url($probe['url'], PHP_URL_QUERY), $query);
$_SESSION['oauth_pending'][$query['state']]['expires'] = time() - 1;
forbidden(fn () => $oauth->consume('google', $query), 403);
$probe = $oauth->begin('google', true);
parse_str(parse_url($probe['url'], PHP_URL_QUERY), $query);
$query['error'] = 'access_denied';
forbidden(fn () => $oauth->consume('google', $query), 400);

$config['providers']['github']['client_id'] = 'test-client';
$config['providers']['github']['client_secret'] = 'test-secret';
$oauth = new OAuthPreparation($config);
check(!str_contains(json_encode($oauth->status('github')), 'test-secret'), 'secret not exposed');
$start = $oauth->begin('github');
parse_str(parse_url($start['url'], PHP_URL_QUERY), $params);
$verifier = $_SESSION['oauth_pending'][$params['state']]['verifier'];
check($params['code_challenge_method'] === 'S256', 'PKCE S256');
check($params['code_challenge'] === rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'='), 'PKCE challenge');
// ---- GOOGLE: đúng endpoint/scope/callback, nonce/PKCE và secret giữ ở server ----
$googleConfig = $config;
$googleConfig['providers']['google']['client_id'] = '';
$googleConfig['providers']['google']['client_secret'] = '';
$google = new OAuthPreparation($googleConfig);
check($google->status('google')['missing'] === ['client_id', 'client_secret'], 'Google required credentials');
forbidden(fn () => $google->begin('google'), 503);
$googleConfig['providers']['google']['client_id'] = 'test-google.apps.googleusercontent.com';
$googleConfig['providers']['google']['client_secret'] = 'test-google-secret';
$google = new OAuthPreparation($googleConfig);
check($google->status('google')['ready'], 'Google configured');
check(!str_contains(json_encode($google->status('google')), 'test-google-secret'), 'Google secret not exposed');
$start = $google->begin('google');
check(str_starts_with($start['url'], 'https://accounts.google.com/o/oauth2/v2/auth?'), 'Google endpoint');
parse_str(parse_url($start['url'], PHP_URL_QUERY), $params);
check($params['scope'] === 'openid email profile' && $params['response_type'] === 'code', 'Google OIDC scopes/code');
check($params['redirect_uri'] === $googleConfig['providers']['google']['callback'], 'Google redirect exact');
$pending = $_SESSION['oauth_pending'][$params['state']];
check(strlen($params['nonce']) === 64 && $params['nonce'] === $pending['nonce'], 'Google nonce kept in session');
check($params['nonce'] !== $params['state'], 'Google nonce separate from state');
check($params['code_challenge_method'] === 'S256', 'Google PKCE S256');
check($params['code_challenge'] === rtrim(strtr(base64_encode(hash('sha256', $pending['verifier'], true)), '+/', '-_'), '='), 'Google PKCE challenge');
check(!isset($params['client_secret'], $params['code_verifier']), 'Google URL hides secret/verifier');
check(!str_contains($start['url'], 'test-google-secret') && !str_contains($start['url'], $pending['verifier']), 'Google sensitive values not in URL');
Session::logout();
// Các trang Admin thật được kiểm tra trong mysqli-integration.php và http-integration.py.
echo "PASS: roles, owner, locked/deleted, timeouts, redirects, CSRF/logout, OAuth state/expiry/replay/PKCE (no real DB).\n";

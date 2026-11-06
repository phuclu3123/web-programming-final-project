<?php

declare(strict_types=1);

// Read-only regression test: temp session files/log only, no DB or outbound HTTP.
require __DIR__ . '/../app/bootstrap.php';

use App\Core\App;
use App\Core\MerchantError;
use App\Core\Session;
use App\Core\UserFacingException;
use App\Services\MerchantService;

function assert10b(bool $condition, string $case): void
{
    if (!$condition) {
        throw new RuntimeException('FAIL Phase 10B: ' . $case);
    }
    echo 'PASS: ' . $case . "\n";
}

$original = [];
foreach (['COUPONHUB_SESSION_LIFETIME', 'COUPONHUB_SESSION_IDLE', 'COUPONHUB_SESSION_PATH'] as $key) {
    $original[$key] = getenv($key);
}
$logBefore = ini_get('error_log');
$logDir = sys_get_temp_dir() . '/couponhub-10b-' . bin2hex(random_bytes(6));
if (!mkdir($logDir, 0700, true) && !is_dir($logDir)) {
    throw new RuntimeException('Unable to create test session directory');
}
ob_start(); // Suppress output before session cookie operations in CLI.
try {
    // Confirm defaults and explicit legacy-compatibility overrides independently
    // from any process environment or project-local .env values.
    putenv('COUPONHUB_SESSION_LIFETIME');
    putenv('COUPONHUB_SESSION_IDLE');
    $config = require __DIR__ . '/../config/auth.php';
    assert10b($config['session_lifetime'] === 28800, 'default absolute session timeout = 8h');
    assert10b($config['session_idle'] === 1800, 'default idle timeout = 30m');

    putenv('COUPONHUB_SESSION_LIFETIME=7200');
    putenv('COUPONHUB_SESSION_IDLE=600');
    $custom = require __DIR__ . '/../config/auth.php';
    assert10b($custom['session_lifetime'] === 7200 && $custom['session_idle'] === 600, 'custom secure timeouts supported');

    putenv('COUPONHUB_SESSION_LIFETIME=2592000');
    putenv('COUPONHUB_SESSION_IDLE=2592000');
    $legacy = require __DIR__ . '/../config/auth.php';
    assert10b($legacy['session_lifetime'] === 2592000 && $legacy['session_idle'] === 2592000, 'explicit 30-day legacy policy remains configurable');

    putenv('COUPONHUB_SESSION_LIFETIME=3600');
    putenv('COUPONHUB_SESSION_IDLE=2592000');
    $bounded = require __DIR__ . '/../config/auth.php';
    assert10b($bounded['session_lifetime'] === 3600 && $bounded['session_idle'] === 3600, 'idle never exceeds absolute limit');

    App::init(['app' => ['timezone' => 'Asia/Ho_Chi_Minh', 'base_url' => 'http://localhost', 'debug' => true], 'auth' => $config]);
    putenv('COUPONHUB_SESSION_PATH=' . $logDir);
    Session::start();
    $actor = ['id' => 7, 'role' => 'USER', 'full_name' => 'Phase 10B', 'auth_version' => 1];
    Session::establish($actor);
    assert10b(Session::userId() === 7, 'new session is authenticated');
    assert10b($_SESSION['expires_at'] - $_SESSION['authenticated_at'] <= 28800, 'new sessions use short absolute TTL');

    $_SESSION['last_activity'] = time() - 1801;
    assert10b(Session::userId() === 0, 'idle timeout clears authentication');

    Session::establish($actor);
    // An existing 30-day session from before the security policy change must
    // not remain accepted after the new 8-hour absolute limit is deployed.
    $_SESSION['authenticated_at'] = time() - 28801;
    $_SESSION['last_activity'] = time();
    $_SESSION['expires_at'] = time() + 2592000;
    assert10b(Session::userId() === 0, 'old long-lived session expires at current policy boundary');

    Session::establish($actor + ['session_ttl' => 300]);
    assert10b($_SESSION['expires_at'] - $_SESSION['authenticated_at'] <= 300, 'provider TTL shorter than application TTL is respected');
    Session::clearAuthentication();

    App::init(['app' => ['timezone' => 'Asia/Ho_Chi_Minh', 'base_url' => 'http://localhost', 'debug' => true], 'auth' => $legacy]);
    foreach (['ADMIN', 'MERCHANT'] as $role) {
        Session::establish(array_replace($actor, ['role' => $role]));
        assert10b($_SESSION['expires_at'] - $_SESSION['authenticated_at'] <= 28800, $role . ' absolute cap survives long environment overrides');
        $_SESSION['last_activity'] = time() - 1801;
        assert10b(Session::userId() === 0, $role . ' idle cap survives long environment overrides');
        Session::establish(array_replace($actor, ['role' => $role]));
        $_SESSION['authenticated_at'] = time() - 28801;
        $_SESSION['last_activity'] = time();
        $_SESSION['expires_at'] = time() + 2592000;
        assert10b(Session::userId() === 0, $role . ' legacy session cannot bypass role absolute cap');
    }
    App::init(['app' => ['timezone' => 'Asia/Ho_Chi_Minh', 'base_url' => 'http://localhost', 'debug' => true], 'auth' => $config]);

    $service = (new ReflectionClass(MerchantService::class))->newInstanceWithoutConstructor();
    try {
        $service->requestMerchant(1, ['mode' => 'unexpected']);
        throw new RuntimeException('Missing validation exception');
    } catch (UserFacingException $error) {
        assert10b(MerchantError::message($error, 'generic', 'apply') === 'Hình thức đăng ký Merchant không hợp lệ.', 'safe business validation remains visible');
    }
    try {
        $service->updateProfile(1, 1, ['name' => '']);
        throw new RuntimeException('Missing validation exception');
    } catch (UserFacingException $error) {
        assert10b(MerchantError::message($error, 'generic', 'profile') === 'Tên Merchant là bắt buộc và tối đa 160 ký tự.', 'profile validation remains actionable');
    }

    ini_set('error_log', $logDir . '/errors.log');
    $secret = 'SQL SELECT secret_token FROM oauth_accounts WHERE id=1';
    assert10b(MerchantError::message(new RuntimeException($secret), 'Không thể lưu.', 'test') === 'Không thể lưu.', 'unexpected exception is masked even when debug=true');
    if (class_exists('mysqli_sql_exception')) {
        assert10b(MerchantError::message(new \mysqli_sql_exception($secret), 'Không thể tạo.', 'test') === 'Không thể tạo.', 'SQL exception cannot disclose query details');
    } else {
        assert10b(MerchantError::message(new \RuntimeException('mysqli_sql_exception: ' . $secret), 'Không thể tạo.', 'test') === 'Không thể tạo.', 'SQL-like exception is masked (mysqli extension unavailable in this CLI)');
    }
    $logged = file_get_contents($logDir . '/errors.log') ?: '';
    assert10b(!str_contains($logged, $secret) && str_contains($logged, 'merchant:test'), 'server log records error class without SQL or secrets');

    $controller = file_get_contents(__DIR__ . '/../app/controllers/MerchantController.php') ?: '';
    assert10b(!str_contains($controller, '$e->getMessage()') && substr_count($controller, 'MerchantError::message(') === 5, 'all five Merchant exception paths use safe messaging');
    Session::logout();
} finally {
    ini_set('error_log', (string) $logBefore);
    foreach ($original as $key => $value) {
        if ($value === false) putenv($key);
        else putenv($key . '=' . $value);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    foreach (glob($logDir . '/*') ?: [] as $file) @unlink($file);
    @rmdir($logDir);
    ob_end_flush();
}

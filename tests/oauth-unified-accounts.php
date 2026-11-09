<?php
declare(strict_types=1);
if (getenv('COUPONHUB_RUN_DB_TEST') !== '1') exit("SKIP: isolated MySQL required.\n");
require __DIR__ . '/../app/bootstrap.php';
use App\Core\{Database, Session, HttpError, OAuthAccountLinkRequired, Auth};
use App\Repositories\OAuthRepository;
use App\Repositories\UserRepository;
use App\Services\AuthService;
if (config('database.database') !== 'couponhub_merge_test' || (int)config('database.port') !== 13379 || is_file(__DIR__ . '/../config/local.php')) throw new RuntimeException('Unsafe test database');
$db = Database::connection();
if ((int)$db->query('SELECT @@port')->fetchColumn() !== 13379) throw new RuntimeException('Unsafe test port');
$repo = new OAuthRepository($db);
if (($argv[1] ?? '') === '--race-link-worker') {
    try {
        $user = $repo->resolveLinked(['provider' => 'google', 'subject' => $argv[2], 'email' => $argv[3], 'email_verified' => true, 'name' => 'Concurrent link'],
            ['provider' => 'facebook', 'subject' => $argv[4], 'email' => null, 'email_verified' => false, 'name' => 'Concurrent Facebook']);
        echo (int)$user['id']; exit;
    } catch (HttpError $error) { if ($error->status !== 409) throw $error; exit(3); }
}
if (($argv[1] ?? '') === '--race-worker') {
    $user = $repo->resolve(['provider' => $argv[2], 'subject' => $argv[3], 'email' => $argv[4], 'email_verified' => true, 'name' => 'Concurrent signup']);
    echo (int)$user['id']; exit;
}
ob_start(); session_save_path(sys_get_temp_dir()); Session::start();
function unifiedCheck(bool $ok, string $label): void { if (!$ok) throw new RuntimeException('FAIL: ' . $label); echo 'PASS: ' . $label . "\n"; }
function unifiedReject(callable $action, string $label, string $type = HttpError::class): void {
    try { $action(); } catch (Throwable $error) { if (!$error instanceof $type) throw $error; unifiedCheck(true, $label); return; }
    throw new RuntimeException('FAIL: accepted ' . $label);
}
$email = 'unified-' . bin2hex(random_bytes(6)) . '@example.test';
$password = password_hash('Unchanged!2026', PASSWORD_DEFAULT);
$db->prepare("INSERT INTO users(email,password_hash,full_name,role,status) VALUES(?,?,?,'USER','ACTIVE')")->execute([$email, $password, 'Original profile']);
$uid = (int)$db->lastInsertId();
$count = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$github = ['provider' => 'github', 'subject' => 'unified-github-' . $uid, 'email' => strtoupper($email), 'email_verified' => true, 'name' => 'Provider name', 'role' => 'ADMIN'];
unifiedCheck((int)$repo->resolve($github)['id'] === $uid, 'verified GitHub automatically finds existing password account');
$google = array_replace($github, ['provider' => 'google', 'subject' => 'unified-google-' . $uid]);
unifiedCheck((int)$repo->resolve($google)['id'] === $uid, 'verified Google resolves to same user ID');
unifiedCheck((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === $count, 'no duplicate users after provider login');
$original = $db->prepare('SELECT password_hash,full_name,role FROM users WHERE id=?'); $original->execute([$uid]); $row = $original->fetch();
unifiedCheck($row['password_hash'] === $password && $row['full_name'] === 'Original profile' && $row['role'] === 'USER', 'login preserves password, profile and server role');
$facebook = ['provider' => 'facebook', 'subject' => 'unified-facebook-' . $uid, 'email' => null, 'email_verified' => false, 'name' => 'Facebook Member'];
unifiedReject(fn() => $repo->resolve($facebook), 'unknown Facebook cannot claim or duplicate an account by unverified email', OAuthAccountLinkRequired::class);
unifiedCheck(!$db->inTransaction(), 'unlinked identity branch rolls transaction back');
$resolved = $repo->resolveLinked($google, $facebook);
unifiedCheck((int)$resolved['id'] === $uid, 'Google proof atomically attaches Facebook to original account');
unifiedCheck((int)$repo->resolve($facebook)['id'] === $uid && count($repo->linkedProviders($uid)) === 3, 'future Facebook login goes directly to same Google/GitHub user ID');
unifiedCheck((int)$repo->resolveLinked($google, $facebook)['id'] === $uid, 'repeated association is idempotent');
$login = (new AuthService(new UserRepository($db)))->login($email, 'Unchanged!2026');
unifiedCheck($login['success'] && (int)Auth::requireAuth()['id'] === $uid, 'password login uses same user ID as Google, GitHub and Facebook');
unifiedReject(fn() => $repo->resolve(array_replace($github, ['subject' => 'different-github'])), 'same provider with another subject cannot overwrite identity');
$another = $repo->resolve(array_replace($github, ['subject' => 'another-user', 'email' => 'another-' . $email]));
unifiedReject(fn() => $repo->resolve(array_replace($github, ['email' => $another['email']])), 'identity and email belonging to different users are rejected');
$db->prepare("UPDATE users SET status='LOCKED' WHERE id=?")->execute([$uid]);
unifiedReject(fn() => $repo->resolve($facebook), 'locked linked account cannot log in');
unifiedReject(fn() => $repo->resolve(array_replace($google, ['subject' => 'new-locked-google'])), 'verified matching email cannot bypass account lock');
$db->prepare("UPDATE users SET status='ACTIVE' WHERE id=?")->execute([$uid]);
// A new Google + Facebook pair creates exactly one real user in this test DB.
$newFacebook = array_replace($facebook, ['subject' => 'new-facebook-' . $uid, 'email' => 'untrusted-' . $email]);
$newGoogle = array_replace($google, ['subject' => 'new-google-' . $uid, 'email' => 'new-' . $email]);
$before = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$newUser = $repo->resolveLinked($newGoogle, $newFacebook);
unifiedCheck($newUser['email'] === $newGoogle['email'] && $newUser['role'] === 'USER'
    && (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn() === $before + 1, 'new Google/Facebook pair creates one USER with Google email, no invented email');
unifiedCheck((int)$repo->resolve($newFacebook)['id'] === (int)$newUser['id'], 'new Facebook identity persists in MySQL');
$freshGoogle = array_replace($google, ['subject' => 'rollback-google-' . $uid, 'email' => 'rollback-' . $email]);
$counts = $db->query('SELECT (SELECT COUNT(*) FROM users) u,(SELECT COUNT(*) FROM oauth_accounts) o,(SELECT COUNT(*) FROM activity_logs) a')->fetch();
unifiedReject(fn() => $repo->resolveLinked($freshGoogle, $facebook), 'Facebook already owned elsewhere cannot be reassigned during Google confirmation');
unifiedCheck($db->query('SELECT (SELECT COUNT(*) FROM users) u,(SELECT COUNT(*) FROM oauth_accounts) o,(SELECT COUNT(*) FROM activity_logs) a')->fetch() === $counts,
    'conflict rolls back new Google user, identity and audit; no orphan accounts');
unifiedReject(fn() => $repo->resolveLinked($google, $newFacebook), 'existing Google account cannot replace its Facebook identity');
unifiedReject(fn() => $repo->resolveLinked($github, $newFacebook), 'confirmation requires Google as the primary proof');
$_SESSION['google_one_tap_pending'] = ['old' => ['expires' => time() + 600]];
Session::establish($newUser);
unifiedCheck(!isset($_SESSION['oauth_login_pending'], $_SESSION['google_one_tap_pending']), 'new authenticated session clears pending provider proofs');
Session::logout();
echo "PASS: four login methods share one user in isolated MySQL; OAuth uses no email service.\n";

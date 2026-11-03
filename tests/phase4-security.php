<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
use App\Core\{App, AuthRateLimit, HttpError, RequestSecurity, Session};
ob_start();
function securityCheck(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException('FAIL: '.$label);
    echo 'PASS: '.$label."\n";
}
function securityReject(callable $action, int $status, string $label): void {
    try { $action(); } catch (HttpError $error) { securityCheck($error->status === $status, $label); return; }
    throw new RuntimeException('FAIL: accepted '.$label);
}
$config=['app'=>['base_url'=>'https://couponhub.example/app'], 'auth'=>['session_idle'=>2592000,'session_lifetime'=>2592000]];
App::init($config);
$_SERVER=['HTTP_HOST'=>'internal:8080','HTTP_ORIGIN'=>'https://couponhub.example','REMOTE_ADDR'=>'192.0.2.7'];
RequestSecurity::checkWrite();
securityCheck(true, 'canonical HTTPS origin works behind internal HTTP server');
foreach (['https://evil.test','null','https://couponhub.example.evil.test','http://couponhub.example','https://couponhub.example:444','https://couponhub.example@evil.test','https://couponhub.example/x'] as $origin) {
    $_SERVER['HTTP_ORIGIN']=$origin;
    securityReject(fn()=>RequestSecurity::checkWrite(),403,'reject foreign/malformed origin '.$origin);
}
$_SERVER['HTTP_ORIGIN']='https://couponhub.example:443'; RequestSecurity::checkWrite();
$_SERVER['HTTP_SEC_FETCH_SITE']='cross-site';
securityReject(fn()=>RequestSecurity::checkWrite(),403,'cross-site metadata cannot bypass CSRF policy');
unset($_SERVER['HTTP_SEC_FETCH_SITE'],$_SERVER['HTTP_ORIGIN']);
$_SERVER['CONTENT_LENGTH']=1048577;
securityReject(fn()=>RequestSecurity::checkWrite(),413,'oversize body rejected');
unset($_SERVER['CONTENT_LENGTH']);
session_save_path(sys_get_temp_dir()); Session::start();
$cookie=session_get_cookie_params();
securityCheck($cookie['secure'] && $cookie['httponly'] && $cookie['samesite']==='Lax' && ini_get('session.use_strict_mode')==='1','cookie security and strict server session settings');
$old=session_id(); $oldCsrf=Session::token();
Session::establish(['id'=>99,'role'=>'ADMIN','full_name'=>'Admin','auth_version'=>0]);
securityCheck(session_id()!==$old && Session::token()!==$oldCsrf,'login rotates session ID and CSRF');
securityCheck($_SESSION['expires_at']-time()<=28800,'Admin absolute lifetime capped at 8 hours');
$_SESSION['last_activity']=time()-1801;
securityCheck(Session::userId()===0,'Admin idle timeout remains 30 minutes even with 30-day User configuration');
Session::establish(['id'=>99,'role'=>'MERCHANT','full_name'=>'Shop','auth_version'=>0]);
securityCheck($_SESSION['expires_at']-time()<=28800,'Merchant lifetime capped');
Session::establish(['id'=>99,'role'=>'USER','full_name'=>'User','auth_version'=>0]);
securityCheck($_SESSION['expires_at']-time()>28800,'regular User lifetime preserves configuration');
$directory=sys_get_temp_dir().'/ch-phase4-'.bin2hex(random_bytes(6)); mkdir($directory,0700);
$config['auth']['rate_limit_dir']=$directory; App::init($config);
try {
    AuthRateLimit::hit('fixture','account',2,60);
    Session::logout(); Session::start();
    AuthRateLimit::hit('fixture','account',2,60);
    securityReject(fn()=>AuthRateLimit::hit('fixture','account',2,60),429,'new browser session does not reset file-backed rate limit');
    $path=$directory.'/auth-rate-'.hash('sha256','fixture:account').'.json';
    file_put_contents($path,json_encode(['until'=>time()-1,'count'=>2]));
    AuthRateLimit::hit('fixture','account',2,60);
    securityCheck(json_decode(file_get_contents($path),true)['count']===1,'expired rate window resets');
    $blocker=$directory.'/not-a-directory'; file_put_contents($blocker,'file');
    $config['auth']['rate_limit_dir']=$blocker; App::init($config);
    securityReject(fn()=>@AuthRateLimit::hit('fixture','x',2,60),503,'unwritable limiter fails closed');
} finally {
    foreach (glob($directory.'/*') as $file) unlink($file);
    rmdir($directory); Session::logout();
}
echo "PASS: phase 4 request/session/rate protections, no DB or provider calls.\n";

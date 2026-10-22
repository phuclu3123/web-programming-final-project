<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
ob_start();
function envCheck(bool $ok,string $label): void {if (!$ok) throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label."\n";}
$file=tempnam(sys_get_temp_dir(),'ch-env-');
try {
    putenv('COUPONHUB_ENV_PRIORITY=process');
    file_put_contents($file,"COUPONHUB_ENV_PRIORITY=file\nCOUPONHUB_ENV_EMPTY=\nCOUPONHUB_ENV_QUOTED=\"CouponHub <test@example.test>\"\nCOUPONHUB_ENV_HASH=\"literal#value\"\nCOUPONHUB_ENV_COMMENT=value # comment\nUNRELATED_ENV_TEST=ignored\n");
    \App\Core\Env::load($file);
    envCheck(getenv('COUPONHUB_ENV_PRIORITY')==='process','process variables override dotenv');
    envCheck(getenv('COUPONHUB_ENV_EMPTY')==='' && getenv('COUPONHUB_ENV_QUOTED')==='CouponHub <test@example.test>','empty and quoted dotenv values');
    envCheck(getenv('COUPONHUB_ENV_HASH')==='literal#value' && getenv('COUPONHUB_ENV_COMMENT')==='value','comments do not corrupt quoted values');
    envCheck(getenv('UNRELATED_ENV_TEST')===false,'dotenv cannot inject unrelated process settings');
    file_put_contents($file,'COUPONHUB_ENV_BROKEN="unclosed');
    try {\App\Core\Env::load($file);throw new LogicException('Invalid file accepted');}
    catch (RuntimeException $e) {envCheck(!str_contains($e->getMessage(),'unclosed'),'dotenv syntax error does not print secret values');}
} finally {unlink($file);}
$scope='unit-'.bin2hex(random_bytes(8));
\App\Core\AuthRateLimit::hit($scope,'test',2,60);
\App\Core\AuthRateLimit::hit($scope,'test',2,60);
try {\App\Core\AuthRateLimit::hit($scope,'test',2,60);throw new LogicException('Rate limit accepted');}
catch (\App\Core\HttpError $e) {envCheck($e->status===429,'rate limit persists independently of browser session');}
// No session is started by the environment loader or provider status inspection.
envCheck(session_status()!==PHP_SESSION_ACTIVE,'configuration does not create a login session');

// Inspect readiness without a .env file, network calls or database writes.
$fixture=getenv();
foreach (['GOOGLE','GITHUB'] as $provider) {
    $fixture['COUPONHUB_'.$provider.'_CLIENT_ID']='configuration-test-client';
    $fixture['COUPONHUB_'.$provider.'_CLIENT_SECRET']='configuration-test-secret';
}
$fixture['COUPONHUB_APP_URL']='http://localhost:8000';
$fixture['COUPONHUB_OTP_SECRET']=str_repeat('configuration-test-',3);
$fixture['COUPONHUB_MAIL_DRIVER']='resend';
$fixture['RESEND_API_KEY']='configuration-test-key';
$fixture['RESEND_FROM_EMAIL']='test@example.test';
$configProbe=static function(array $env): array {
    $process=proc_open([PHP_BINARY,__DIR__.'/../scripts/check_auth_config.php'],[1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env);
    if (!is_resource($process)) throw new RuntimeException('Cannot start configuration check');
    $output=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($process),$output];
};
[$exit,$output]=$configProbe($fixture);
envCheck($exit===0 && !str_contains($output,$fixture['RESEND_API_KEY']) && !str_contains($output,$fixture['COUPONHUB_GOOGLE_CLIENT_SECRET']),'process-only configuration is accepted without printing secrets');
$fixture['COUPONHUB_MAIL_DRIVER']='invalid-driver';
[$exit]=$configProbe($fixture);
envCheck($exit!==0,'invalid mail driver is not reported ready');

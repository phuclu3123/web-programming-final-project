<?php
declare(strict_types=1);
if (getenv('COUPONHUB_RUN_DB_TEST')!=='1') exit("SKIP: isolated database required.\n");
require __DIR__.'/../app/bootstrap.php';
if (config('database.database')!=='couponhub_merge_test' || (int)config('database.port')!==13379 || is_file(__DIR__.'/../config/local.php')) throw new RuntimeException('Unsafe database configuration');
ob_start(); session_save_path(sys_get_temp_dir());
use App\Core\{Database, Session, Auth, HttpError};
use App\Repositories\{UserRepository, PasswordResetRepository, OAuthRepository};
use App\Services\{OtpPasswordResetService, ResendMailService};
function completeCheck(bool $ok,string $label): void { if (!$ok) throw new RuntimeException('FAIL: '.$label); echo 'PASS: '.$label."\n"; }
function completeReject(callable $action,string $label): void { try {$action();} catch (HttpError $e) {completeCheck(true,$label);return;} throw new RuntimeException('FAIL: accepted '.$label); }
final class CapturedAuthMail implements \App\Contracts\AuthMailerInterface {
    public array $sent=[]; public bool $fail=false;
    public function sendPasswordReset(string $to,string $link): void {throw new RuntimeException('Not an OTP');}
    public function sendOtp(string $to,string $code): void {if ($this->fail) throw new RuntimeException('Delivery offline');$this->sent[]=[$to,$code];}
}
$db=Database::connection();
completeCheck((int)$db->query('SELECT @@port')->fetchColumn()===13379,'isolated MariaDB');
$users=new UserRepository($db); $tokens=new PasswordResetRepository($db); $mail=new CapturedAuthMail();
$service=new OtpPasswordResetService($users,$tokens,$mail,str_repeat('test-secret-',5));
$user=$users->findByLogin('user_demo'); $uid=(int)$user['id']; $email=$user['email'];
Session::start();
$service->request('unknown-auth@example.test','127.0.0.2');
completeCheck(count($mail->sent)===0 && isset($_SESSION['password_reset_flow']),'unknown email has same browser flow without delivery');
completeCheck(!$service->reset('123456','Complete!2026','Complete!2026'),'unknown email cannot reset an account');
$service->request($email,'127.0.0.2');
$flow=$_SESSION['password_reset_flow']; $code=end($mail->sent)[1];
completeCheck(preg_match('/^[0-9]{6}$/D',$code)===1 && !isset($flow['otp']) && !isset($flow['code']) && !in_array($code,$flow,true),'six-digit OTP is mailed, only keyed hash stored in session');
completeCheck(!$service->reset('000000','Complete!2026','Complete!2026') && $_SESSION['password_reset_flow']['attempts']===1,'wrong OTP consumes one attempt');
completeReject(fn()=>$service->reset($code,'weak','weak'),'weak password rejected before consumption');
$oldVersion=(int)$user['auth_version']; Auth::login($user);
function requestAgain(Database $db,OtpPasswordResetService $service,int $uid,string $email): void {
    $db->prepare('UPDATE password_reset_tokens SET created_at=UTC_TIMESTAMP()-INTERVAL 2 MINUTE WHERE user_id=?')->execute([$uid]);
    $service->request($email,'127.0.0.2');
}
requestAgain($db,$service,$uid,$email); $code=end($mail->sent)[1];
completeCheck($service->reset($code,'Complete!2026','Complete!2026'),'correct OTP atomically changes password');
completeCheck(password_verify('Complete!2026',$users->passwordHashForUser($uid)) && (int)$users->findById($uid)['auth_version']===$oldVersion+1,'password hash/version updated');
completeCheck(Auth::user()===null,'previous authenticated session is invalid');
completeCheck(!$service->reset($code,'Replay!2026','Replay!2026'),'successful OTP cannot be replayed');
requestAgain($db,$service,$uid,$email); $oldFlow=$_SESSION['password_reset_flow'];
for($i=0;$i<5;$i++) completeCheck(!$service->reset('000000','Complete!2026','Complete!2026'),'invalid OTP attempt rejected');
completeCheck(!isset($_SESSION['password_reset_flow']) && !$tokens->isUsable(hash('sha256',$oldFlow['token'])),'five wrong attempts revoke SQL token and clear session flow');
requestAgain($db,$service,$uid,$email); $code=end($mail->sent)[1];
$_SESSION['password_reset_flow']['expires']=time()-1;
completeCheck(!$service->reset($code,'Complete!2026','Complete!2026'),'expired OTP rejected');
requestAgain($db,$service,$uid,$email); $code=end($mail->sent)[1];
$db->prepare('UPDATE password_reset_tokens SET expires_at=UTC_TIMESTAMP()-INTERVAL 1 SECOND WHERE token_hash=?')->execute([hash('sha256',$_SESSION['password_reset_flow']['token'])]);
completeCheck(!$service->reset($code,'Unexpected!2026','Unexpected!2026'),'SQL expiry rejects correct OTP even when browser flow is unexpired');
foreach (['locked'=>"status='LOCKED'",'deleted'=>'deleted_at=UTC_TIMESTAMP()'] as $case=>$assignment) {
    requestAgain($db,$service,$uid,$email); $code=end($mail->sent)[1];
    $version=(int)$users->findById($uid)['auth_version'];
    $db->prepare('UPDATE users SET '.$assignment.' WHERE id=?')->execute([$uid]);
    completeCheck(!$service->reset($code,'Unexpected!2026','Unexpected!2026'),'correct OTP cannot reset a user '.$case.' after issuance');
    $db->prepare("UPDATE users SET status='ACTIVE',deleted_at=NULL WHERE id=?")->execute([$uid]);
    completeCheck((int)$users->findById($uid)['auth_version']===$version && password_verify('Complete!2026',$users->passwordHashForUser($uid)),'rejected '.$case.' reset leaves password and auth version unchanged');
}
requestAgain($db,$service,$uid,$email); $oldFlow=$_SESSION['password_reset_flow'];
requestAgain($db,$service,$uid,$email);
completeCheck(!$tokens->isUsable(hash('sha256',$oldFlow['token'])),'resend invalidates older recovery token');
$mail->fail=true; requestAgain($db,$service,$uid,$email);
completeCheck(!$tokens->isUsable(hash('sha256',$_SESSION['password_reset_flow']['token'])),'failed mail delivery revokes reset token');
$mail->fail=false;
$db->prepare("UPDATE users SET status='LOCKED' WHERE id=?")->execute([$uid]);
$before=count($mail->sent); requestAgain($db,$service,$uid,$email);
completeCheck(count($mail->sent)===$before,'locked account cannot receive a usable OTP');
$db->prepare("UPDATE users SET status='ACTIVE' WHERE id=?")->execute([$uid]);
$oauth=new OAuthRepository($db);
$identity=['provider'=>'github','subject'=>'987654321001','email'=>'oauth-complete@example.test','name'=>'OAuth Complete','role'=>'ADMIN'];
$created=$oauth->resolve($identity);
completeCheck($created['role']==='USER' && $created['id']>0,'OAuth creates only USER; role injection ignored');
completeCheck($oauth->resolve($identity)['id']===$created['id'],'provider identity login returns existing account');
$collision=$identity; $collision['subject']='987654321002'; $collision['email']=$email;
completeReject(fn()=>$oauth->resolve($collision),'matching email cannot silently attach a provider');
$actor=$users->findById($uid);
completeCheck($oauth->resolve($collision,$actor)['id']===$uid && in_array('github',$oauth->linkedProviders($uid),true),'explicit authenticated link attaches identity to intended account');
completeReject(fn()=>$oauth->resolve($collision,$created),'identity already linked elsewhere is rejected');
$other=$collision; $other['subject']='987654321003';
completeReject(fn()=>$oauth->resolve($other,$actor),'second identity for same provider cannot replace first');
$stale=$actor; $stale['auth_version']--;
completeReject(fn()=>$oauth->resolve($collision,$stale),'stale actor version cannot link identity');
$db->prepare("UPDATE users SET status='LOCKED' WHERE id=?")->execute([(int)$created['id']]);
completeReject(fn()=>$oauth->resolve($identity),'linked locked user cannot login');
foreach (['merchant_shops'=>'MERCHANT','admin_demo'=>'ADMIN'] as $login=>$role) {
    $owner=$users->findByLogin($login);
    $external=['provider'=>'google','subject'=>'review-'.$login,'email'=>$owner['email'],'name'=>'External role is ignored','role'=>'USER'];
    $linked=$oauth->resolve($external,$owner);
    Auth::login($oauth->resolve($external));
    $fresh=Auth::requireAuth();
    completeCheck($fresh['role']===$role && Auth::safeReturn('', $fresh['role'])===($role==='ADMIN'?'/admin':'/merchant'),'OAuth preserves server-side role and center for '.$role);
}
final class CaptureResendHttp implements \App\Contracts\HttpClientInterface {
    public array $request=[];
    public function json(string $method,string $url,array $headers=[],?string $body=null): array {$this->request=[$method,$url,$headers,json_decode($body,true)];return ['id'=>'test-mail-id'];}
}
$transport=new CaptureResendHttp();
(new ResendMailService($transport,['resend_api_key'=>'test-key','resend_from'=>'CouponHub <sender@example.test>']))->sendOtp('to@example.test','123456');
completeCheck($transport->request[1]==='https://api.resend.com/emails' && $transport->request[3]['to']===['to@example.test'] && str_contains($transport->request[3]['text'],'123456'),'Resend request uses fixed HTTPS API, recipient and OTP payload');
echo "PASS: complete auth database integration; captured mail only, no actual email sent.\n";

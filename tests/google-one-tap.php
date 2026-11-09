<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
ob_start();
session_save_path(sys_get_temp_dir());
use App\Core\{HttpError, OAuthPreparation, Session};
use App\Services\OAuthService;

function tapCheck(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException('FAIL: '.$label);
    echo 'PASS: '.$label."\n";
}
function tapReject(callable $action, int $status, string $label): void {
    try { $action(); } catch (HttpError $e) { tapCheck($e->status === $status, $label); return; }
    throw new RuntimeException('FAIL: accepted '.$label);
}
final class TapHttp implements \App\Contracts\HttpClientInterface {
    public string $key;
    public function json(string $method, string $url, array $headers=[], ?string $body=null): array {
        if ($method !== 'GET' || $url !== 'https://www.googleapis.com/oauth2/v1/certs') throw new RuntimeException('Unexpected provider request');
        return ['test-key' => $this->key];
    }
}
final class TapUsers implements \App\Contracts\OAuthRepositoryInterface {
    public array $calls=[];
    public function resolveLinked(array $google, array $facebook): array { throw new LogicException('Unexpected confirmation'); }
    public function resolve(array $identity, ?array $linkActor=null): array {
        $this->calls[]=[$identity, $linkActor];
        return ['id'=>7,'role'=>'USER','email'=>$identity['email'],'full_name'=>$identity['name'],'auth_version'=>0];
    }
}
$cfg=['base_url'=>'http://localhost:8000','google_one_tap'=>true,'providers'=>['google'=>[
    'client_id'=>'test-client.apps.googleusercontent.com','client_secret'=>'','callback'=>'http://localhost:8000/api/oauth/google-callback.php']]];
$http=new TapHttp();
$options=['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA];
foreach (['C:/xampp/apache/conf/openssl.cnf','C:/xampp/php/extras/ssl/openssl.cnf'] as $file) if (is_file($file)) { $options['config']=$file; break; }
$key=openssl_pkey_new($options);
if (!$key) throw new RuntimeException('RSA unavailable');
$http->key=openssl_pkey_get_details($key)['key'];
$users=new TapUsers(); $service=new OAuthService($cfg,$http,$users);
Session::start();
$sign=static function(array $claims) use ($key): string {
    $encode=static fn(string $s)=>rtrim(strtr(base64_encode($s),'+/','-_'),'=');
    $data=$encode(json_encode(['alg'=>'RS256','kid'=>'test-key'])).'.'.$encode(json_encode($claims));
    openssl_sign($data,$signature,$key,OPENSSL_ALGO_SHA256);
    return $data.'.'.$encode($signature);
};
$claims=static fn(array $intent)=>['iss'=>'https://accounts.google.com','aud'=>$cfg['providers']['google']['client_id'],
    'sub'=>'provider-subject','exp'=>time()+300,'iat'=>time(),'nonce'=>$intent['nonce'],
    'email'=>'Google@Example.test','email_verified'=>true,'name'=>'Google User'];
tapCheck((new OAuthPreparation($cfg))->oneTapReady() && !(new OAuthPreparation($cfg))->status('google')['ready'], 'One Tap needs client ID; authorization-code fallback separately needs secret');
$disabled=$cfg; $disabled['google_one_tap']=false;
tapCheck(!(new OAuthPreparation($disabled))->oneTapReady(),'feature flag disables One Tap');
foreach (['client_id'=>'', 'base_url'=>'http://untrusted.test'] as $field=>$bad) {
    $badConfig=$cfg;
    if ($field==='client_id') $badConfig['providers']['google'][$field]=$bad; else $badConfig[$field]=$bad;
    tapCheck(!(new OAuthPreparation($badConfig))->oneTapReady(),'reject missing client ID or insecure public origin');
}
$intent=$service->beginOneTap('/saved-coupons');
$result=$service->completeOneTap(['state'=>$intent['state'],'credential'=>$sign($claims($intent)),'return_url'=>'/coupons/2']);
tapCheck($result['return']==='/coupons/2' && $result['user']['session_ttl']<=300,'signed identity completes with updated form return and token-bounded session');
tapCheck($users->calls[0][0]['subject']==='provider-subject' && $users->calls[0][0]['email']==='google@example.test' && $users->calls[0][1]===null,'only verified Google subject reaches existing repository without implicit linking');
tapReject(fn()=>$service->completeOneTap(['state'=>$intent['state'],'credential'=>$sign($claims($intent))]),403,'replay rejected');
foreach (['nonce'=>'wrong','aud'=>'another-client','exp'=>time()-1,'email_verified'=>false,'iss'=>'https://evil.test'] as $field=>$bad) {
    $intent=$service->beginOneTap(); $before=count($users->calls);
    tapReject(fn()=>$service->completeOneTap(['state'=>$intent['state'],'credential'=>$sign(array_replace($claims($intent),[$field=>$bad]))]),403,'invalid '.$field.' rejected before repository');
    tapCheck(count($users->calls)===$before,'invalid credential has no account write');
}
$intent=$service->beginOneTap(); $jwt=$sign($claims($intent)); $jwt[10]=$jwt[10]==='a'?'b':'a';
tapReject(fn()=>$service->completeOneTap(['state'=>$intent['state'],'credential'=>$jwt]),403,'tampered token rejected');
$intent=$service->beginOneTap(); $_SESSION['google_one_tap_pending'][$intent['state']]['expires']=time()-1;
tapReject(fn()=>$service->completeOneTap(['state'=>$intent['state'],'credential'=>$sign($claims($intent))]),403,'expired session intent rejected');
$intent=$service->beginOneTap(); $saved=$_SESSION['google_one_tap_pending']; $_SESSION['google_one_tap_pending']=[];
tapReject(fn()=>$service->completeOneTap(['state'=>$intent['state'],'credential'=>$sign($claims($intent))]),403,'another session cannot consume intent');
$_SESSION['google_one_tap_pending']=$saved;
tapReject(fn()=>$service->completeOneTap(['state'=>$intent['state'],'credential'=>'']),422,'missing credential rejected');
tapReject(fn()=>$service->completeOneTap(['state'=>$intent['state'],'credential'=>$sign($claims($intent))]),403,'failed credential also consumes intent');
$_SESSION['google_one_tap_pending']=[];
for($i=0;$i<9;$i++) $service->beginOneTap();
tapCheck(count($_SESSION['google_one_tap_pending'])===5,'pending intents bounded across tabs');
echo "PASS One Tap protocol: generated RSA keys/injected cert transport; no real Google credentials or DB writes.\n";

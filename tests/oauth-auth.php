<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
ob_start(); session_save_path(sys_get_temp_dir());
use App\Core\{Session, HttpError};
use App\Services\OAuthService;

function oauthCheck(bool $value,string $label): void {
    if (!$value) throw new RuntimeException('FAIL: '.$label);
    echo 'PASS: '.$label."\n";
}
function oauthReject(callable $action,string $label): void {
    try {$action();} catch (HttpError $e) {oauthCheck(true,$label);return;}
    throw new RuntimeException('FAIL: accepted '.$label);
}
final class OAuthHttpFake implements \App\Contracts\HttpClientInterface {
    public array $calls=[]; public string $jwt=''; public string $cert=''; public bool $verified=true;
    public function json(string $method,string $url,array $headers=[],?string $body=null): array {
        $this->calls[]=[$method,$url,$headers,$body];
        if (str_contains($url,'access_token')) return ['access_token'=>'server_access','token_type'=>'bearer'];
        if (str_contains($url,'googleapis.com/token')) return ['id_token'=>$this->jwt];
        if (str_ends_with($url,'/certs')) return ['fixture-key'=>$this->cert];
        if (str_ends_with($url,'/user/emails')) return [['email'=>'unverified@example.test','primary'=>true,'verified'=>false],['email'=>'verified@example.test','primary'=>false,'verified'=>$this->verified]];
        return ['id'=>1234,'name'=>'Provider User','login'=>'provider-user'];
    }
}
final class OAuthUsersFake implements \App\Contracts\OAuthRepositoryInterface {
    public array $calls=[];
    public function resolve(array $identity,?array $linkActor=null): array {
        $this->calls[]=[$identity,$linkActor];
        return ['id'=>42,'role'=>'USER','email'=>$identity['email'],'full_name'=>$identity['name'],'auth_version'=>0];
    }
}
$cfg=['base_url'=>'http://localhost:8000','providers'=>[]];
foreach (['google','github'] as $p) $cfg['providers'][$p]=['client_id'=>'public-client','client_secret'=>'server-secret','callback'=>'http://localhost:8000/api/oauth/'.$p.'-callback.php','scope'=>$p==='google'?'openid email profile':'read:user user:email','authorize_url'=>$p==='google'?'https://accounts.google.com/o/oauth2/v2/auth':'https://github.com/login/oauth/authorize'];
$http=new OAuthHttpFake(); $users=new OAuthUsersFake(); $service=new OAuthService($cfg,$http,$users);
Session::start();
function oauthBegin(OAuthService $service,string $provider='github',?array $actor=null): array {
    parse_str((string)parse_url($service->begin($provider,'/account',$actor),PHP_URL_QUERY),$params);
    return $params;
}
$p=oauthBegin($service);
oauthCheck($p['code_challenge_method']==='S256' && strlen($p['state'])===64 && !str_contains(json_encode($p),'server-secret'),'state/PKCE and secret stays server-side');
$result=$service->complete('github',['state'=>$p['state'],'code'=>'valid-code','role'=>'ADMIN','user_id'=>1]);
oauthCheck($result['user']['role']==='USER' && $users->calls[0][0]['subject']==='1234' && $users->calls[0][0]['email']==='verified@example.test','GitHub identity and verified email come only from provider');
parse_str($http->calls[0][3],$exchange);
oauthCheck(isset($exchange['code_verifier']) && $exchange['redirect_uri']===$cfg['providers']['github']['callback'],'code exchange uses stored verifier and exact callback');
oauthReject(fn()=>$service->complete('github',['state'=>$p['state'],'code'=>'valid-code']),'callback replay rejected');
$p=oauthBegin($service); $_SESSION['oauth_login_pending'][$p['state']]['expires']=time()-1;
oauthReject(fn()=>$service->complete('github',['state'=>$p['state'],'code'=>'valid-code']),'expired state rejected');
$p=oauthBegin($service);
oauthReject(fn()=>$service->complete('google',['state'=>$p['state'],'code'=>'valid-code']),'provider mismatch rejected');
$p=oauthBegin($service);
oauthReject(fn()=>$service->complete('github',['state'=>$p['state'],'error'=>'access_denied']),'provider cancellation rejected');
$http->verified=false; $p=oauthBegin($service);
oauthReject(fn()=>$service->complete('github',['state'=>$p['state'],'code'=>'valid-code']),'unverified GitHub email rejected');
$http->verified=true;
$actor=['id'=>9,'auth_version'=>3]; $p=oauthBegin($service,'github',$actor);
oauthReject(fn()=>$service->complete('github',['state'=>$p['state'],'code'=>'valid-code'],['id'=>9,'auth_version'=>4]),'link callback cannot cross password/session version change');
$p=oauthBegin($service,'github',$actor);
oauthReject(fn()=>$service->complete('github',['state'=>$p['state'],'code'=>'valid-code'],['id'=>10,'auth_version'=>3]),'link callback cannot cross account switch');
$p=oauthBegin($service,'github',$actor);
$result=$service->complete('github',['state'=>$p['state'],'code'=>'valid-code'],$actor);
oauthCheck($result['linked'] && end($users->calls)[1]===$actor,'linking requires the initiating authenticated actor');

$options=['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA];
foreach (['C:/xampp/apache/conf/openssl.cnf','C:/xampp/php/extras/openssl/openssl.cnf'] as $file) if (is_file($file)) {$options['config']=$file;break;}
$key=openssl_pkey_new($options);
if (!$key) throw new RuntimeException('RSA test fixture unavailable');
$http->cert=openssl_pkey_get_details($key)['key'];
function signGoogle(array $claims,$key): string {
    $encode=static fn($value)=>rtrim(strtr(base64_encode($value),'+/','-_'),'=');
    $body=$encode(json_encode(['alg'=>'RS256','kid'=>'fixture-key'])).'.'.$encode(json_encode($claims));
    openssl_sign($body,$signature,$key,OPENSSL_ALGO_SHA256);
    return $body.'.'.$encode($signature);
}
$p=oauthBegin($service,'google');
$nonce=$_SESSION['oauth_login_pending'][$p['state']]['nonce'];
$claims=['iss'=>'https://accounts.google.com','aud'=>'public-client','sub'=>'google-subject','exp'=>time()+3600,'iat'=>time(),'nonce'=>$nonce,'email'=>'google@example.test','email_verified'=>true,'name'=>'Google User'];
$http->jwt=signGoogle($claims,$key);
$result=$service->complete('google',['state'=>$p['state'],'code'=>'valid-code']);
oauthCheck($result['user']['session_ttl']<=3600 && end($users->calls)[0]['subject']==='google-subject','signed Google identity verified and session bounded by token expiry');
foreach (['aud'=>'other-client','iss'=>'https://evil.test','exp'=>time()-1,'iat'=>time()+3600,'nonce'=>'wrong','email_verified'=>false] as $field=>$bad) {
    oauthReject(fn()=>$service->verifyGoogle(signGoogle(array_replace($claims,[$field=>$bad]),$key),'public-client',$nonce),'Google rejects invalid '.$field);
}
oauthReject(fn()=>$service->verifyGoogle(substr($http->jwt,0,-6).'AAAAAA','public-client',$nonce),'forged Google signature rejected');
oauthReject(fn()=>$service->verifyGoogle('eyJhbGciOiJub25lIn0.e30.fake','public-client',$nonce),'unsigned JWT rejected');
echo "PASS: OAuth protocol tests (generated RSA fixture and fake provider HTTP; no real credentials).\n";

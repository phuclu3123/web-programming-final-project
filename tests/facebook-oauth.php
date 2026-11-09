<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
use App\Core\{Session,HttpError};
use App\Services\OAuthService;
ob_start(); session_save_path(sys_get_temp_dir()); Session::start();
function fbCheck(bool $ok,string $label): void {if (!$ok) throw new RuntimeException('FAIL: '.$label);echo 'PASS: '.$label."\n";}
function fbReject(callable $call,string $label): void {try {$call();} catch (HttpError $e) {fbCheck(true,$label);return;}throw new RuntimeException('FAIL: accepted '.$label);}
final class FacebookHttp implements \App\Contracts\HttpClientInterface {
    public array $calls=[]; public array $changes=[]; public string $profileId='90001'; public bool $offline=false;
    public function json(string $method,string $url,array $headers=[],?string $body=null): array {
        $this->calls[]=[$method,$url,$headers,$body];
        if ($this->offline) throw new RuntimeException('Offline');
        if (str_ends_with($url,'/oauth/access_token')) return ['access_token'=>'private_access','token_type'=>'bearer'];
        if (str_contains($url,'/debug_token?')) return ['data'=>array_replace(['is_valid'=>true,'type'=>'USER','app_id'=>'12345','user_id'=>'90001','expires_at'=>time()+3600,'data_access_expires_at'=>time()+1800],$this->changes)];
        if (str_contains($url,'/me?')) return ['id'=>$this->profileId,'name'=>'Facebook Member','email'=>'untrusted@example.test'];
        throw new LogicException('Unexpected endpoint');
    }
}
final class FacebookUsers implements \App\Contracts\OAuthRepositoryInterface {
    public array $identities=[];
    public function resolveLinked(array $google, array $facebook): array { throw new LogicException('Unexpected confirmation'); }
    public function resolve(array $identity,?array $linkActor=null): array {
        $this->identities[]=[$identity,$linkActor];
        return ['id'=>7,'role'=>'USER','full_name'=>'Existing User','auth_version'=>0];
    }
}
$cfg=['base_url'=>'http://localhost:8000','providers'=>['facebook'=>['client_id'=>'12345','client_secret'=>'server_only_secret','graph_version'=>'v24.0','scope'=>'public_profile','callback'=>'http://localhost:8000/api/oauth/facebook-callback.php','authorize_url'=>'https://www.facebook.com/v24.0/dialog/oauth']]];
$http=new FacebookHttp(); $users=new FacebookUsers(); $service=new OAuthService($cfg,$http,$users);
$begin=static function(?array $actor=null) use ($service): array {parse_str(parse_url($service->begin('facebook','/account',$actor),PHP_URL_QUERY),$query);return $query;};
$p=$begin();
fbCheck(strlen($p['state'])===64 && !isset($p['code_challenge'],$p['client_secret']) && $p['scope']==='public_profile','Facebook code/state flow requests minimum permission, no unsupported PKCE');
$result=$service->complete('facebook',['state'=>$p['state'],'code'=>'code','role'=>'ADMIN']);
fbCheck($result['user']['role']==='USER' && $result['user']['session_ttl']<=1800,'session bounded by token and data-access expiry; role is server-owned');
fbCheck($users->identities[0][0]['email']==='untrusted@example.test' && $users->identities[0][0]['email_verified']===false,'Facebook email is only a hint, never an email-ownership proof');
parse_str($http->calls[0][3],$exchange);
fbCheck($exchange['client_secret']==='server_only_secret' && $exchange['redirect_uri']===$p['redirect_uri'],'code exchanged on server with exact callback');
fbCheck(in_array('Authorization: Bearer 12345|server_only_secret',$http->calls[1][2],true),'token introspected for the configured app');
fbCheck(str_contains($http->calls[2][1],hash_hmac('sha256','private_access','server_only_secret')),'profile request includes appsecret proof');
fbReject(fn()=>$service->complete('facebook',['state'=>$p['state'],'code'=>'code']),'callback replay');
foreach (['is_valid'=>false,'type'=>'APP','app_id'=>['invalid'],'user_id'=>'','expires_at'=>time()-1,'data_access_expires_at'=>time()-1] as $key=>$value) {
    $http->changes=[$key=>$value];$p=$begin();
    fbReject(fn()=>$service->complete('facebook',['state'=>$p['state'],'code'=>'code']),'reject invalid metadata '.$key);
}
$http->changes=[];$http->profileId='different';$p=$begin();
fbReject(fn()=>$service->complete('facebook',['state'=>$p['state'],'code'=>'code']),'profile/token identity mismatch');
$http->profileId='90001';$p=$begin();$_SESSION['oauth_login_pending'][$p['state']]['expires']=time()-1;
fbReject(fn()=>$service->complete('facebook',['state'=>$p['state'],'code'=>'code']),'expired state');
$p=$begin();fbReject(fn()=>$service->complete('facebook',['state'=>$p['state'],'error'=>'access_denied']),'provider cancellation');
$p=$begin();fbReject(fn()=>$service->complete('github',['state'=>$p['state'],'code'=>'code']),'wrong provider');
$actor=['id'=>7,'auth_version'=>4];$p=$begin($actor);
fbReject(fn()=>$service->complete('facebook',['state'=>$p['state'],'code'=>'code'],['id'=>7,'auth_version'=>5]),'linking cannot survive password/session-version change');
$p=$begin($actor);$result=$service->complete('facebook',['state'=>$p['state'],'code'=>'code'],$actor);
fbCheck($result['linked'] && end($users->identities)[1]===$actor,'explicit link intent bound to initiating actor');
$p=$begin();$http->offline=true;$failed=false;
try {$service->complete('facebook',['state'=>$p['state'],'code'=>'code']);} catch (RuntimeException $error) {$failed=true;}
fbCheck($failed,'network failure does not create a login');
$http->offline=false;fbReject(fn()=>$service->complete('facebook',['state'=>$p['state'],'code'=>'code']),'network failure consumes state');
fbCheck(!str_contains(json_encode($_SESSION),'private_access') && !str_contains(json_encode($_SESSION),'server_only_secret'),'provider credentials and tokens not persisted in session');
Session::logout();
echo "PASS: simulated Facebook protocol; real Meta account still requires acceptance.\n";

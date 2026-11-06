<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
use App\Core\{App,HttpError};
use App\Services\{TurnstileService,AccesstradeService,ResendMailService};
final class ProviderFixture implements \App\Contracts\HttpClientInterface {
    public array $calls=[];public array $response=[];public bool $fail=false;
    public function json(string $method,string $url,array $headers=[],?string $body=null):array {
        $this->calls[]=[$method,$url,$headers,$body];if($this->fail)throw new RuntimeException('provider unavailable');return $this->response;
    }
}
$n=0;
function providerCheck(bool $ok,string $label):void {global $n;if(!$ok)throw new RuntimeException('FAIL '.$label);++$n;echo 'PASS '.$label.PHP_EOL;}
function providerReject(callable $action,int $status,string $label):void {try{$action();}catch(HttpError $error){providerCheck($error->status===$status,$label);return;}throw new RuntimeException('FAIL accepted '.$label);}
App::init(['app'=>['base_url'=>'https://couponhub.example/subfolder']]);
$http=new ProviderFixture();$settings=['mode'=>'auto','site_key'=>'site-public','secret_key'=>'secret-private','hostnames'=>'couponhub.example'];
(new TurnstileService($http,['mode'=>'auto']))->verify(null,'login');providerCheck(!$http->calls,'unconfigured auto mode preserves local forms');
providerReject(fn()=>(new TurnstileService($http,['mode'=>'required']))->verify('token','login'),503,'required mode never skips missing keys');
providerReject(fn()=>(new TurnstileService($http,['site_key'=>'partial']))->verify('token','login'),503,'partial auto configuration fails closed');
$turnstile=new TurnstileService($http,$settings);
providerReject(fn()=>$turnstile->verify(null,'login'),422,'missing challenge rejected before network');
providerReject(fn()=>$turnstile->verify(str_repeat('a',2049),'login'),422,'oversized challenge rejected');
$http->response=['success'=>true,'hostname'=>'couponhub.example','action'=>'login'];$turnstile->verify('one-use-token','login','192.0.2.4');
$call=end($http->calls);providerCheck($call[1]==='https://challenges.cloudflare.com/turnstile/v0/siteverify' && json_decode($call[3],true)['remoteip']==='192.0.2.4','challenge sent to fixed HTTPS verifier');
foreach([['success'=>false],['success'=>true,'hostname'=>'evil.example','action'=>'login'],['success'=>true,'hostname'=>'couponhub.example','action'=>'register'],['success'=>'true','hostname'=>'couponhub.example','action'=>'login']] as $bad){$http->response=$bad;providerReject(fn()=>$turnstile->verify('token','login'),403,'reject invalid success/host/action');}
$http->fail=true;providerReject(fn()=>$turnstile->verify('token','login'),503,'provider outage never accepts challenge');$http->fail=false;
$at=new AccesstradeService($http,'publisher-key');$http->response=['data'=>[]];$at->fetch('coupons',2,'shopee');$call=end($http->calls);
providerCheck(str_starts_with($call[1],'https://api.accesstrade.vn/v1/offers_informations/coupon?') && in_array('Authorization: Token publisher-key',$call[2],true),'Vietnam Publisher endpoint and Token authorization');
providerReject(fn()=>$at->fetch('https://evil.example'),422,'provider endpoint cannot be replaced by request');
providerReject(fn()=>(new AccesstradeService($http,'bad'."\r\n".'header'))->fetch(),503,'credential header injection rejected');
$offer=['id'=>'5498845288268871151','name'=>'<b>Ưu đãi thật từ nguồn</b>','merchant'=>'shopee','coupons'=>['CODE1','CODE2'],'content'=>'<script>alert(1)</script>Điều kiện cần đọc','link'=>'https://shopee.vn/offer','start_time'=>'2026-11-05','end_time'=>'2026-12-01','discount_percentage'=>10];
$rows=$at->normalize(['data'=>[$offer]]);providerCheck(count($rows['offers'])===2 && $rows['offers'][0]['external_key']!==$rows['offers'][1]['external_key'],'one provider offer can have two stable coupon identities');
$p=$rows['offers'][0]['payload'];providerCheck($p['min_order_amount']===null && $p['terms']==='' && $p['starts_at']==='2026-11-04 17:00:00' && $p['ends_at']==='2026-12-01 16:59:59','missing monetary conditions stay unknown; date-only ranges use Vietnam timezone');
providerCheck(!str_contains($p['description'],'<script>') && $p['title']==='Ưu đãi thật từ nguồn','external HTML becomes plain escaped display content');
$bad=$offer;$bad['id']=[];$bad2=$offer;$bad2['coupons']=["CODE\nHEADER"];
providerCheck(count($at->normalize(['data'=>[$bad,$bad2]])['offers'])===0,'invalid external IDs and coupon code control characters skipped');
providerReject(fn()=>$at->normalize(['unexpected'=>['items'=>[]]]),502,'unexpected feed shape cannot silently clear data');
$missing=$offer;unset($missing['discount_percentage']);$missing['discount_value']='1e309';$missing['start_time']='2026-02-31';
$p=$at->normalize(['data'=>[$missing]])['offers'][0]['payload'];providerCheck($p['discount_type']==='' && $p['starts_at']===null,'malformed amounts and dates are not fabricated');
$nested=$offer;unset($nested['start_time'],$nested['end_time']);$nested['start_date']='Fri, 06 Nov 2026 01:02:03 GMT';$nested['end_date']=['$date'=>1794009600000];$nested['min_spend']=120000;
$p=$at->normalize(['success'=>true,'data'=>['count'=>2,'data'=>[$nested]]])['offers'][0]['payload'];
providerCheck($p['starts_at']==='2026-11-06 01:02:03' && $p['ends_at']===gmdate('Y-m-d H:i:s',1794009600) && $p['min_order_amount']===120000.0,'documented coupon data.data envelope, GMT/Mongo dates and min_spend supported');
providerReject(fn()=>$at->normalize(['success'=>false,'data'=>[]]),502,'provider-declared failure never treated as empty success');
$http->response=['id'=>'mail-accepted'];$mail=new ResendMailService($http,['resend_api_key'=>'server-key','resend_from'=>'CouponHub <noreply@example.test>']);$mail->sendOtp('user@example.test','123456');$call=end($http->calls);$body=json_decode($call[3],true);
providerCheck($call[1]==='https://api.resend.com/emails' && in_array('Authorization: Bearer server-key',$call[2],true) && $body['to']===['user@example.test'] && str_contains($body['text'],'123456'),'Resend request contract includes OTP and server-only auth');
providerCheck(count(array_filter($call[2],fn($h)=>str_starts_with($h,'Idempotency-Key: ')))===1,'Resend send has an idempotency key');
$http->response=[];try{$mail->sendOtp('user@example.test','123456');throw new LogicException('accepted missing id');}catch(RuntimeException $e){providerCheck(!$e instanceof LogicException,'Resend must acknowledge message ID');}
echo "PASS $n provider checks; injected transports only, no real OAuth/email/provider credentials used.\n";

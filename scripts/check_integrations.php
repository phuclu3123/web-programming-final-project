<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$checks=[];
$checks['PHP cURL HTTPS']=extension_loaded('curl');
$checks['OpenSSL OAuth']=extension_loaded('openssl');
$base=(string)config('app.base_url','');
$checks['APP_URL tuyệt đối']=in_array(parse_url($base,PHP_URL_SCHEME),['http','https'],true) && (bool)parse_url($base,PHP_URL_HOST);
$checks['ACCESSTRADE key']=(string)config('integrations.accesstrade.access_key','')!=='';
$guard=new \App\Services\TurnstileService(new \App\Core\HttpClient(),(array)config('integrations.turnstile',[]));
try {$checks['Turnstile bật và đủ cấu hình']=$guard->enabled() && $guard->siteKey()!=='' && config('integrations.turnstile.secret_key','')!=='' && count($guard->hostnames())>0;}catch(Throwable){$checks['Turnstile bật và đủ cấu hình']=false;}
$checks['Resend key/sender']=config('mail.driver')==='resend' && config('mail.resend_api_key','')!=='' && config('mail.resend_from','')!=='';
$checks['OTP secret tối thiểu 32 ký tự']=strlen((string)config('auth.otp_secret',''))>=32;
foreach(['google','github','facebook'] as $provider){$checks['OAuth '.$provider]=(new \App\Core\OAuthPreparation((array)config('oauth')))->status($provider)['ready'];}
if(in_array('--database',$argv,true)) {
    try {$checks['Bảng hàng chờ ACCESSTRADE']=(new \App\Repositories\ExternalOfferRepository(\App\Core\Database::connection()))->ready();}catch(Throwable){$checks['Bảng hàng chờ ACCESSTRADE']=false;}
}
foreach($checks as $label=>$ok)echo ($ok?'READY ':'MISSING ').$label.PHP_EOL;
echo "Chỉ kiểm tra cấu hình; không gửi email, không gọi OAuth, không xác nhận API key hoạt động.\n";
exit(in_array(false,$checks,true)?1:0);

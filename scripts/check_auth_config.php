<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
$failed=false;
$check=static function(bool $ok,string $name) use (&$failed): void {
    echo ($ok?'OK   ':'MISS ').$name.PHP_EOL;
    if (!$ok) $failed=true;
};
$check(PHP_VERSION_ID>=80100,'PHP >= 8.1');
foreach (['curl','openssl','mysqli','mbstring'] as $extension) $check(extension_loaded($extension),'extension '.$extension);
echo 'INFO Configuration source: '.(is_file(__DIR__.'/../.env')?'.env and process environment':'process environment; no .env file').PHP_EOL;
$url=parse_url((string)config('app.base_url',''));
$check(is_array($url) && isset($url['scheme'],$url['host']) && in_array($url['scheme'],['http','https'],true)
    && ($url['scheme']==='https' || in_array($url['host'],['localhost','127.0.0.1'],true)),'COUPONHUB_APP_URL canonical URL');
foreach (['google','github'] as $provider) {
    $status=(new \App\Core\OAuthPreparation((array)config('oauth')))->status($provider);
    $check($status['ready'],$provider.' OAuth: '.($status['ready']?'configured':implode(', ',$status['missing'])));
}
$check(strlen((string)config('auth.otp_secret',''))>=32,'COUPONHUB_OTP_SECRET >= 32 characters');
$driver=config('mail.driver');
$check(in_array($driver,['resend','smtp'],true),'mail driver is resend or smtp');
if ($driver==='resend') {
    $check(config('mail.resend_api_key','')!=='','RESEND_API_KEY');
    $from=(string)config('mail.resend_from','');
    $address=preg_match('/<([^<>]+)>$/',$from,$match)?$match[1]:$from;
    $check(!preg_match('/[\r\n]/',$from) && filter_var($address,FILTER_VALIDATE_EMAIL)!==false,'RESEND_FROM_EMAIL (verification must be checked in Resend dashboard)');
} elseif ($driver==='smtp') echo 'INFO SMTP selected; real delivery is not tested by this command.'.PHP_EOL;
if (in_array('--db',$argv,true)) {
    try {
        $db=\App\Core\Database::connection();
        $columns=[
            'users'=>['id','email','username','password_hash','full_name','role','status','deleted_at','auth_version'],
            'oauth_accounts'=>['id','user_id','provider','provider_user_id','provider_email','email_verified'],
            'password_reset_tokens'=>['user_id','token_hash','request_ip_hash','created_at','expires_at','used_at'],
            'activity_logs'=>['user_id','action','description'],
        ];
        foreach ($columns as $table=>$required) {
            $read=$db->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
            $read->execute([$table]);
            $found=array_column($read->fetchAll(),'COLUMN_NAME');
            $check(array_diff($required,$found)===[],'database columns '.$table);
        }
        $read=$db->query("SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE() AND ROUTINE_NAME='ch_auth_user' AND ROUTINE_TYPE='PROCEDURE'");
        $check((int)$read->fetchColumn()===1,'database auth routine ch_auth_user');
    } catch (Throwable $e) { $check(false,'Database connection/schema (details omitted)'); }
}
echo 'No secrets printed. No email sent. No database writes.'.PHP_EOL;
exit($failed?1:0);

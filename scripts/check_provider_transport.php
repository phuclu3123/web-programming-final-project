<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(!in_array('--network',$argv,true))exit("Use --network to check public Google/GitHub HTTPS endpoints. No credentials are sent.\n");
require __DIR__.'/../app/bootstrap.php';
$http=new App\Core\HttpClient();$failed=false;
foreach(['Google signing keys'=>'https://www.googleapis.com/oauth2/v3/certs','GitHub public API'=>'https://api.github.com/rate_limit'] as $name=>$endpoint){
    try{$http->json('GET',$endpoint);echo 'PASS verified HTTPS: '.$name.PHP_EOL;}
    catch(Throwable $error){$failed=true;echo 'FAIL HTTPS: '.$name.' ('.$error->getMessage().')'.PHP_EOL;}
}
echo "Connectivity/TLS only. No OAuth sign-in or email delivery verified.\n";
exit($failed?1:0);

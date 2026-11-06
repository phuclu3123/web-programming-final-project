<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
use App\Core\{Request,HttpError};
foreach([['csrf'=>'current'],['_token'=>'legacy'],['csrf'=>'current','_token'=>'legacy']] as $data){
    $actual=(new Request([],$data,['REQUEST_METHOD'=>'POST']))->csrfToken();
    if($actual!==($data['csrf']??$data['_token']))throw new RuntimeException('Token alias mismatch');
}
try{(new Request([],['csrf'=>['bad']],[]))->csrfToken();throw new LogicException('Array token accepted');}
catch(HttpError $e){if($e->status!==403)throw $e;}
echo "PASS shared request CSRF aliases, deterministic precedence and array rejection.\n";
if(!(new Request([],[],['HTTP_ACCEPT'=>'text/html']))->acceptsHtml())throw new RuntimeException('Native HTML form not detected');
foreach([['HTTP_ACCEPT'=>'application/json'],['HTTP_ACCEPT'=>'text/html','HTTP_X_REQUESTED_WITH'=>'XMLHttpRequest'],['HTTP_ACCEPT'=>'text/html','CONTENT_TYPE'=>'application/json']] as $server){
    if((new Request([],[],$server))->acceptsHtml())throw new RuntimeException('API request detected as native browser form');
}
echo "PASS explicit native form vs JSON/XHR response negotiation.\n";

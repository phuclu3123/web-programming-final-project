<?php
declare(strict_types=1);
if(getenv('COUPONHUB_RUN_DB_TEST')!=='1')exit('SKIP');
require __DIR__.'/../app/bootstrap.php';
$db=App\Core\Database::connection();
if($db->query('SELECT DATABASE()')->fetchColumn()!=='couponhub_merge_test' || (int)$db->query('SELECT @@port')->fetchColumn()!==13379)throw new RuntimeException('Unsafe database');
$options=getopt('',['suffix:']);$suffix=$options['suffix']??'browser';if(!is_string($suffix)||!preg_match('/\A[a-zA-Z0-9_-]{1,40}\z/D',$suffix))throw new RuntimeException('Invalid fixture suffix');
$admin=(int)$db->query("SELECT id FROM users WHERE username='admin_demo'")->fetchColumn();
$http=new class implements App\Contracts\HttpClientInterface { public function json(string $method,string $url,array $headers=[],?string $body=null):array {throw new LogicException('Fixture never calls providers');}};
$data=(new App\Services\AccesstradeService($http,''))->normalize(['data'=>[['id'=>'local-review-fixture-'.$suffix,'name'=>'[TEST] Coupon kiểm định biểu mẫu','coupons'=>['TEST_REVIEW_ONLY_'.$suffix],'merchant'=>'local-fixture','link'=>'https://example.com/offer','content'=>'Dữ liệu kiểm thử riêng, không dùng để thanh toán.','start_time'=>date('Y-m-d'),'end_time'=>date('Y-m-d',time()+86400*7)]]]);
$repo=new App\Repositories\ExternalOfferRepository($db);$repo->stage($admin,$data['offers']);
$q=$db->prepare('SELECT id FROM external_offers WHERE external_key=?');$q->execute([$data['offers'][0]['external_key']]);echo $q->fetchColumn();

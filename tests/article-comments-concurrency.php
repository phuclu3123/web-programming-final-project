<?php
declare(strict_types=1);
if(getenv('COUPONHUB_RUN_DB_TEST')!=='1')exit('SKIP: explicit isolated DB opt-in required'.PHP_EOL);
require __DIR__.'/../app/bootstrap.php';
$db=\App\Core\Database::connection();
if((int)$db->query('SELECT @@port')->fetchColumn()!==13379 || $db->query('SELECT DATABASE()')->fetchColumn()!=='couponhub_merge_test')throw new RuntimeException('Refusing non-isolated database');
$repo=new \App\Repositories\ArticleCommentRepository($db);
if(($argv[1]??'')==='--probe'){
    echo json_encode($repo->create((int)$argv[2],(int)$argv[3],'Concurrent network retry acceptance',(string)$argv[4]));exit;
}
$actor=(int)$db->query("SELECT id FROM users WHERE username='user_demo'")->fetchColumn();
$article=(int)$db->query("SELECT id FROM articles WHERE slug='doc-dieu-kien-ma'")->fetchColumn();
if(!$actor || !$article)throw new RuntimeException('Missing isolated editorial fixture');
$key=bin2hex(random_bytes(16));$processes=[];
for($i=0;$i<8;$i++){
    $p=proc_open([PHP_BINARY,__FILE__,'--probe',(string)$actor,(string)$article,$key],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
    if(!is_resource($p))throw new RuntimeException('Cannot start concurrent worker');
    $processes[]=[$p,$pipes];
}
$ids=[];$created=0;
foreach($processes as [$p,$pipes]){
    $out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    if(proc_close($p)!==0)throw new RuntimeException('Worker failed: '.$error);
    $r=json_decode($out,true,512,JSON_THROW_ON_ERROR);$ids[]=$r['id'];$created+=(int)$r['created'];
}
if(count(array_unique($ids))!==1 || $created!==1)throw new RuntimeException('Concurrent retry duplicated a write');
$q=$db->prepare('SELECT COUNT(*) FROM article_comments WHERE user_id=? AND request_id=?');$q->execute([$actor,$key]);
if((int)$q->fetchColumn()!==1)throw new RuntimeException('Expected one persisted comment');
echo "PASS 8 concurrent PHP workers: one comment ID, one creation, one persisted row.\n";

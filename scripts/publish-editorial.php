<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$options=getopt('',['apply','expected-database:','author-id:']);$cfg=config('database');
if(!isset($options['apply']) || ($options['expected-database']??'')!==$cfg['database'] || !in_array($cfg['host'],['localhost','127.0.0.1'],true)){
    fwrite(STDERR,"Back up first. Usage: php scripts/publish-editorial.php --apply --expected-database=DB --author-id=ACTIVE_ADMIN_ID\n");exit(2);
}
$author=filter_var($options['author-id']??'',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$author)throw new RuntimeException('An existing active administrator is required.');
$rows=require __DIR__.'/../app/content/editorial-articles.php';
foreach(productGuides() as $slug=>$guide){$rows[$slug]=['title'=>$guide['title'],'summary'=>$guide['summary'],'body'=>implode("\n\n",array_merge(...array_map(static fn($section)=>[$section['title'],$section['body']],$guide['sections'])))];}
$db=\App\Core\Database::connection();$db->beginTransaction();$result=[];
try{
    $q=$db->prepare("SELECT id FROM users WHERE id=? AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL FOR UPDATE");$q->execute([$author]);
    if(!$q->fetch())throw new RuntimeException('Author is not an active administrator.');
    foreach($rows as $slug=>$row){
        $q=$db->prepare('SELECT id FROM articles WHERE slug=?');$q->execute([$slug]);$existing=$q->fetchColumn();
        if($existing){$result[]=['slug'=>$slug,'id'=>(int)$existing,'action'=>'kept-existing'];continue;}
        $q=$db->prepare("INSERT INTO articles(author_id,title,slug,summary,body,status,published_at) VALUES(?,?,?,?,?,'PUBLISHED',UTC_TIMESTAMP())");$q->execute([$author,$row['title'],$slug,$row['summary'],$row['body']]);$id=(int)$db->lastInsertId();
        $db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')->execute([$author,'ARTICLE_PUBLISH_EDITORIAL',json_encode(['article_id'=>$id,'slug'=>$slug],JSON_THROW_ON_ERROR)]);
        $result[]=['slug'=>$slug,'id'=>$id,'action'=>'created'];
    }
    $db->commit();echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}

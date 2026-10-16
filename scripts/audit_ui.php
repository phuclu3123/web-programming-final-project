<?php
declare(strict_types=1);
// CLI-only read-only rendering. Uses an isolated temporary session, never a browser session.
if (PHP_SAPI!=='cli') { http_response_code(403); exit; }
$role=$argv[1] ?? 'GUEST';
$path=$argv[2] ?? '/';
if (!in_array($role,['GUEST','USER','MERCHANT','ADMIN'],true) || !str_starts_with($path,'/') || str_starts_with($path,'//')) exit(2);
$root=dirname(__DIR__);
$sessionDir=$root.'/.runtime/ui-audit-sessions';
if (!is_dir($sessionDir)) mkdir($sessionDir,0700,true);
ini_set('session.save_path',$sessionDir);
$_SERVER=['REQUEST_METHOD'=>'GET','REQUEST_URI'=>$path,'SCRIPT_NAME'=>'/index.php','HTTP_HOST'=>'127.0.0.1:8000'];
parse_str(parse_url($path,PHP_URL_QUERY) ?: '',$_GET);
$_POST=[];
require $root.'/app/bootstrap.php';
\App\Core\Session::start();
$db=\App\Core\Database::connection();
$db->query('SET TRANSACTION READ ONLY');
$db->beginTransaction();
if ($role!=='GUEST') {
    $statement=$db->prepare("SELECT id, full_name, role, auth_version FROM users WHERE role=? AND status='ACTIVE' AND deleted_at IS NULL ORDER BY id LIMIT 1");
    $statement->execute([$role]);
    $user=$statement->fetch();
    if (!$user) throw new RuntimeException('No audit account for role '.$role);
    \App\Core\Auth::login($user);
}
$router=new \App\Core\Router();
require $root.'/config/routes.php';
http_response_code(200);
ob_start();
register_shutdown_function(static function() use($root,$role,$path): void {
    $html=ob_get_contents() ?: '';
    if (ob_get_level()) ob_end_clean();
    if (session_status()===PHP_SESSION_ACTIVE) session_destroy();
    $document=new DOMDocument();
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    $ids=[]; $duplicates=[]; $missing=[]; $unlabelled=[]; $links=[];
    foreach ($document->getElementsByTagName('*') as $node) {
        $id=$node->getAttribute('id');
        if ($id!=='') { if(isset($ids[$id])) $duplicates[]=$id; $ids[$id]=true; }
        if ($node->tagName==='a') {
            $href=$node->getAttribute('href');
            if (preg_match('~^/(?:merchant/(?:profile/[0-9]+|coupons/[0-9]+/edit|campaigns/[0-9]+(?:/edit)?)|admin/(?:campaigns/[0-9]+|articles/[0-9]+/edit))(?:[?#].*)?$~',$href)) $links[]=$href;
        }
        $asset=match($node->tagName) { 'img','script'=>$node->getAttribute('src'), 'link'=>$node->getAttribute('rel')==='stylesheet' ? $node->getAttribute('href') : '', default=>'' };
        if ($asset!=='' && !preg_match('~^(https?:|data:|//)~',$asset)) {
            $file=ltrim((string)parse_url($asset,PHP_URL_PATH),'/');
            if (!is_file($root.'/'.$file)) $missing[]=$asset;
        }
    }
    $xpath=new DOMXPath($document);
    foreach ($xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="checkbox") and not(@type="radio")]|//textarea|//select') as $field) {
        if ($field->hasAttribute('aria-label') || $field->hasAttribute('aria-labelledby')) continue;
        $id=$field->getAttribute('id');
        if ($id!=='' && $xpath->query('//label[@for="'.$id.'"]')->length) continue;
        if ($xpath->query('ancestor::label',$field)->length) continue;
        $unlabelled[]=$field->getAttribute('name') ?: $field->tagName;
    }
    $report=['role'=>$role,'path'=>$path,'status'=>http_response_code(),'bytes'=>strlen($html),'h1'=>$xpath->query('//h1[not(ancestor::*[@hidden or @aria-hidden="true"])]')->length,'duplicateIds'=>array_values(array_unique($duplicates)),'missingAssets'=>array_values(array_unique($missing)),'unlabelledFields'=>array_values(array_unique($unlabelled)),'error'=>preg_match('~Fatal error|Parse error|<b>Warning</b>~',$html)===1];
    $report['links']=array_values(array_unique($links));
    echo json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
});
$router->dispatch(new \App\Core\Request($_GET,[],$_SERVER));

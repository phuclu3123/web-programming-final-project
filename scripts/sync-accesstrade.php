<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$options=getopt('',['apply','expected-database:','actor:','resource:','page:','merchant:']);
try {
    $provider=new \App\Services\AccesstradeService(new \App\Core\HttpClient(),(string)config('integrations.accesstrade.access_key',''));
    $resource=(string)($options['resource'] ?? 'offers');
    $response=$provider->fetch($resource,positiveId((string)($options['page'] ?? '1')),(string)($options['merchant'] ?? ''));
    if (in_array($resource,['merchants','categories'],true)) {
        if(isset($options['apply']))throw new RuntimeException('Danh mục nhà cung cấp chỉ để tham khảo, không tự tạo cửa hàng.');
        echo json_encode($response,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;exit;
    }
    $result=$provider->normalize($response);
    if(!isset($options['apply'])) { echo json_encode($result,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;exit; }
    $db=\App\Core\Database::connection();$name=(string)$db->query('SELECT DATABASE()')->fetchColumn();
    if (($options['expected-database'] ?? '')!==$name || !isset($options['actor']))throw new RuntimeException('Cần --expected-database chính xác và --actor là ID admin đang hoạt động.');
    $repo=new \App\Repositories\ExternalOfferRepository($db);
    if(!$repo->ready())throw new RuntimeException('Chạy migration external_offers trước.');
    $count=$repo->stage(positiveId((string)$options['actor']),$result['offers']);
    echo "Đã cập nhật $count mục trong hàng chờ; không xuất bản hoặc sửa coupon công khai.\n";
} catch(Throwable $error) {fwrite(STDERR,$error instanceof \App\Core\HttpError ? $error->getMessage().PHP_EOL : "Không đồng bộ được. Kiểm tra cấu hình, quyền Admin và schema.\n");exit(1);}

<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Services\DealRequestValidator as Validator;

function drCheck(bool $condition, string $description): void
{
    if (!$condition) throw new RuntimeException('FAIL ' . $description);
}
function drReject(callable $fn, string $description): void
{
    try { $fn(); } catch (DomainException) { return; }
    throw new RuntimeException('FAIL rejected ' . $description);
}

$data = Validator::request('Ai có mã Highlands hôm nay không?', 'Mình đang tìm coupon còn hiệu lực tại cửa hàng.');
drCheck($data['title'] === 'Ai có mã Highlands hôm nay không?', 'valid Unicode request');
drCheck($data['body'] !== '', 'request body');
drReject(fn() => Validator::request('Ngắn', 'Nội dung hợp lệ nhưng tiêu đề quá ngắn'), 'short title');
drReject(fn() => Validator::request(str_repeat('A', 161), 'Mô tả ít nhất mười ký tự'), 'long title');
drReject(fn() => Validator::request('Tiêu đề đầy đủ', str_repeat('B', 1501)), 'long body');
drReject(fn() => Validator::request("Abc\xFF title", 'Mô tả ít nhất mười ký tự'), 'invalid UTF8');
$reply = Validator::reply('34', 'Điều kiện đơn tối thiểu 150.000đ');
drCheck($reply['coupon_id'] === 34, 'valid coupon id');
drReject(fn() => Validator::reply('34 OR 1=1', ''), 'SQL injection coupon ID');
drReject(fn() => Validator::reply('-5', ''), 'negative ID');
drReject(fn() => Validator::reply('0', ''), 'zero ID');
drReject(fn() => Validator::reply('34', str_repeat('x', 501)), 'long note');

$routes = file_get_contents(__DIR__ . '/../config/routes.php');
foreach (["'/deal-requests'", "'/deal-requests/create'", "'/deal-requests/{id}'", "'/deal-requests/{id}/reply'", "'/deal-requests/{id}/close'"] as $route) {
    drCheck(str_contains($routes, $route), 'route ' . $route);
}
$router = file_get_contents(__DIR__ . '/../app/core/Router.php');
drCheck(str_contains($router, "'/deal-requests/create'") && str_contains($router, '/deal-requests/[0-9]+/'), 'role guard');
$migration = file_get_contents(__DIR__ . '/../database/migrations/phase8_deal_requests.sql');
drCheck(str_contains($migration, 'uq_deal_request_coupon'), 'unique coupon reply');
drCheck(str_contains($migration, 'fk_deal_requests_author'), 'owner FK');
$repo = file_get_contents(__DIR__ . '/../app/repositories/DealRequestRepository.php');
drCheck(str_contains($repo, "s.effective_status='ACTIVE'"), 'ACTIVE coupon eligibility');
drCheck(str_contains($repo, "d.user_id<>?"), 'no self answers');
drCheck(str_contains($repo, 'AND user_id=?'), 'close ownership');

$status = 'OPEN';
$listing = ['items'=>[[
    'id'=>1, 'title'=>'<script>alert(1)</script>', 'body'=>'<img src=x onerror=alert(2)>',
    'status'=>'OPEN', 'created_at'=>'2026-10-27 08:00:00', 'author_name'=>'<b>attacker</b>', 'reply_count'=>0,
]], 'page'=>1, 'pages'=>1, 'total'=>1];
ob_start();
try { require __DIR__ . '/../app/views/deal-requests/index.php'; $html = ob_get_contents(); }
finally { ob_end_clean(); }
drCheck(!str_contains($html, '<script>alert(1)</script>') && !str_contains($html, '<img src=x'), 'list output escaped');
drCheck(str_contains($html, '&lt;script&gt;'), 'encoded title visible');

$dealRequest = ['id'=>1, 'title'=>'<script>alert(1)</script>', 'body'=>'<img src=x onerror=alert(2)>', 'user_id'=>19,
    'status'=>'OPEN','author_name'=>'<b>someone</b>','created_at'=>'2026-10-27 08:00:00'];
$replies = [[
    'id'=>1,'user_id'=>20,'coupon_id'=>34,'note'=>'<svg onload=alert(3)>',
    'created_at'=>'2026-10-27 08:30:00', 'responder_name'=>'Visitor',
    'coupon_title'=>'<script>alert(4)</script>', 'merchant_name'=>'CGV','effective_status'=>'ACTIVE',
]];
ob_start();
try { require __DIR__ . '/../app/views/deal-requests/show.php'; $html = ob_get_contents(); }
finally { ob_end_clean(); }
drCheck(!str_contains($html, '<script>alert(') && !str_contains($html, '<svg onload=') && !str_contains($html, '<img src='), 'detail output escaped');
drCheck(str_contains($html, '/coupons/34'), 'detail links proposed coupon');
echo "PASS: deal request validation, routes, authorization guards, SQL conditions and XSS escaping.\n";

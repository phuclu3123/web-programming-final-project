<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
\App\Core\App::init(['app'=>['base_url'=>'/CouponHub_UI','timezone'=>'Asia/Ho_Chi_Minh']]);
function presentationCheck(bool $ok,string $message): void { if (!$ok) throw new RuntimeException($message); echo "PASS: $message\n"; }
$overview=campaignOverview([
 ['merchant_name'=>'Nike','merchant_id'=>7,'category_name'=>'Thời trang','usage_channel'=>'ONLINE','min_order_amount'=>1000000],
 ['merchant_name'=>'Nike','merchant_id'=>7,'category_name'=>'Thời trang','usage_channel'=>'BOTH','min_order_amount'=>0],
 ['merchant_name'=>'Hasaki','merchant_id'=>9,'category_name'=>'Làm đẹp','usage_channel'=>'ONLINE','min_order_amount'=>300000],
]);
presentationCheck($overview['count']===3 && count($overview['brands'])===2 && count($overview['categories'])===2,'overview counts real coupons and distinct brands/categories');
presentationCheck($overview['minimum']===0.0 && campaignOverview([])['minimum']===null,'zero-order offers remain distinct from an empty campaign');
$campaign=['id'=>3,'name'=>'Nike · <script>alert(1)</script> [DEMO]','merchant_name'=>'Nike','starts_at'=>'2026-01-01 00:00:00','ends_at'=>'2099-01-01 00:00:00','description'=>'A <b>description</b>','banner_path'=>null,'coupon_count'=>3];
$visual=campaignVisual($campaign);
presentationCheck(str_ends_with($visual['image'],'nike-training.webp') && $visual['tone']==='sport','Nike uses its own local brand art');
$campaign['banner_path']='https://example.com/banner.webp';
presentationCheck(campaignVisual($campaign)['image']===$campaign['banner_path'],'merchant campaign banner retains priority');
$campaign['banner_path']='javascript:alert(1)';
presentationCheck(str_ends_with(campaignVisual($campaign)['image'],'nike-training.webp'),'unsafe legacy artwork falls back to a local image');
$detailMode=false;$posterPriority=false;ob_start();require __DIR__.'/../app/views/partials/campaign-feature.php';$html=(string)ob_get_clean();
presentationCheck(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script>') && str_contains($html,'&lt;b&gt;description'),'campaign titles and descriptions remain escaped');
presentationCheck(str_contains($html,'Coupon minh họa') && str_contains($html,'/CouponHub_UI/campaigns/3'),'demo disclosure and subfolder links survive the redesign');

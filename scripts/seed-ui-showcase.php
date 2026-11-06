<?php
declare(strict_types=1);
// Additive local showcase data. This is deliberately a CLI operation, never a web route.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../app/bootstrap.php';
$options=getopt('', ['apply','expected-database:']);
$config=config('database');
if (!isset($options['apply']) || ($options['expected-database'] ?? '')!==$config['database'] || !in_array($config['host'],['localhost','127.0.0.1'],true)) {
    fwrite(STDERR,"Back up the local database first. Usage: php scripts/seed-ui-showcase.php --apply --expected-database=YOUR_LOCAL_DB\n"); exit(2);
}
$db=\App\Core\Database::connection();
$admin=$db->query("SELECT id FROM users WHERE username='admin_demo' AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL")->fetchColumn();
$merchantUser=$db->query("SELECT id FROM users WHERE username='merchant_lifestyle' AND role='MERCHANT' AND status='ACTIVE' AND deleted_at IS NULL")->fetchColumn();
if (!$admin || !$merchantUser) throw new RuntimeException('This additive showcase requires the existing local demo accounts. Nothing was imported.');
$rows=[
 ['Lazada','lazada','mua-sam','Chọn món cho tổ ấm.','Từ đồ dùng hằng ngày đến một góc nhà mới. So sánh mức giảm và điều kiện đơn hàng trước khi chọn mã.','lazada-campaign','ONLINE','FIXED_AMOUNT',50000,350000,null],
 ['Grab','grab','an-uong','Bữa ngon, đúng nhịp.','Một bữa trưa tại văn phòng hay món quen cho buổi tối. Tìm mã theo đơn tối thiểu, xem giới hạn giảm trước khi đặt.','grab-food-wide','ONLINE','FIXED_AMOUNT',25000,120000,null],
 ['Hasaki','hasaki','lam-dep','Một chút chăm da.','Làm sạch, dưỡng ẩm và chăm sóc mỗi ngày. Chọn coupon phù hợp với giỏ mỹ phẩm, không thêm sản phẩm chỉ để đủ mức đơn.','hasaki-skincare','BOTH','PERCENTAGE',12,250000,60000],
 ['CGV','cgv','giai-tri','Hẹn nhau màn ảnh lớn.','Lên kế hoạch cho buổi xem phim cùng bạn bè. Kiểm tra kênh đặt vé, khung giờ và điều kiện từng coupon trước khi dùng.',null,'ONLINE','FIXED_AMOUNT',30000,180000,null],
 ['Highlands Coffee','highlands-coffee','an-uong','Một khoảng nghỉ đậm vị.','Cà phê cho buổi sáng, một chỗ hẹn cho buổi chiều. Xem mã áp dụng online hay tại quầy để chọn đúng cách đặt.','highlands-phin','BOTH','FIXED_AMOUNT',20000,100000,null],
 ['Phúc Long','phuc-long','an-uong','Hẹn một ly trà.','Một cuộc hẹn nhỏ, một vị trà quen. Bộ sưu tập coupon minh họa cho đơn đồ uống và những lần ghé quán cùng bạn bè.','phuc-long-identity','BOTH','PERCENTAGE',15,120000,35000],
 ['Coolmate','coolmate','thoi-trang','Mặc nhẹ, đi xa.','Trang phục cho nhịp sống mỗi ngày và những chuyến đi. Chọn ưu đãi theo giá trị giỏ hàng, xem hạn dùng ngay trên mã.','coolmate-collection','ONLINE','FIXED_AMOUNT',60000,400000,null],
 ['Nike','nike','thoi-trang','Chuyển động theo cách bạn.','Từ bước chạy đầu ngày đến buổi tập cuối tuần. Khám phá bộ sưu tập coupon minh họa, chọn theo nhu cầu và điều kiện đơn hàng.','nike-training','ONLINE','PERCENTAGE',10,1000000,200000],
];
$receipt=['database'=>$config['database'],'created_merchants'=>[],'created_campaigns'=>[],'created_coupons'=>[],'linked_coupons'=>0];
$locked=$db->query("SELECT GET_LOCK('couponhub_ui_showcase_v1',10)")->fetchColumn();
if ((int)$locked!==1) throw new RuntimeException('Another showcase import is running.');
try {
    $db->beginTransaction();
    foreach ($rows as [$brand,$slug,$categorySlug,$title,$description,$image,$channel,$type,$value,$minimum,$maximum]) {
        $q=$db->prepare('SELECT id FROM categories WHERE slug=? AND is_active=1');$q->execute([$categorySlug]);$category=$q->fetchColumn();
        if (!$category) throw new RuntimeException('Missing category: '.$categorySlug);
        $q=$db->prepare("SELECT id,status,deleted_at FROM merchants WHERE slug=? OR name=? ORDER BY id LIMIT 1");$q->execute([$slug,$brand]);$merchant=$q->fetch();
        if (!$merchant) {
            $q=$db->prepare("INSERT INTO merchants(owner_user_id,name,slug,description,website_url,status,reviewed_by,reviewed_at,review_note) VALUES(?,?,?,?,?,'APPROVED',?,UTC_TIMESTAMP(),?)");
            $q->execute([$merchantUser,$brand,$slug,'[DEMO] Thương hiệu trong bộ dữ liệu trình diễn CouponHub. Không phải tài khoản đại diện thương hiệu.', $slug==='nike'?'https://www.nike.com/vn/':'https://phuclong.com.vn/',$admin,'[DEMO] Chỉ dùng trong bản local trình diễn.']);
            $merchant=['id'=>(int)$db->lastInsertId(),'status'=>'APPROVED','deleted_at'=>null];$receipt['created_merchants'][]=$merchant['id'];
            $q=$db->prepare("INSERT INTO merchant_memberships(user_id,merchant_id,status,granted_by) VALUES(?,?,'ACTIVE',?)");$q->execute([$merchantUser,$merchant['id'],$admin]);
        }
        if ($merchant['status']!=='APPROVED' || $merchant['deleted_at']!==null) throw new RuntimeException('Existing merchant is unavailable; refusing to change its approval: '.$slug);
        $q=$db->prepare("SELECT mm.user_id FROM merchant_memberships mm JOIN users u ON u.id=mm.user_id WHERE mm.merchant_id=? AND mm.status='ACTIVE' AND u.role='MERCHANT' AND u.status='ACTIVE' AND u.deleted_at IS NULL ORDER BY mm.user_id LIMIT 1");$q->execute([$merchant['id']]);$couponAuthor=$q->fetchColumn();
        if (!$couponAuthor) throw new RuntimeException('No active demo manager for merchant: '.$slug);
        $campaignSlug='showcase-v1-'.$slug;
        $q=$db->prepare('SELECT id FROM campaigns WHERE slug=?');$q->execute([$campaignSlug]);$campaignId=$q->fetchColumn();
        if (!$campaignId) {
            $banner=$image ? 'assets/images/brands/'.$image.'.webp' : null;
            if ($banner && !is_file(dirname(__DIR__).'/'.$banner)) throw new RuntimeException('Missing campaign artwork: '.$banner);
            $q=$db->prepare("INSERT INTO campaigns(owner_merchant_id,created_by,name,slug,description,banner_path,starts_at,ends_at,status) VALUES(?,?,?,?,?,?,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 90 DAY),'PUBLISHED')");
            $q->execute([$merchant['id'],$admin,$brand.' · '.$title.' [DEMO]',$campaignSlug,$description,$banner]);$campaignId=(int)$db->lastInsertId();$receipt['created_campaigns'][]=$campaignId;
        }
        // Reuse visible official demo coupons without editing them, plus one new clearly marked sample.
        $code='DEMO_SHOWCASE_'.strtoupper(str_replace('-','_',$slug));
        $q=$db->prepare('SELECT id FROM coupons WHERE merchant_id=? AND code_normalized=?');$q->execute([$merchant['id'],$code]);$couponId=$q->fetchColumn();
        if (!$couponId) {
            $q=$db->prepare("INSERT INTO coupons(merchant_id,category_id,submitted_by,source,title,code,description,terms,discount_type,discount_value,min_order_amount,max_discount_amount,usage_channel,starts_at,ends_at,moderation_status,publication_status,reviewed_by,reviewed_at,review_note) VALUES(?,?,?,'OFFICIAL',?,?,?,?,?,?,?,?,?,DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 75 DAY),'APPROVED','ENABLED',?,UTC_TIMESTAMP(),?)");
            $q->execute([$merchant['id'],$category,$couponAuthor,'[DEMO] '.$brand.' · '.($type==='PERCENTAGE' ? 'Giảm '.$value.'%' : 'Giảm '.number_format($value,0,',','.').'đ'),$code,$description,'[DEMO] Mã thử nghiệm CouponHub, không phải ưu đãi do thương hiệu phát hành và không dùng để thanh toán. Đơn tối thiểu '.number_format($minimum,0,',','.').'đ. '.($maximum ? 'Giảm tối đa '.number_format($maximum,0,',','.').'đ. ' : '').'Không quy đổi tiền mặt; chỉ áp dụng trong bản trình diễn.',$type,$value,$minimum,$maximum,$channel,$admin,'[DEMO] Dữ liệu trình diễn UI, không phải xác nhận thương hiệu.']);
            $couponId=(int)$db->lastInsertId();$receipt['created_coupons'][]=$couponId;
        }
        $q=$db->prepare("SELECT id FROM v_coupon_status WHERE merchant_id=? AND source='OFFICIAL' AND effective_status='ACTIVE' AND title LIKE '[DEMO]%' AND starts_at<(SELECT ends_at FROM campaigns WHERE id=?) AND ends_at>(SELECT starts_at FROM campaigns WHERE id=?)");$q->execute([$merchant['id'],$campaignId,$campaignId]);
        foreach ($q->fetchAll() as $coupon) {
            $attach=$db->prepare('INSERT INTO campaign_coupons(campaign_id,coupon_id,added_by) SELECT ?,?,? WHERE NOT EXISTS(SELECT 1 FROM campaign_coupons WHERE campaign_id=? AND coupon_id=?)');$attach->execute([$campaignId,$coupon['id'],$admin,$campaignId,$coupon['id']]);$receipt['linked_coupons']+=$attach->rowCount();
        }
    }
    $db->commit();echo json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)."\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();throw $e;
} finally { $db->query("SELECT RELEASE_LOCK('couponhub_ui_showcase_v1')"); }

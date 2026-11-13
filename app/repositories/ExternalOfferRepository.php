<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\{Database, HttpError, Pagination};

/** Provider sync never updates public coupons or clears a moderation decision. */
final class ExternalOfferRepository
{
    use AdminWriteGuard;
    public function __construct(private Database $db) {}

    public function ready(): bool
    {
        return (bool)$this->db->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='external_offers'")->fetchColumn();
    }
    public function page(int $page=1): array
    {
        $total=(int)$this->db->query('SELECT COUNT(*) FROM external_offers')->fetchColumn();
        $pagination = Pagination::fromTotal($total, $page, 20);
        $page = $pagination['page'];
        $pages = $pagination['pages'];
        $s=$this->db->prepare('SELECT id,provider_id,code,payload,state,coupon_id,fetched_at,fingerprint,imported_fingerprint FROM external_offers ORDER BY fetched_at DESC,id DESC LIMIT 20 OFFSET ?');
        $s->execute([$pagination['offset']]);
        return ['items'=>$s->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages];
    }
    public function find(int $id): ?array
    {
        $s=$this->db->prepare('SELECT * FROM external_offers WHERE id=?');$s->execute([$id]);return $s->fetch() ?: null;
    }
    // Upsert payload và fingerprint; giữ nguyên quyết định nhập/bỏ qua và coupon đã liên kết.
    public function stage(int $actor,array $offers): int
    {
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actor);
            $s=$this->db->prepare("INSERT INTO external_offers(provider,external_key,provider_id,code,payload,fingerprint,fetched_at) VALUES('accesstrade',?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE payload=VALUES(payload),fingerprint=VALUES(fingerprint),fetched_at=UTC_TIMESTAMP()");
            foreach ($offers as $offer) {
                $payload=json_encode($offer['payload'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
                $s->execute([$offer['external_key'],$offer['provider_id'],$offer['code'],$payload,hash('sha256',$payload)]);
            }
            $this->audit($actor,null,'EXTERNAL_SYNC','ACCESSTRADE: '.count($offers).' bản ghi được cập nhật trong hàng chờ.');
            $this->db->commit();return count($offers);
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    // Chỉ bỏ qua nguồn còn chờ, không mở lại nguồn đã xử lý khi đồng bộ lần sau.
    public function dismiss(int $actor,int $id): void
    {
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actor);
            $s=$this->db->prepare("UPDATE external_offers SET state='DISMISSED' WHERE id=? AND state='PENDING' AND coupon_id IS NULL");$s->execute([$id]);
            if ($s->rowCount()!==1) throw new HttpError(409,'Bản ghi đã được xử lý hoặc không tồn tại.');
            $this->audit($actor,null,'EXTERNAL_DISMISS','Bỏ qua nguồn #'.$id);
            $this->db->commit();
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    // Khóa nguồn, so fingerprint và mã trùng rồi tạo coupon chờ duyệt trong cùng transaction.
    public function import(int $actor,int $id,array $data,string $expectedFingerprint): int
    {
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actor);
            $s=$this->db->prepare('SELECT state,coupon_id,payload,fingerprint FROM external_offers WHERE id=? FOR UPDATE');$s->execute([$id]);$source=$s->fetch();
            if (!$source || $source['state']!=='PENDING' || $source['coupon_id']!==null) throw new HttpError(409,'Nguồn đã được nhập hoặc bỏ qua; không thể nhập lại.');
            if(!preg_match('/\A[a-f0-9]{64}\z/D',$expectedFingerprint) || !hash_equals($source['fingerprint'],$expectedFingerprint)) throw new HttpError(409,'Nguồn đã thay đổi trong lúc bạn đối chiếu. Tải lại trang và kiểm tra thông tin mới trước khi nhập.');
            $s=$this->db->prepare("SELECT id FROM merchants WHERE id=? AND status='APPROVED' AND deleted_at IS NULL FOR UPDATE");$s->execute([$data['merchant_id']]);
            if (!$s->fetch()) throw new HttpError(422,'Chọn cửa hàng đang được duyệt.');
            $s=$this->db->prepare('SELECT id FROM categories WHERE id=? AND is_active=1 LOCK IN SHARE MODE');$s->execute([$data['category_id']]);
            if (!$s->fetch()) throw new HttpError(422,'Chọn danh mục đang hoạt động.');
            if ($data['code']!==null) {
                $s=$this->db->prepare('SELECT id FROM coupons WHERE merchant_id=? AND code=? AND starts_at<? AND ends_at>? LIMIT 1');
                $s->execute([$data['merchant_id'],$data['code'],$data['ends_at_utc'],$data['starts_at_utc']]);
                if ($s->fetch()) throw new HttpError(409,'Mã đã có trong khoảng hiệu lực này, kể cả mã đã thu hồi. Không tạo bản sao.');
            }
            $payload=json_decode($source['payload'],true,64,JSON_THROW_ON_ERROR);
            $description="Nguồn: ACCESSTRADE. Thông tin do quản trị viên đối chiếu trước khi duyệt.\n".($data['description'] ?? '');
            if (!empty($payload['source_url'])) $description.="\nTham khảo: ".$payload['source_url'];
            $s=$this->db->prepare("INSERT INTO coupons(merchant_id,category_id,submitted_by,source,title,code,description,terms,discount_type,discount_value,cashback_unit,min_order_amount,max_discount_amount,usage_channel,promotion_url,starts_at,ends_at,moderation_status,publication_status) VALUES(?,?,?,'COMMUNITY',?,?,?,?,?,?,?,?,?,?,?,?,?,'PENDING','ENABLED')");
            $s->execute([$data['merchant_id'],$data['category_id'],$actor,$data['title'],$data['code'],$description,$data['terms'],$data['discount_type'],$data['discount_value'],$data['cashback_unit'],$data['min_order_amount'],$data['max_discount_amount'],$data['usage_channel'],$data['promotion_url'],$data['starts_at_utc'],$data['ends_at_utc']]);
            $coupon=(int)$this->db->lastInsertId();
            $this->db->prepare("UPDATE external_offers SET state='IMPORTED',coupon_id=?,imported_at=UTC_TIMESTAMP(),imported_fingerprint=fingerprint WHERE id=?")->execute([$coupon,$id]);
            $this->audit($actor,$coupon,'EXTERNAL_IMPORT','ACCESSTRADE #'.$id.' → coupon chờ duyệt.');
            $this->db->commit();return $coupon;
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    private function audit(int $actor,?int $coupon,string $action,string $message): void
    {
        $this->db->prepare('INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(?,?,?,?)')->execute([$actor,$coupon,$action,$message]);
    }
}

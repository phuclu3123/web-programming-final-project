<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use RuntimeException;
use Throwable;

/** Independent rights per (user_id, merchant_id), requests preserved for audit. */
final class MerchantAccessRepository implements \App\Contracts\MerchantAccessRepositoryInterface
{
    public function __construct(private Database $db) {}

    public function requests(): array
    {
        // Chỉ cảnh báo trên đơn mới PENDING nếu bằng chứng đã được người dùng
        // KHÁC nộp cho cùng Shop ở một đơn CŨ HƠN, chưa bị từ chối/hủy.
        // ID AUTO_INCREMENT là thứ tự nộp đơn ổn định; không dựa vào thứ tự
        // hiển thị (PENDING luôn được liệt kê trước APPROVED).
        $sql = "SELECT r.*, u.full_name, u.email, m.name AS merchant_name, m.status AS shop_status, " .
            "(SELECT COUNT(DISTINCT previous.user_id) FROM merchant_requests previous " .
            "WHERE r.status = 'PENDING' " .
            "AND previous.id < r.id " .
            "AND previous.merchant_id = r.merchant_id " .
            "AND previous.user_id <> r.user_id " .
            "AND previous.status IN ('PENDING','APPROVED') " .
            "AND NULLIF(TRIM(r.evidence_reference), '') IS NOT NULL " .
            "AND previous.evidence_reference IS NOT NULL " .
            "AND TRIM(previous.evidence_reference) = TRIM(r.evidence_reference)) AS duplicate_evidence_users " .
            "FROM merchant_requests r " .
            "JOIN users u ON u.id = r.user_id " .
            "JOIN merchants m ON m.id = r.merchant_id " .
            "ORDER BY FIELD(r.status,'PENDING','APPROVED','REJECTED','CANCELLED'), " .
            "r.created_at DESC, r.id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function memberships(): array
    {
        return $this->db->query("SELECT mm.*, u.full_name,u.email,m.name AS merchant_name,m.status AS shop_status FROM merchant_memberships mm JOIN users u ON u.id=mm.user_id JOIN merchants m ON m.id=mm.merchant_id ORDER BY mm.updated_at DESC,mm.user_id,mm.merchant_id")->fetchAll();
    }

    /** This must never downgrade User access to any other merchant. */
    public function review(int $adminId,int $requestId,string $action,?string $note,bool $duplicateConfirmed = false): void
    {
        if(!in_array($action,['APPROVE','REJECT'],true)) throw new RuntimeException('Hành động không hợp lệ.');
        if($action==='APPROVE' && trim((string)$note)==='')throw new RuntimeException('Vui lòng ghi kết quả xác minh quyền đại diện trước khi duyệt.');
        $this->db->beginTransaction();
        try {
            $q=$this->db->prepare("SELECT * FROM merchant_requests WHERE id=? FOR UPDATE");$q->execute([$requestId]);$r=$q->fetch();
            if(!is_array($r) || $r['status']!=='PENDING')throw new RuntimeException('Yêu cầu không còn ở trạng thái chờ duyệt.');
            $userCheck=$this->db->prepare("SELECT status FROM users WHERE id=? AND deleted_at IS NULL FOR UPDATE");
            $userCheck->execute([(int)$r['user_id']]);
            if($userCheck->fetchColumn()!=='ACTIVE')throw new RuntimeException('Tài khoản đăng ký hiện không hoạt động.');
            $shop=$this->db->prepare("SELECT id,status,owner_user_id FROM merchants WHERE id=? AND deleted_at IS NULL FOR UPDATE");
            $shop->execute([(int)$r['merchant_id']]);$merchant=$shop->fetch();
            if(!is_array($merchant) || in_array($merchant['status'],['LOCKED','REJECTED'],true)) throw new RuntimeException('Shop không thể cấp quyền.');

            // Verify duplicate evidence AGAIN on the server, inside this transaction.
            // A previously APPROVED/PENDING request from another user for the same shop
            // requires a second explicit approval. Other shops and REJECTED/CANCELLED
            // applications do not trigger this safeguard.
            if ($action === 'APPROVE' && trim((string)($r['evidence_reference'] ?? '')) !== '') {
                $checkEvidence = $this->db->prepare(
                    "SELECT COUNT(DISTINCT previous.user_id) FROM merchant_requests previous " .
                    "WHERE previous.id < ? AND previous.merchant_id = ? " .
                    "AND previous.user_id <> ? " .
                    "AND previous.status IN ('PENDING','APPROVED') " .
                    "AND previous.evidence_reference IS NOT NULL " .
                    "AND TRIM(previous.evidence_reference) = TRIM(?)"
                );
                $checkEvidence->execute([
                    (int)$r['id'], (int)$r['merchant_id'],
                    (int)$r['user_id'], (string)$r['evidence_reference'],
                ]);
                if ((int)$checkEvidence->fetchColumn() > 0 && !$duplicateConfirmed) {
                    throw new RuntimeException('Bằng chứng trùng với đơn của User khác. Cần xác nhận lần hai trước khi duyệt.');
                }
            }
            if($action==='APPROVE'){
                if($merchant['status']==='PENDING' || $merchant['status']==='UNCLAIMED'){
                    // Leave legacy primary owner intact if already set. Existing approved shops can have many managers.
                    $stmt=$this->db->prepare("UPDATE merchants SET status='APPROVED',owner_user_id=COALESCE(owner_user_id,?),reviewed_by=?,reviewed_at=UTC_TIMESTAMP() WHERE id=?");
                    $stmt->execute([(int)$r['user_id'],$adminId,(int)$r['merchant_id']]);
                }
                $stmt=$this->db->prepare("INSERT INTO merchant_memberships(user_id,merchant_id,status,granted_by,granted_at,revoked_by,revoked_at,revoke_note) VALUES(?,?,'ACTIVE',?,UTC_TIMESTAMP(),NULL,NULL,NULL) ON DUPLICATE KEY UPDATE status='ACTIVE',granted_by=VALUES(granted_by),granted_at=UTC_TIMESTAMP(),revoked_by=NULL,revoked_at=NULL,revoke_note=NULL");
                $stmt->execute([(int)$r['user_id'],(int)$r['merchant_id'],$adminId]);
                // Once manager, existing interactions must no longer help quality/reward.
                $this->db->prepare("UPDATE favorites f JOIN coupons c ON c.id=f.coupon_id SET f.is_eligible_for_rewards=0 WHERE f.user_id=? AND c.merchant_id=?")->execute([(int)$r['user_id'],(int)$r['merchant_id']]);
                $this->db->prepare("UPDATE votes v JOIN coupons c ON c.id=v.coupon_id SET v.is_eligible_for_trust=0 WHERE v.user_id=? AND c.merchant_id=?")->execute([(int)$r['user_id'],(int)$r['merchant_id']]);
                $this->db->prepare("UPDATE users SET role='MERCHANT' WHERE id=? AND role='USER'")->execute([(int)$r['user_id']]);
            }else if(in_array($r['request_type'],['CREATE','LEGACY'],true) && $merchant['status']==='PENDING'){
                $this->db->prepare("UPDATE merchants SET status='REJECTED',reviewed_by=?,reviewed_at=UTC_TIMESTAMP(),review_note=? WHERE id=?")->execute([$adminId,$note,(int)$r['merchant_id']]);
            }
            $stmt=$this->db->prepare("UPDATE merchant_requests SET status=?,reviewed_by=?,reviewed_at=UTC_TIMESTAMP(),review_note=? WHERE id=?");
            $stmt->execute([$action==='APPROVE'?'APPROVED':'REJECTED',$adminId,$note,$requestId]);
            $this->db->commit();
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function revoke(int $adminId,int $userId,int $merchantId,string $note): void
    {
        if(trim($note)==='')throw new RuntimeException('Cần ghi lý do thu hồi.');
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare("SELECT status FROM merchant_memberships WHERE user_id=? AND merchant_id=? FOR UPDATE");$s->execute([$userId,$merchantId]);
            if($s->fetchColumn()!=='ACTIVE')throw new RuntimeException('Quyền này không còn hiệu lực.');
            $this->db->prepare("UPDATE merchant_memberships SET status='REVOKED',revoked_by=?,revoked_at=UTC_TIMESTAMP(),revoke_note=? WHERE user_id=? AND merchant_id=?")->execute([$adminId,$note,$userId,$merchantId]);
            // Never reactivate historical favorites or votes after revocation.
            $check=$this->db->prepare("SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id WHERE mm.user_id=? AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL LIMIT 1");
            $check->execute([$userId]);
            if(!$check->fetchColumn())$this->db->prepare("UPDATE users SET role='USER' WHERE id=? AND role='MERCHANT'")->execute([$userId]);
            $this->db->commit();
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
}

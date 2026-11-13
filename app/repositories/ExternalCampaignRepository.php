<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\{Database,HttpError,Pagination};

// Nguồn chiến dịch có hàng chờ riêng, không tự tạo shop hay coupon Official.
final class ExternalCampaignRepository
{
    use AdminWriteGuard;
    public function __construct(private Database $db) {}
    public function ready(): bool
    {
        return (bool)$this->db->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='external_campaigns'")->fetchColumn();
    }
    public function page(int $page=1,string $state=''): array
    {
        if(!in_array($state,['','PENDING','APPROVED','DISMISSED'],true))throw new HttpError(422,'Trạng thái nguồn không hợp lệ.');
        $where=$state!==''?' WHERE state=?':'';$params=$state!==''?[$state]:[];
        $q=$this->db->prepare('SELECT COUNT(*) FROM external_campaigns'.$where);$q->execute($params);$total=(int)$q->fetchColumn();
        $pagination = Pagination::fromTotal($total, $page, 50);
        $page = $pagination['page'];
        $pages = $pagination['pages'];
        $q=$this->db->prepare('SELECT * FROM external_campaigns'.$where.' ORDER BY fetched_at DESC,id DESC LIMIT 50 OFFSET ?');$q->execute([...$params, $pagination['offset']]);
        return ['items'=>$q->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages];
    }
    public function stage(int $actor,array $campaigns): int
    {
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actor);
            $q=$this->db->prepare("INSERT INTO external_campaigns(provider_id,payload,fingerprint,source_running,starts_at,ends_at,fetched_at) VALUES(?,?,?,?,?,?,UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE payload=VALUES(payload),fingerprint=VALUES(fingerprint),source_running=VALUES(source_running),starts_at=VALUES(starts_at),ends_at=VALUES(ends_at),fetched_at=UTC_TIMESTAMP()");
            foreach($campaigns as $campaign) {
                $p=$campaign['payload'];$payload=json_encode($p,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
                $q->execute([$campaign['provider_id'],$payload,hash('sha256',$payload),(int)$p['running'],$p['starts_at'],$p['ends_at']]);
            }
            $this->audit($actor,'EXTERNAL_CAMPAIGN_SYNC',['received'=>count($campaigns)]);
            $this->db->commit();return count($campaigns);
        }catch(\Throwable $error){if($this->db->inTransaction())$this->db->rollBack();throw $error;}
    }
    // Mỗi mục có transaction riêng; nguồn đổi phiên bản phải được xem và chọn lại.
    public function moderate(int $actor,int $id,string $fingerprint,string $action,string $note=''): void
    {
        if(!in_array($action,['APPROVE','DISMISS'],true) || mb_strlen($note)>1000)throw new HttpError(422,'Quyết định kiểm duyệt không hợp lệ.');
        $this->db->beginTransaction();
        try {
            $this->requireActiveAdmin($actor);
            $q=$this->db->prepare('SELECT * FROM external_campaigns WHERE id=? FOR UPDATE');$q->execute([$id]);$row=$q->fetch();
            if(!$row || !hash_equals($row['fingerprint'],$fingerprint))throw new HttpError(409,'Nguồn đã thay đổi. Tải lại danh sách trước khi duyệt.');
            if($row['state']==='DISMISSED')throw new HttpError(409,'Chiến dịch đã bị bỏ qua; đồng bộ không mở lại quyết định này.');
            if($action==='APPROVE'){
                if($row['state']==='APPROVED' && $row['approved_fingerprint']===$fingerprint)throw new HttpError(409,'Chiến dịch đã được duyệt.');
                $p=json_decode($row['payload'],true,64,JSON_THROW_ON_ERROR);
                if(!$row['source_running'] || empty($p['url']) || ($row['ends_at']!==null && $row['ends_at']<=gmdate('Y-m-d H:i:s')))throw new HttpError(422,'Nguồn đã ngừng, hết hạn hoặc chưa có liên kết HTTPS hợp lệ.');
                $this->db->prepare("UPDATE external_campaigns SET state='APPROVED',approved_fingerprint=fingerprint,reviewed_by=?,reviewed_at=UTC_TIMESTAMP(),review_note=? WHERE id=?")->execute([$actor,$note,$id]);
            }else $this->db->prepare("UPDATE external_campaigns SET state='DISMISSED',reviewed_by=?,reviewed_at=UTC_TIMESTAMP(),review_note=? WHERE id=?")->execute([$actor,$note,$id]);
            $this->audit($actor,'EXTERNAL_CAMPAIGN_'.$action,['external_campaign_id'=>$id,'provider_id'=>$row['provider_id'],'fingerprint'=>$fingerprint,'note'=>$note]);
            $this->db->commit();
        }catch(\Throwable $error){if($this->db->inTransaction())$this->db->rollBack();throw $error;}
    }
    public function published(int $page=1,string $phase='ALL'): array
    {
        if(!$this->ready())return ['items'=>[],'total'=>0,'page'=>1,'pages'=>1];
        // Thay đổi từ provider không được tự vượt bước duyệt, kể cả khi trước đó đã công khai.
        $where="state='APPROVED' AND fingerprint=approved_fingerprint AND source_running=1 AND (ends_at IS NULL OR ends_at>UTC_TIMESTAMP())";
        if($phase==='ACTIVE')$where.=' AND (starts_at IS NULL OR starts_at<=UTC_TIMESTAMP())';
        elseif($phase==='UPCOMING')$where.=' AND starts_at>UTC_TIMESTAMP()';
        $total=(int)$this->db->query('SELECT COUNT(*) FROM external_campaigns WHERE '.$where)->fetchColumn();
        $pagination = Pagination::fromTotal($total, $page, 12);
        $page = $pagination['page'];
        $pages = $pagination['pages'];
        $q=$this->db->prepare('SELECT id,payload,starts_at,ends_at FROM external_campaigns WHERE '.$where.' ORDER BY reviewed_at DESC,id DESC LIMIT 12 OFFSET ?');$q->execute([$pagination['offset']]);
        return ['items'=>$q->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages];
    }
    private function audit(int $actor,string $action,array $data): void
    {
        $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')->execute([$actor,$action,json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
    }
}

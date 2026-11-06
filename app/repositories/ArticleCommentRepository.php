<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\{Database,HttpError};

final class ArticleCommentRepository
{
    use AdminWriteGuard;
    public function __construct(private Database $db) {}

    private function published(int $id, bool $lock = false): void
    {
        $q=$this->db->prepare("SELECT id FROM articles WHERE id=? AND status='PUBLISHED' AND published_at<=UTC_TIMESTAMP()".($lock?' LOCK IN SHARE MODE':''));
        $q->execute([$id]);
        if (!$q->fetch()) throw new HttpError(404,'Bài viết không tồn tại hoặc chưa được xuất bản.');
    }

    public function page(int $articleId, int $viewerId = 0, int $before = 0): array
    {
        $this->published($articleId);
        $q=$this->db->prepare("SELECT c.id,c.user_id,c.body,c.created_at,CASE WHEN u.deleted_at IS NULL THEN u.full_name ELSE 'Thành viên' END AS author_name FROM article_comments c JOIN users u ON u.id=c.user_id WHERE c.article_id=? AND c.status='VISIBLE' AND (?=0 OR c.id<?) ORDER BY c.id DESC LIMIT 21");
        $q->execute([$articleId,$before,$before]);$rows=$q->fetchAll();$more=count($rows)>20;$rows=array_slice($rows,0,20);
        foreach ($rows as &$row) {
            $row['id']=(int)$row['id'];$row['can_delete']=$viewerId>0 && (int)$row['user_id']===$viewerId;
            unset($row['user_id']);
            $row['created_at_display']=format_db_datetime($row['created_at']);
        }
        unset($row);
        $q=$this->db->prepare("SELECT COUNT(*) FROM article_comments WHERE article_id=? AND status='VISIBLE'");$q->execute([$articleId]);
        return ['items'=>$rows,'total'=>(int)$q->fetchColumn(),'next_before'=>$more?(int)end($rows)['id']:null];
    }

    public function create(int $actorId, int $articleId, string $body, string $requestId): array
    {
        $this->db->beginTransaction();
        try {
            // Serialize writes by this actor, including concurrent retries of one request ID.
            $actor=$this->db->prepare("SELECT id FROM users WHERE id=? AND status='ACTIVE' AND deleted_at IS NULL AND role IN ('USER','MERCHANT','ADMIN') FOR UPDATE");$actor->execute([$actorId]);
            if (!$actor->fetch()) throw new HttpError(403,'Tài khoản không được phép bình luận.');
            $this->published($articleId,true);
            $q=$this->db->prepare('SELECT id,article_id,body,status FROM article_comments WHERE user_id=? AND request_id=?');$q->execute([$actorId,$requestId]);$old=$q->fetch();
            if ($old && ((int)$old['article_id']!==$articleId || $old['body']!==$body || $old['status']!=='VISIBLE')) throw new HttpError(409,'Lượt gửi này đã được xử lý. Tải lại trang trước khi gửi nội dung mới.');
            if (!$old) {
                $q=$this->db->prepare('INSERT INTO article_comments(article_id,user_id,body,request_id) VALUES(?,?,?,?)');$q->execute([$articleId,$actorId,$body,$requestId]);$id=(int)$this->db->lastInsertId();
                $this->audit($actorId,'ARTICLE_COMMENT_CREATE',$id);
            } else $id=(int)$old['id'];
            $this->db->commit();return ['id'=>$id,'created'=>!$old];
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function change(int $actorId, int $id, string $status, bool $admin): int
    {
        if(($admin && !in_array($status,['VISIBLE','HIDDEN'],true)) || (!$admin && $status!=='DELETED'))throw new HttpError(422,'Trạng thái bình luận không hợp lệ.');
        $this->db->beginTransaction();
        try {
            if($admin) $this->requireActiveAdmin($actorId);
            else {
                $q=$this->db->prepare("SELECT id FROM users WHERE id=? AND status='ACTIVE' AND deleted_at IS NULL FOR UPDATE");$q->execute([$actorId]);
                if(!$q->fetch())throw new HttpError(403,'Tài khoản không được phép thao tác.');
            }
            $q=$this->db->prepare('SELECT article_id,user_id,status FROM article_comments WHERE id=? FOR UPDATE');$q->execute([$id]);$row=$q->fetch();
            if(!$row)throw new HttpError(404,'Không tìm thấy bình luận.');
            if(!$admin && (int)$row['user_id']!==$actorId)throw new HttpError(403,'Bạn chỉ được xóa bình luận của mình.');
            if($row['status']==='DELETED' && $status!=='DELETED')throw new HttpError(409,'Bình luận đã xóa không thể khôi phục.');
            if($row['status']!==$status){
                $this->db->prepare('UPDATE article_comments SET status=? WHERE id=?')->execute([$status,$id]);$this->audit($actorId,'ARTICLE_COMMENT_'.$status,$id);
            }
            $this->db->commit();return (int)$row['article_id'];
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function moderation(int $page): array
    {
        $offset=(max(1,min(10000,$page))-1)*30;
        return $this->db->query("SELECT c.id,c.body,c.status,c.created_at,u.full_name AS author_name,a.id AS article_id,a.title,a.slug FROM article_comments c JOIN articles a ON a.id=c.article_id JOIN users u ON u.id=c.user_id WHERE c.status<>'DELETED' ORDER BY c.id DESC LIMIT 31 OFFSET ".$offset)->fetchAll();
    }
    private function audit(int $actorId,string $action,int $id):void
    {
        $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')->execute([$actorId,$action,json_encode(['article_comment_id'=>$id],JSON_THROW_ON_ERROR)]);
    }
}

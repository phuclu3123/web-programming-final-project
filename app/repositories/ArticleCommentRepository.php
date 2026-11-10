<?php
declare(strict_types=1);
namespace App\Repositories;

use App\Core\{Database,HttpError};

// Lưu bình luận bền trong MySQL; khóa actor và request ID để chống ghi trùng.
final class ArticleCommentRepository
{
    use AdminWriteGuard;
    public function __construct(private Database $db) {}

    // Lấy slug cho URL quay lại sau POST; quyền ghi đã được kiểm tra ở thao tác bình luận.
    public function articleSlug(int $articleId): string
    {
        $query = $this->db->prepare('SELECT slug FROM articles WHERE id=?');
        $query->execute([$articleId]);
        return (string)$query->fetchColumn();
    }

    private function published(int $id, bool $lock = false): void
    {
        $q=$this->db->prepare("SELECT id FROM articles WHERE id=? AND status='PUBLISHED' AND published_at<=UTC_TIMESTAMP()".($lock?' LOCK IN SHARE MODE':''));
        $q->execute([$id]);
        if (!$q->fetch()) throw new HttpError(404,'Bài viết không tồn tại hoặc chưa được xuất bản.');
    }

    // Đọc theo cursor ID để tải thêm bình luận ổn định; không trả user ID nội bộ ra JSON.
    public function page(int $articleId,int $viewerId=0,int $before=0,int $focus=0):array
    {
        $this->published($articleId);$focusRoot=0;
        if($focus>0){
            $q=$this->db->prepare("SELECT COALESCE(root_comment_id,id) FROM comments WHERE id=? AND article_id=? AND status='VISIBLE'");
            $q->execute([$focus,$articleId]);$focusRoot=(int)$q->fetchColumn();
            if(!$focusRoot)throw new HttpError(404,'Bình luận không còn hiển thị.');
            $before=$focusRoot+1;
        }
        $where="c.article_id=? AND c.root_comment_id IS NULL AND (?=0 OR c.id<?) AND (c.status='VISIBLE' OR EXISTS(SELECT 1 FROM comments r WHERE r.root_comment_id=c.id AND r.status='VISIBLE'))";
        $rows=$this->readRows($where,[$articleId,$before,$before],$viewerId,'DESC');
        $more=count($rows)>20;$rows=array_slice($rows,0,20);
        foreach($rows as &$row){
            $after=0;
            if($focusRoot===$row['id'] && $focus!==$focusRoot){
                $q=$this->db->prepare("SELECT COUNT(*) FROM comments WHERE article_id=? AND root_comment_id=? AND status='VISIBLE' AND id<?");$q->execute([$articleId,$row['id'],$focus]);
                if((int)$q->fetchColumn()>=20)$after=$focus-1;
            }
            $replies=$this->replyPage($articleId,$row['id'],$viewerId,$after);
            $row['replies']=$replies['items'];$row['reply_count']=$replies['total'];
            $row['next_reply_after']=$replies['next_after'];$row['replies_from_start']=$after===0;
        }
        unset($row);
        return ['items'=>$rows,'total'=>$this->total($articleId),'next_before'=>$more?(int)end($rows)['id']:null];
    }

    public function total(int $articleId):int
    {
        $q=$this->db->prepare("SELECT COUNT(*) FROM comments WHERE article_id=? AND status='VISIBLE'");$q->execute([$articleId]);return (int)$q->fetchColumn();
    }

    public function replies(int $articleId,int $rootId,int $viewerId=0,int $after=0):array
    {
        $this->published($articleId);
        $q=$this->db->prepare("SELECT id FROM comments c WHERE id=? AND article_id=? AND root_comment_id IS NULL AND (status='VISIBLE' OR EXISTS(SELECT 1 FROM comments r WHERE r.root_comment_id=c.id AND r.status='VISIBLE'))");
        $q->execute([$rootId,$articleId]);if(!$q->fetch())throw new HttpError(404,'Cuộc trao đổi không còn hiển thị.');
        return $this->replyPage($articleId,$rootId,$viewerId,$after);
    }

    private function replyPage(int $articleId,int $rootId,int $viewerId,int $after):array
    {
        $rows=$this->readRows("c.article_id=? AND c.root_comment_id=? AND c.status='VISIBLE' AND c.id>?",[$articleId,$rootId,$after],$viewerId,'ASC');
        $more=count($rows)>20;$rows=array_slice($rows,0,20);
        $q=$this->db->prepare("SELECT COUNT(*) FROM comments WHERE article_id=? AND root_comment_id=? AND status='VISIBLE'");$q->execute([$articleId,$rootId]);
        return ['items'=>$rows,'total'=>(int)$q->fetchColumn(),'next_after'=>$more?(int)end($rows)['id']:null];
    }

    // Gốc đã ẩn/xóa chỉ giữ chỗ cho phản hồi; không lộ tên và nội dung cũ.
    private function readRows(string $where,array $params,int $viewerId,string $direction):array
    {
        $q=$this->db->prepare("SELECT c.id,c.user_id,c.parent_comment_id,c.root_comment_id,c.body,c.status,c.created_at,
            CASE WHEN u.deleted_at IS NULL THEN u.full_name ELSE 'Thành viên' END AS author_name,
            CASE WHEN u.deleted_at IS NULL AND u.status='ACTIVE' THEN u.role ELSE 'USER' END AS author_role,
            CASE WHEN p.status='VISIBLE' AND pu.deleted_at IS NULL THEN pu.full_name ELSE 'bình luận không còn hiển thị' END AS reply_to_name,
            (SELECT COUNT(*) FROM comment_reactions cr WHERE cr.comment_id=c.id) AS like_count,
            EXISTS(SELECT 1 FROM comment_reactions cr WHERE cr.comment_id=c.id AND cr.user_id=?) AS liked
            FROM comments c JOIN users u ON u.id=c.user_id LEFT JOIN comments p ON p.id=c.parent_comment_id LEFT JOIN users pu ON pu.id=p.user_id
            WHERE ".$where.' ORDER BY c.id '.$direction.' LIMIT 21');
        $q->execute([$viewerId,...$params]);$rows=$q->fetchAll();
        foreach($rows as &$row){
            $row=$this->withAuthorRole($row);$visible=$row['status']==='VISIBLE';
            $row['id']=(int)$row['id'];$row['parent_comment_id']=$row['parent_comment_id']===null?null:(int)$row['parent_comment_id'];
            $row['root_comment_id']=$row['root_comment_id']===null?null:(int)$row['root_comment_id'];
            $row['can_delete']=$visible && $viewerId>0 && (int)$row['user_id']===$viewerId;
            $row['can_reply']=$visible && $viewerId>0;$row['removed']=!$visible;
            $row['like_count']=$visible?(int)$row['like_count']:0;$row['liked']=$visible && (bool)$row['liked'];
            $row['created_at_display']=format_db_datetime($row['created_at']);
            if(!$visible){$row['body']='Bình luận không còn hiển thị.';$row['author_name']='';$row['author_role']='USER';$row['author_role_label']='';}
            unset($row['user_id'],$row['status']);
        }
        unset($row);return $rows;
    }

    // Khóa actor và request ID để cùng lượt gửi chỉ tạo một bình luận, kể cả khi thử lại.
    public function create(int $actorId, int $articleId, string $body, string $requestId, int $parentId=0): array
    {
        $this->db->beginTransaction();
        try {
            // Serialize writes by this actor, including concurrent retries of one request ID.
            $actor=$this->db->prepare("SELECT id FROM users WHERE id=? AND status='ACTIVE' AND deleted_at IS NULL AND role IN ('USER','MERCHANT','ADMIN') FOR UPDATE");$actor->execute([$actorId]);
            if (!$actor->fetch()) throw new HttpError(403,'Tài khoản không được phép bình luận.');
            $this->published($articleId,true);
            $q=$this->db->prepare('SELECT id,article_id,parent_comment_id,body,status FROM comments WHERE user_id=? AND request_id=?');$q->execute([$actorId,$requestId]);$old=$q->fetch();
            if ($old && ((int)$old['article_id']!==$articleId || (int)$old['parent_comment_id']!==$parentId || $old['body']!==$body || $old['status']!=='VISIBLE')) throw new HttpError(409,'Lượt gửi này đã được xử lý. Tải lại trang trước khi gửi nội dung mới.');
            if (!$old) {
                $rootId=null;
                if($parentId>0){
                    $parent=$this->lockThreadComment($parentId,$articleId);
                    if($parent['status']!=='VISIBLE')throw new HttpError(409,'Bình luận này đã bị ẩn hoặc xóa. Chọn bình luận khác để trả lời.');
                    $rootId=(int)($parent['root_comment_id']??$parent['id']);
                }
                $q=$this->db->prepare('INSERT INTO comments(article_id,user_id,body,request_id,parent_comment_id,root_comment_id) VALUES(?,?,?,?,?,?)');$q->execute([$articleId,$actorId,$body,$requestId,$parentId?:null,$rootId]);$id=(int)$this->db->lastInsertId();
                $this->audit($actorId,'ARTICLE_COMMENT_CREATE',$id);
                if($parentId>0 && (int)$parent['user_id']!==$actorId){
                    $this->notify((int)$parent['user_id'],$actorId,$id,$parentId,'ARTICLE_REPLY','article-reply-'.$id,true);
                }
            } else $id=(int)$old['id'];
            $this->db->commit();return ['id'=>$id,'created'=>!$old];
        } catch (\Throwable $e) {if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    // Khóa bình luận và kiểm tra owner/admin; không phục hồi bình luận đã xóa.
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
            $row=$this->lockThreadComment($id);
            if(!$admin && (int)$row['user_id']!==$actorId)throw new HttpError(403,'Bạn chỉ được xóa bình luận của mình.');
            if($row['status']==='DELETED' && $status!=='DELETED')throw new HttpError(409,'Bình luận đã xóa không thể khôi phục.');
            if($row['status']!==$status){
                $this->db->prepare('UPDATE comments SET status=? WHERE id=?')->execute([$status,$id]);$this->audit($actorId,'ARTICLE_COMMENT_'.$status,$id);
            }
            $this->db->commit();return (int)$row['article_id'];
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function moderation(int $page): array
    {
        $offset=(max(1,min(10000,$page))-1)*30;
        $rows=$this->db->query("SELECT c.id,c.body,c.status,c.parent_comment_id,c.root_comment_id,c.created_at,u.full_name AS author_name,CASE WHEN u.deleted_at IS NULL AND u.status='ACTIVE' THEN u.role ELSE 'USER' END AS author_role,a.id AS article_id,a.title,a.slug,(a.status='PUBLISHED' AND a.published_at<=UTC_TIMESTAMP()) AS article_published FROM comments c JOIN articles a ON a.id=c.article_id JOIN users u ON u.id=c.user_id WHERE c.article_id IS NOT NULL AND c.status<>'DELETED' ORDER BY c.id DESC LIMIT 31 OFFSET ".$offset)->fetchAll();
        return array_map(fn(array $row):array=>$this->withAuthorRole($row),$rows);
    }
    // Khóa gốc trước phản hồi để reply, react và kiểm duyệt dùng cùng thứ tự.
    private function lockThreadComment(int $id,int $articleId=0):array
    {
        $q=$this->db->prepare('SELECT id,article_id,root_comment_id FROM comments WHERE id=? AND article_id IS NOT NULL');$q->execute([$id]);$row=$q->fetch();
        if(!$row || ($articleId>0 && (int)$row['article_id']!==$articleId))throw new HttpError(404,'Không tìm thấy bình luận trong bài viết này.');
        $rootId=(int)($row['root_comment_id']??$row['id']);
        $q=$this->db->prepare('SELECT id FROM comments WHERE id=? AND article_id=? AND root_comment_id IS NULL FOR UPDATE');$q->execute([$rootId,(int)$row['article_id']]);
        if(!$q->fetch())throw new HttpError(409,'Luồng bình luận không hợp lệ.');
        $q=$this->db->prepare('SELECT id,article_id,user_id,status,root_comment_id FROM comments WHERE id=? FOR UPDATE');$q->execute([$id]);return $q->fetch();
    }

    // Gửi trạng thái mong muốn để retry không vô tình đảo thích thành bỏ thích.
    public function react(int $actorId,int $id,bool $liked):array
    {
        $this->db->beginTransaction();
        try{
            $q=$this->db->prepare("SELECT id FROM users WHERE id=? AND status='ACTIVE' AND deleted_at IS NULL AND role IN ('USER','MERCHANT','ADMIN') FOR UPDATE");$q->execute([$actorId]);
            if(!$q->fetch())throw new HttpError(403,'Tài khoản không được phép tương tác.');
            $q=$this->db->prepare('SELECT article_id FROM comments WHERE id=? AND article_id IS NOT NULL');$q->execute([$id]);$articleId=(int)$q->fetchColumn();
            if(!$articleId)throw new HttpError(404,'Không tìm thấy bình luận bài viết.');
            $this->published($articleId,true);$row=$this->lockThreadComment($id,$articleId);
            if($row['status']!=='VISIBLE')throw new HttpError(409,'Bình luận không còn hiển thị.');
            if($liked)$this->db->prepare('INSERT INTO comment_reactions(comment_id,user_id) VALUES(?,?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id)')->execute([$id,$actorId]);
            else $this->db->prepare('DELETE FROM comment_reactions WHERE comment_id=? AND user_id=?')->execute([$id,$actorId]);
            if((int)$row['user_id']!==$actorId)$this->notify((int)$row['user_id'],$actorId,$id,$id,'COMMENT_LIKE','comment-like-'.$id.'-'.$actorId,$liked);
            $q=$this->db->prepare('SELECT COUNT(*) FROM comment_reactions WHERE comment_id=?');$q->execute([$id]);$count=(int)$q->fetchColumn();
            $this->db->commit();return ['id'=>$id,'liked'=>$liked,'like_count'=>$count,'article_id'=>$articleId];
        }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    private function notify(int $recipient,int $actor,int $comment,int $subject,string $kind,string $key,bool $active):void
    {
        // Cùng transaction với reply/react; thích lại không tạo sự kiện mới hoặc reset đã đọc.
        if(!$active){$this->db->prepare('UPDATE notifications SET active=0 WHERE user_id=? AND event_key=?')->execute([$recipient,$key]);return;}
        $this->db->prepare('INSERT INTO notifications(user_id,event_key,actor_id,comment_id,subject_comment_id,kind) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE active=1')->execute([$recipient,$key,$actor,$comment,$subject,$kind]);
    }

    // Vai trò lấy từ tài khoản trong DB, không lấy từ nội dung hoặc dữ liệu gửi lên.
    private function withAuthorRole(array $row):array
    {
        $labels=['USER'=>'Thành viên','MERCHANT'=>'Đối tác','ADMIN'=>'Quản trị viên'];
        $role=$row['author_role']??'USER';
        $row['author_role']=isset($labels[$role])?$role:'USER';
        $row['author_role_label']=$labels[$row['author_role']];
        return $row;
    }
    private function audit(int $actorId,string $action,int $id):void
    {
        $this->db->prepare('INSERT INTO activity_logs(user_id,action,description) VALUES(?,?,?)')->execute([$actorId,$action,json_encode(['article_comment_id'=>$id],JSON_THROW_ON_ERROR)]);
    }
}

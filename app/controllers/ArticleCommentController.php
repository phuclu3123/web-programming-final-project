<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Auth,Controller,Database,HttpError,InteractionRateLimit,Request,Response,Session};
use App\Repositories\ArticleCommentRepository;

// Điều phối bình luận bài viết qua repository; giữ cùng quyền cho HTML và AJAX.
final class ArticleCommentController extends Controller
{
    private function repository():ArticleCommentRepository {return new ArticleCommentRepository(Database::connection());}
    private function id(mixed $value,bool $optional=false):int
    {
        if($optional && ($value==='' || $value===null))return 0;
        $id=is_scalar($value)?filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):false;
        if($id===false)throw new HttpError(422,'Mã định danh không hợp lệ.');return (int)$id;
    }
    public function index(Request $request,string $id):void
    {
        Response::json(['success'=>true,'data'=>$this->repository()->page($this->id($id),(int)(Auth::id()??0),$this->id($request->query('before',''),true),$this->id($request->query('comment',''),true))]);
    }
    public function replies(Request $request,string $id,string $rootId):void
    {
        Response::json(['success'=>true,'data'=>$this->repository()->replies($this->id($id),$this->id($rootId),(int)(Auth::id()??0),$this->id($request->query('after',''),true))]);
    }
    public function react(Request $request,string $id):void
    {
        $actor=Auth::requireRole(['USER','MERCHANT','ADMIN']);$liked=$request->input('liked');
        if(!in_array($liked,['0','1',0,1],true))throw new HttpError(422,'Trạng thái thích không hợp lệ.');
        $result=$this->repository()->react((int)$actor['id'],$this->id($id),(string)$liked==='1');
        if($request->acceptsHtml())Response::redirect(url('/articles/'.$this->slug($result['article_id'])).'?comment='.$result['id'].'#article-comment-'.$result['id'],303);
        unset($result['article_id']);Response::json(['success'=>true,'data'=>$result]);
    }
    public function store(Request $request,string $id):void
    {
        $actor=Auth::requireRole(['USER','MERCHANT','ADMIN']);
        $body=$request->input('body','');$key=$request->input('request_id','');
        if(!is_string($body) || !mb_check_encoding($body,'UTF-8'))throw new HttpError(422,'Nội dung bình luận không hợp lệ.');
        $body=trim(str_replace(["\r\n","\r"],"\n",$body));
        if($body==='' || !preg_match('/\S/u',$body) || mb_strlen($body)>2000 || preg_match('/[\x00-\x08\x0b\x0c\x0e-\x1f\x7f]/',$body))throw new HttpError(422,'Nhập bình luận từ 1 đến 2.000 ký tự.');
        if(!is_string($key)||!preg_match('/\A[a-f0-9]{32}\z/',$key))throw new HttpError(422,'Lượt gửi không hợp lệ. Vui lòng tải lại trang.');
        InteractionRateLimit::check('comment',(int)$actor['id']);
        $parent=$this->id($request->input('parent_comment_id',''),true);
        $articleId=$this->id($id);$result=$this->repository()->create((int)$actor['id'],$articleId,$body,$key,$parent);
        if($request->acceptsHtml()){
            Session::flash('success','Đã gửi bình luận.');Response::redirect(url('/articles/'.$this->slug($articleId)).'?comment='.$result['id'].'#article-comment-'.$result['id'],303);
        }
        Response::json(['success'=>true,'message'=>$parent?'Đã gửi trả lời.':'Đã gửi bình luận.','data'=>$this->repository()->page($articleId,(int)$actor['id'],0,$result['id']),'comment_id'=>$result['id']],$result['created']?201:200);
    }
    public function delete(Request $request,string $id):void
    {
        $actor=Auth::requireRole(['USER','MERCHANT','ADMIN']);
        $articleId=$this->repository()->change((int)$actor['id'],$this->id($id),'DELETED',false);
        if($request->acceptsHtml()){Session::flash('success','Đã xóa bình luận.');Response::redirect(url('/articles/'.$this->slug($articleId)).'#article-comments',303);}
        Response::json(['success'=>true,'message'=>'Đã xóa bình luận.','data'=>$this->repository()->page($articleId,(int)$actor['id'])]);
    }
    private function slug(int $articleId):string
    {
        return $this->repository()->articleSlug($articleId);
    }
    public function moderation(Request $request):void
    {
        Auth::requireRole(['ADMIN']);$page=$this->id($request->query('page','1'));
        $rows=$this->repository()->moderation($page);$more=count($rows)>30;
        $this->view('admin/articles/comments',['pageTitle'=>'Bình luận bài viết','comments'=>array_slice($rows,0,30),'page'=>$page,'more'=>$more]);
    }
    public function moderate(Request $request,string $id):void
    {
        $actor=Auth::requireRole(['ADMIN']);$status=$request->input('status','');
        if(!in_array($status,['VISIBLE','HIDDEN'],true))throw new HttpError(422,'Trạng thái bình luận không hợp lệ.');
        $this->repository()->change((int)$actor['id'],$this->id($id),$status,true);
        Session::flash('success','Đã cập nhật bình luận.');Response::redirect(url('/admin/article-comments'),303);
    }
}

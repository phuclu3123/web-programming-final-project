<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\ArticleRepository;

// Chọn bài đã xuất bản và dữ liệu trình bày trước khi render trang đọc.
final class ArticleController extends Controller
{
    public function guide(Request $request, string $slug): void
    {
        $article=(new ArticleRepository(Database::connection()))->findPublished($slug);
        if(!$article){$this->renderError(404,'Hướng dẫn chưa được xuất bản.');return;}
        \App\Core\Response::redirect(url('/articles/'.$article['slug']),302);
    }
    public function index(Request $request): void
    {
        $guideTopic = $request->query('topic', 'all');
        if (!in_array($guideTopic, ['all', 'dung-ma', 'tinh-toan', 'cong-dong'], true)) {
            $this->renderError(422, 'Chủ đề hướng dẫn không hợp lệ.');
            return;
        }
        $this->view('articles/journal', ['pageTitle' => 'Bài viết & hướng dẫn', 'guideTopic' => $guideTopic, 'articles' => (new ArticleRepository(Database::connection()))->published()]);
    }
    public function show(Request $request, string $slug): void
    {
        $article = (new ArticleRepository(Database::connection()))->findPublished($slug);
        if ($article === null) {
            $this->renderError(404, 'Bài viết không tồn tại hoặc chưa được xuất bản.');
            return;
        }
        $viewer=\App\Core\Auth::user();$before=$request->query('before','');
        if(!is_scalar($before) || ($before!=='' && filter_var($before,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false))throw new \App\Core\HttpError(422,'Trang bình luận không hợp lệ.');
        $focus=$request->query('comment','');
        $reply=$request->query('reply','');
        if($reply!=='' && filter_var($reply,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false)throw new \App\Core\HttpError(422,'Bình luận trả lời không hợp lệ.');
        if($reply!=='')$focus=$reply;
        if($focus!=='' && filter_var($focus,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false)throw new \App\Core\HttpError(422,'Bình luận không hợp lệ.');
        $comments=(new \App\Repositories\ArticleCommentRepository(Database::connection()))->page((int)$article['id'],(int)($viewer['id']??0),(int)$before,(int)$focus);
        $replyTarget=null;
        if($reply!=='' && $viewer)foreach($comments['items'] as $thread)foreach([$thread,...$thread['replies']] as $comment)if($comment['id']===(int)$reply && $comment['can_reply'])$replyTarget=$comment;
        $this->view('articles/show', ['pageTitle' => (string)$article['title'], 'article' => $article,'commentPage'=>$comments,'commentViewer'=>$viewer,'replyTarget'=>$replyTarget,'commentFocusId'=>(int)$focus]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\ArticleRepository;

final class ArticleController extends Controller
{
    public function guide(Request $request, string $slug): void
    {
        $guides=productGuides();
        if (!isset($guides[$slug])) {
            $this->renderError(404,'Hướng dẫn không tồn tại.');
            return;
        }
        $this->view('articles/guide',['pageTitle'=>$guides[$slug]['title'],'guide'=>$guides[$slug],'guideSlug'=>$slug,'guides'=>$guides]);
    }
    public function index(Request $request): void
    {
        $guideTopic=$request->query('topic','all');
        if (!in_array($guideTopic,['all','dung-ma','tinh-toan','cong-dong'],true)) {
            $this->renderError(422,'Chủ đề hướng dẫn không hợp lệ.');
            return;
        }
        $this->view('articles/journal', ['pageTitle' => 'Bài viết & hướng dẫn', 'guideTopic'=>$guideTopic, 'articles' => (new ArticleRepository(Database::connection()))->published()]);
    }
    public function show(Request $request, string $slug): void
    {
        $article = (new ArticleRepository(Database::connection()))->findPublished($slug);
        if ($article === null) {
            $this->renderError(404, 'Bài viết không tồn tại hoặc chưa được xuất bản.');
            return;
        }
        $this->view('articles/show', ['pageTitle' => (string)$article['title'], 'article' => $article]);
    }
}

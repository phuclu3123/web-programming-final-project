<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\ArticleRepository;

final class ArticleController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('articles/index', ['pageTitle' => 'Bài viết & hướng dẫn', 'articles' => (new ArticleRepository(Database::connection()))->published()]);
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

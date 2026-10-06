<?php

namespace App\Controllers;

use App\Core\{Controller, Database as DB, HttpException};

final class ArticleController extends Controller
{
    public function index(): void
    {
        $articles = DB::all("SELECT a.*,u.full_name FROM {{articles}} a JOIN {{users}} u ON u.id=a.author_id WHERE a.status='PUBLISHED' AND a.published_at<=UTC_TIMESTAMP() ORDER BY a.published_at DESC");
        $this->view('articles/index', compact('articles'), 'Cẩm nang săn deal');
    }
    public function show(): void
    {
        $article = DB::one("SELECT a.*,u.full_name FROM {{articles}} a JOIN {{users}} u ON u.id=a.author_id WHERE a.id=? AND a.status='PUBLISHED' AND a.published_at<=UTC_TIMESTAMP()", [$this->request->id()]);
        if (!$article) throw new HttpException('Không tìm thấy bài viết.', 404);
        $this->view('articles/show', compact('article'), $article['title']);
    }
}

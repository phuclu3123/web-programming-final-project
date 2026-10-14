<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\ArticleRepository;
use Throwable;

final class ArticleController extends Controller
{
    private ArticleRepository $articles;
    public function __construct()
    {
        $this->articles = new ArticleRepository(Database::connection());
    }
    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $this->view('admin/articles/index', ['pageTitle' => 'Quản lý bài viết', 'articles' => $this->articles->all()]);
    }
    public function create(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $this->view('admin/articles/form', ['pageTitle' => 'Tạo bài viết', 'article' => null]);
    }
    public function edit(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        $article = $this->articles->find((int)$id);
        if ($article === null) {
            $this->renderError(404, 'Bài viết không tồn tại');
            return;
        }
        $this->view('admin/articles/form', ['pageTitle' => 'Sửa bài viết', 'article' => $article]);
    }
    private function persist(Request $request, ?int $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string)$request->input('_token', ''))) {
            $this->renderError(419, 'Token không hợp lệ');
            return;
        }
        try {
            $this->articles->save((int)Auth::id(), $id, $request->all());
            Session::flash('success', 'Đã lưu bài viết.');
            Response::redirect(url('/admin/articles'));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect($id === null ? url('/admin/articles/create') : url('/admin/articles/' . $id . '/edit'));
        }
    }
    public function store(Request $request): void
    {
        $this->persist($request, null);
    }
    public function update(Request $request, string $id): void
    {
        $this->persist($request, (int)$id);
    }
}

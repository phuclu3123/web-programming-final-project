<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\AdminRepository;
use App\Repositories\CampaignRepository;
use App\Services\AdminService;
use Throwable;

final class CategoryController extends Controller
{
    private AdminRepository $repo;
    private AdminService $service;
    public function __construct()
    {
        $db = Database::connection();
        $this->repo = new AdminRepository($db);
        $this->service = new AdminService($this->repo, new CampaignRepository($db));
    }
    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $this->view('admin/categories/index', ['pageTitle' => 'Quản lý danh mục', 'categories' => $this->repo->categories()]);
    }
    public function store(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string)$request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn.');
            return;
        }
        try {
            $this->service->addCategory((int)Auth::id(), $request->all());
            Session::flash('success', 'Đã thêm danh mục.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('/admin/categories'));
    }
    public function toggle(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string)$request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn.');
            return;
        }
        try {
            $this->repo->toggleCategory((int)$id, (string)$request->input('active', '0') === '1');
            Session::flash('success', 'Đã cập nhật danh mục.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('/admin/categories'));
    }
    public function update(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string)$request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn.');
            return;
        }
        if (!ctype_digit($id) || (int)$id <= 0) { $this->renderError(404, 'Danh mục không hợp lệ.'); return; }
        try {
            $this->service->updateCategory((int)Auth::id(), (int)$id, $request->all());
            Session::flash('success', 'Đã cập nhật tên, mô tả và thứ tự danh mục.');
        } catch (Throwable $e) {
            if ($e instanceof \mysqli_sql_exception) error_log((string)$e);
            Session::flash('error', $e instanceof \RuntimeException && !$e instanceof \mysqli_sql_exception ? $e->getMessage() : 'Không thể cập nhật danh mục.');
        }
        Response::redirect(url('/admin/categories'));
    }

    public function delete(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string)$request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn.');
            return;
        }
        if (!ctype_digit($id) || (int)$id <= 0) { $this->renderError(404, 'Danh mục không hợp lệ.'); return; }
        try {
            $this->service->deleteCategory((int)Auth::id(), (int)$id);
            Session::flash('success', 'Đã xóa danh mục chưa sử dụng.');
        } catch (Throwable $e) {
            if ($e instanceof \mysqli_sql_exception) error_log((string)$e);
            Session::flash('error', $e instanceof \RuntimeException && !$e instanceof \mysqli_sql_exception ? $e->getMessage() : 'Không thể xóa danh mục.');
        }
        Response::redirect(url('/admin/categories'));
    }

}

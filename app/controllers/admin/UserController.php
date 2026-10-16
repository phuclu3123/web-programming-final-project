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

final class UserController extends Controller
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
        $this->view('admin/users/index', ['pageTitle' => 'Quản lý User', 'users' => $this->repo->users()]);
    }

    public function status(Request $request, string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string)$request->input('_token', ''))) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn.');
            return;
        }
        try {
            $this->service->setUserStatus((int)Auth::id(), (int)$id, (string)$request->input('status', ''));
            Session::flash('success', 'Đã cập nhật trạng thái User.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('/admin/users'));
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Repositories\AdminRepository;

final class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $repo = new AdminRepository(Database::connection());
        $this->view('admin/dashboard/index', [
            'pageTitle'=>'Admin Dashboard',
            'stats'=>$repo->dashboardStats(),
        ]);
    }
}

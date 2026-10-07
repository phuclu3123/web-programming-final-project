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

final class MerchantController extends Controller
{
    private AdminRepository $repo;
    private AdminService $service;
    public function __construct(){ $pdo=Database::connection(); $this->repo=new AdminRepository($pdo); $this->service=new AdminService($this->repo,new CampaignRepository($pdo)); }
    public function index(Request $request): void { Auth::requireRole(['ADMIN']); $this->view('admin/merchants/index',['pageTitle'=>'Quản lý Merchant','merchants'=>$this->repo->merchants()]); }
    public function review(Request $request,string $id): void
    {
        Auth::requireRole(['ADMIN']);
        if (!Session::verifyCsrf((string)$request->input('_token',''))) { $this->renderError(419,'Phiên làm việc đã hết hạn.'); return; }
        try {
            $this->service->reviewMerchant((int)Auth::id(),(int)$id,(string)$request->input('action',''),trim((string)$request->input('note','')) ?: null);
            Session::flash('success','Đã cập nhật trạng thái Merchant.');
        } catch (Throwable $e) { Session::flash('error',$e->getMessage()); }
        Response::redirect(url('/admin/merchants'));
    }
}

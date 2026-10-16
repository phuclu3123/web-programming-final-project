<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\MerchantAccessRepository;
use Throwable;

final class MerchantAccessController extends Controller
{
    private MerchantAccessRepository $access;
    public function __construct(){ $this->access=new MerchantAccessRepository(Database::connection()); }
    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);
        $this->view('admin/merchants/requests',['pageTitle'=>'Duyệt quyền Shop','requests'=>$this->access->requests(),'memberships'=>$this->access->memberships()]);
    }
    private function csrf(Request $request): void
    {
        if(!Session::verifyCsrf((string)$request->input('_token',''))){$this->renderError(419,'CSRF token đã hết hạn');exit;}
    }
    public function review(Request $request,string $id): void
    {
        Auth::requireRole(['ADMIN']);$this->csrf($request);
        try{
            $this->access->review(
                (int)Auth::id(),
                (int)$id,
                strtoupper((string)$request->input('action','')),
                trim((string)$request->input('note','')) ?: null,
                (string)$request->input('duplicate_confirmed','') === '1'
            );
            Session::flash('success','Đã xét duyệt yêu cầu.');
        }
        catch(Throwable $e){Session::flash('error',$e->getMessage());}
        Response::redirect(url('/admin/merchant-requests'));
    }
    public function revoke(Request $request,string $userId,string $merchantId): void
    {
        Auth::requireRole(['ADMIN']);$this->csrf($request);
        try{$this->access->revoke((int)Auth::id(),(int)$userId,(int)$merchantId,trim((string)$request->input('note','')));Session::flash('success','Đã thu hồi quyền quản lý Shop này.');}
        catch(Throwable $e){Session::flash('error',$e->getMessage());}
        Response::redirect(url('/admin/merchant-requests'));
    }
}

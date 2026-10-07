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
use App\Services\CampaignService;
use Throwable;

final class CampaignController extends Controller
{
    private AdminRepository $admin;
    private CampaignRepository $campaigns;
    private CampaignService $service;
    public function __construct(){ $pdo=Database::connection(); $this->admin=new AdminRepository($pdo); $this->campaigns=new CampaignRepository($pdo); $this->service=new CampaignService($this->campaigns); }
    public function index(Request $request): void { Auth::requireRole(['ADMIN']); $this->view('admin/campaigns/index',['pageTitle'=>'Quản lý Campaign','campaigns'=>$this->admin->campaigns()]); }
    public function store(Request $request): void
    {
        Auth::requireRole(['ADMIN']); if(!Session::verifyCsrf((string)$request->input('_token',''))){$this->renderError(419,'Phiên làm việc đã hết hạn.');return;}
        try{$input=$request->all();$input['status']=(string)($input['status']??'DRAFT');$this->service->create((int)Auth::id(),null,$input);Session::flash('success','Đã tạo Campaign nền tảng.');}catch(Throwable $e){Session::flash('error',$e->getMessage());}
        Response::redirect(url('/admin/campaigns'));
    }
    public function status(Request $request,string $id): void
    {
        Auth::requireRole(['ADMIN']); if(!Session::verifyCsrf((string)$request->input('_token',''))){$this->renderError(419,'Phiên làm việc đã hết hạn.');return;}
        try{$this->admin->setCampaignStatus((int)$id,strtoupper((string)$request->input('status','')));Session::flash('success','Đã cập nhật Campaign.');}catch(Throwable $e){Session::flash('error',$e->getMessage());}
        Response::redirect(url('/admin/campaigns'));
    }

    public function show(Request $request,string $id): void
    {
        Auth::requireRole(['ADMIN']);
        $campaign=$this->campaigns->findAdmin((int)$id);
        if($campaign===null){$this->renderError(404,'Không tìm thấy Campaign.');return;}
        $this->view('admin/campaigns/show',[
            'pageTitle'=>(string)$campaign['name'],'campaign'=>$campaign,
            'attached'=>$this->campaigns->attachedCoupons((int)$id),
            'eligible'=>$this->campaigns->allActiveCoupons(),
        ]);
    }
    public function attach(Request $request,string $id): void
    {
        Auth::requireRole(['ADMIN']); if(!Session::verifyCsrf((string)$request->input('_token',''))){$this->renderError(419,'Phiên làm việc đã hết hạn.');return;}
        try{$this->campaigns->attach((int)Auth::id(),(int)$id,(int)$request->input('coupon_id',0));Session::flash('success','Đã gắn coupon vào Campaign.');}catch(Throwable $e){Session::flash('error',$e->getMessage());}
        Response::redirect(url('/admin/campaigns/'.(int)$id));
    }
    public function detach(Request $request,string $id,string $couponId): void
    {
        Auth::requireRole(['ADMIN']); if(!Session::verifyCsrf((string)$request->input('_token',''))){$this->renderError(419,'Phiên làm việc đã hết hạn.');return;}
        try{$this->campaigns->detachAdmin((int)$id,(int)$couponId);Session::flash('success','Đã gỡ coupon khỏi Campaign.');}catch(Throwable $e){Session::flash('error',$e->getMessage());}
        Response::redirect(url('/admin/campaigns/'.(int)$id));
    }
}

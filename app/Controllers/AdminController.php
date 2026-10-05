<?php
namespace App\Controllers;
use App\Core\{Controller,Auth,Database as DB,HttpException,Validator as V};
use App\Services\AdminService;
final class AdminController extends Controller {
 public function index():void {
  Auth::require('ADMIN');$tab=(string)$this->request->input('tab','dashboard');
  $queries=[
   'users'=>'SELECT id,email,full_name,role,status,created_at FROM {{users}} WHERE deleted_at IS NULL ORDER BY id DESC LIMIT 200',
   'merchants'=>'SELECT m.*,u.full_name AS owner_name FROM {{merchants}} m LEFT JOIN {{users}} u ON u.id=m.owner_user_id WHERE m.deleted_at IS NULL ORDER BY m.id DESC LIMIT 200',
   'categories'=>'SELECT * FROM {{categories}} ORDER BY sort_order,id',
   'coupons'=>'SELECT c.*,m.name AS merchant_name FROM v_coupon_status c JOIN {{merchants}} m ON m.id=c.merchant_id WHERE c.deleted_at IS NULL ORDER BY (c.moderation_status=\'PENDING\') DESC,c.id DESC LIMIT 200',
   'campaigns'=>'SELECT * FROM {{campaigns}} ORDER BY id DESC LIMIT 200',
   'articles'=>'SELECT * FROM {{articles}} ORDER BY id DESC LIMIT 200',
   'reports'=>'SELECT r.*,c.title,u.full_name FROM {{reports}} r JOIN {{coupons}} c ON c.id=r.coupon_id JOIN {{users}} u ON u.id=r.reported_by ORDER BY (r.status=\'OPEN\') DESC,r.id DESC LIMIT 200',
   'comments'=>'SELECT c.*,u.full_name FROM {{comments}} c JOIN {{users}} u ON u.id=c.user_id ORDER BY c.id DESC LIMIT 200',
  ];
  if($tab!=='dashboard' && !isset($queries[$tab]))throw new HttpException('Mục quản trị không tồn tại.',404);
  $rows=$tab==='dashboard'?[]:DB::all($queries[$tab]);
  if($tab==='coupons' && $this->request->input('category'))$rows=DB::call('lvhp_list_coupons_by_category',[$this->request->id('category')])[0]??[];
  $stats=DB::one("SELECT (SELECT COUNT(*) FROM {{users}} WHERE deleted_at IS NULL) AS users,(SELECT COUNT(*) FROM v_coupon_status WHERE effective_status='ACTIVE') AS active,(SELECT COUNT(*) FROM {{coupons}} WHERE moderation_status='PENDING' AND deleted_at IS NULL) AS pending,(SELECT COUNT(*) FROM {{reports}} WHERE status='OPEN') AS reports");
  $categoryStats=$tab==='dashboard'?DB::call('lvhp_count_coupons_by_category')[0]:[];$merchantStats=$tab==='dashboard'?DB::call('tuq_count_merchants_by_user')[0]:[];
  $this->view('admin/index',compact('tab','rows','stats','categoryStats','merchantStats'),'Quản trị CouponHub');
 }
 public function form():void {
  Auth::require('ADMIN');$entity=V::choice(['entity'=>$this->request->input('entity')],'entity',['categories','articles']);$record=[];
  if($this->request->input('id')){$record=DB::one('SELECT * FROM {{'.$entity.'}} WHERE id=?',[$this->request->id()]);if(!$record)throw new HttpException('Không tìm thấy dữ liệu.',404);}
  $categories=DB::all('SELECT * FROM {{categories}} WHERE is_active=1 ORDER BY name');$this->view('admin/form',compact('entity','record','categories'),'Biên tập nội dung');
 }
 public function save():void {
  $u=Auth::require('ADMIN');$d=$this->request->post();$entity=V::choice($d,'entity',['users','merchants','categories','coupons','campaigns','articles','reports','comments']);$id=$this->request->input('id')?$this->request->id():null;$service=new AdminService();
  if($entity==='categories')$service->category($d,$id);
  elseif($entity==='articles')$service->article($d,$id);
  else {
   if(!$id)throw new HttpException('Thiếu mã dữ liệu.');
   if($entity==='users')$service->user($d,$id);
   elseif($entity==='merchants')$service->merchant($d,$id);
   elseif($entity==='coupons')DB::call('sp_moderate_coupon',[$u->id(),$id,V::choice($d,'moderation_status',['PENDING','APPROVED','REJECTED']),V::choice($d,'publication_status',['ENABLED','DISABLED','EXPIRED','ARCHIVED']),V::text($d,'review_note',3000,false)]);
   elseif($entity==='reports')DB::call('sp_resolve_report',[$u->id(),$id,V::choice($d,'status',['RESOLVED','DISMISSED']),V::text($d,'resolution_note',3000)]);
   elseif($entity==='comments')DB::run('UPDATE {{comments}} SET status=? WHERE id=?',[V::choice($d,'status',['VISIBLE','HIDDEN','DELETED']),$id]);
   elseif($entity==='campaigns')DB::run('UPDATE {{campaigns}} SET status=? WHERE id=?',[V::choice($d,'status',['DRAFT','PUBLISHED','DISABLED','ARCHIVED']),$id]);
  }
  $this->success('Đã cập nhật dữ liệu.','/admin',['tab'=>$entity]);
 }
}

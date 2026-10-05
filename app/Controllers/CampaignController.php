<?php
namespace App\Controllers;
use App\Core\{Controller,Auth,Database as DB,HttpException};
use App\Services\CampaignService;
final class CampaignController extends Controller {
 public function index():void {$campaigns=DB::all("SELECT ca.* FROM {{campaigns}} ca LEFT JOIN {{merchants}} m ON m.id=ca.owner_merchant_id WHERE ca.status='PUBLISHED' AND ca.ends_at>UTC_TIMESTAMP() AND (ca.owner_merchant_id IS NULL OR (m.status='APPROVED' AND m.deleted_at IS NULL)) ORDER BY ca.starts_at");$this->view('campaigns/index',compact('campaigns'),'Mùa săn ưu đãi');}
 public function show():void {
  $id=$this->request->id();$campaign=DB::one("SELECT ca.* FROM {{campaigns}} ca LEFT JOIN {{merchants}} m ON m.id=ca.owner_merchant_id WHERE ca.id=? AND ca.status='PUBLISHED' AND (ca.owner_merchant_id IS NULL OR (m.status='APPROVED' AND m.deleted_at IS NULL))",[$id]);if(!$campaign)throw new HttpException('Không tìm thấy campaign.',404);
  $rows=DB::call('tttt_list_coupons_by_campaign',[$id])[0]??[];
  $coupons=array_values(array_filter($rows,fn($c)=>in_array($c['effective_status'],['ACTIVE','SCHEDULED'],true)));
  $this->view('campaigns/show',compact('campaign','coupons'),$campaign['name']);
 }
 public function manage():void {
  $u=Auth::require();if(!in_array($u->role(),['ADMIN','MERCHANT'],true))throw new HttpException('Cần tài khoản Merchant hoặc Admin.',403);
  $campaign=[];$attached=[];$candidates=[];
  if($this->request->input('id')){$id=$this->request->id();$campaign=(new CampaignService())->manageable($id);$attached=DB::call('tttt_list_coupons_by_campaign',[$id])[0]??[];
   $sql="SELECT id,title,merchant_id FROM {{coupons}} WHERE deleted_at IS NULL AND starts_at<? AND ends_at>?";$params=[$campaign['ends_at'],$campaign['starts_at']];if($campaign['owner_merchant_id']){$sql.=' AND merchant_id=?';$params[]=$campaign['owner_merchant_id'];}$candidates=DB::all($sql.' ORDER BY id DESC LIMIT 200',$params);
  }
  $merchants=$u->isAdmin()?DB::all("SELECT * FROM {{merchants}} WHERE status='APPROVED' AND deleted_at IS NULL"):DB::all("SELECT * FROM {{merchants}} WHERE owner_user_id=? AND status='APPROVED' AND deleted_at IS NULL",[$u->id()]);
  $this->view('campaigns/form',compact('campaign','attached','candidates','merchants'),'Quản lý campaign');
 }
 public function save():void {$id=(new CampaignService())->save($this->request->post(),$this->request->input('id')?$this->request->id():null);$this->success('Đã lưu campaign.','/campaigns/manage',['id'=>$id]);}
 public function attach():void {$id=$this->request->id();(new CampaignService())->attach($id,$this->request->id('coupon_id'),$this->request->input('remove')==='1');$this->success('Đã cập nhật coupon của campaign.','/campaigns/manage',['id'=>$id]);}
}

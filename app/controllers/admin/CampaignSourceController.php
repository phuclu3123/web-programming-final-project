<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\{Auth,AuthRateLimit,BulkSelection,Controller,Database,HttpClient,HttpError,Request,Response,Session};
use App\Repositories\ExternalCampaignRepository;
use App\Services\AccesstradeCampaignService;

// Đồng bộ và duyệt nguồn chiến dịch bằng quyền Admin; không dùng endpoint Campaign NEW.
final class CampaignSourceController extends Controller
{
    private function repo(): ExternalCampaignRepository {return new ExternalCampaignRepository(Database::connection());}
    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);$repo=$this->repo();$ready=$repo->ready();$state=$request->query('state');
        $this->view('admin/integrations/campaigns',['pageTitle'=>'Nguồn chiến dịch ACCESSTRADE','storageReady'=>$ready,
            'providerReady'=>(string)config('integrations.accesstrade.access_key','')!=='','state'=>$state,
            'listing'=>$ready?$repo->page(positiveId($request->query('page','1')),$state):null]);
    }
    public function sync(Request $request): void
    {
        $actor=Auth::requireRole(['ADMIN']);AuthRateLimit::hit('campaign-sync',(string)$actor['id'],6,60);
        $repo=$this->repo();if(!$repo->ready())throw new HttpError(503,'Cần cập nhật database nguồn chiến dịch.');
        $page=positiveId($request->post('page','1'));$pages=positiveId($request->post('pages','1'));
        if($pages>5 || $page+$pages-1>1000)throw new HttpError(422,'Mỗi lần đồng bộ tối đa 5 trang, từ trang 1 đến 1.000.');
        $provider=new AccesstradeCampaignService(new HttpClient(4194304),(string)config('integrations.accesstrade.access_key',''));
        $rows=[];$received=0;$skipped=0;
        // Hoàn tất các GET trước khi mở transaction; lỗi mạng không để lại nửa đợt đồng bộ.
        for($p=$page;$p<$page+$pages;++$p){$result=$provider->fetch($p);$received+=$result['received'];$skipped+=$result['skipped'];foreach($result['campaigns'] as $row)$rows[$row['provider_id']]=$row;if($result['received']<20)break;}
        $count=$repo->stage((int)$actor['id'],array_values($rows));
        $this->done($request,['success'=>true,'staged'=>$count,'received'=>$received,'skipped'=>$skipped,'message'=>"Đã đồng bộ $count chiến dịch vào hàng chờ. Bỏ qua $skipped mục không hợp lệ."],true);
    }
    public function moderate(Request $request): void
    {
        $actor=Auth::requireRole(['ADMIN']);$rows=BulkSelection::parse($request->post('selection'));
        $action=$request->post('action');$note=$request->post('note');
        if(!in_array($action,['APPROVE','DISMISS'],true) || mb_strlen($note)>1000)throw new HttpError(422,'Quyết định hoặc ghi chú không hợp lệ.');
        $results=[];$count=0;
        foreach($rows as $row){
            try{$this->repo()->moderate((int)$actor['id'],$row['id'],$row['fingerprint'],$action,$note);$results[]=['id'=>$row['id'],'success'=>true,'message'=>$action==='APPROVE'?'Đã duyệt hiển thị':'Đã bỏ qua'];++$count;}
            catch(\Throwable $e){$results[]=['id'=>$row['id'],'success'=>false,'message'=>$e instanceof HttpError?$e->getMessage():'Chưa xử lý được mục này. Vui lòng tải lại và thử lại.'];}
        }
        $failed=count($rows)-$count;
        $this->done($request,['success'=>true,'processed'=>$count,'failed'=>$failed,'results'=>$results,'message'=>"Đã xử lý $count mục; $failed mục chưa xử lý được."]);
    }
    private function done(Request $request,array $data,bool $redirect=false): void
    {
        $url=url('/admin/integrations/campaigns');
        if($request->expectsJson())Response::json($redirect?$data+['redirect'=>$url]:$data);
        Session::flash(($data['failed']??0)>0?'warning':'success',$data['message']);Response::redirect($url,303);
    }
}

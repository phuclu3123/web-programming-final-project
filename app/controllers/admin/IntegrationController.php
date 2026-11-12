<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\{Auth, Controller, Database, HttpClient, HttpError, Request, Response, Session, ValidationException, AuthRateLimit};
use App\Repositories\{ExternalOfferRepository, MerchantRepository, CouponRepository, CampaignRepository};
use App\Services\{AccesstradeService,CouponService};

// Đưa dữ liệu ACCESSTRADE vào hàng chờ; nhập nguồn không tự xuất bản coupon.
final class IntegrationController extends Controller
{
    private function repo(): ExternalOfferRepository { return new ExternalOfferRepository(Database::connection()); }
    public function index(Request $request): void
    {
        Auth::requireRole(['ADMIN']);$repo=$this->repo();$ready=$repo->ready();
        $this->view('admin/integrations/index',['pageTitle'=>'Nguồn coupon ACCESSTRADE','storageReady'=>$ready,
            'providerReady'=>(string)config('integrations.accesstrade.access_key','')!=='',
            'listing'=>$ready ? $repo->page(positiveId($request->query('page','1'))) : null]);
    }
    // Lấy và lưu hàng chờ; thao tác này không gọi bước xuất bản coupon.
    public function sync(Request $request): void
    {
        $actor=Auth::requireRole(['ADMIN']);
        AuthRateLimit::hit('external-sync',(string)$actor['id'],6,60);
        $repo=$this->repo();if(!$repo->ready())throw new HttpError(503,'Cần chạy migration external_offers trước.');
        $provider=new AccesstradeService(new HttpClient(),(string)config('integrations.accesstrade.access_key',''));
        $resource=$request->post('resource','offers');
        if(!in_array($resource,['offers','coupons'],true))throw new HttpError(422,'Chỉ nhận nguồn ưu đãi hoặc coupon vào hàng chờ.');
        $result=$provider->normalize($provider->fetch($resource,positiveId($request->post('page','1')),$request->post('merchant')));
        $count=$repo->stage((int)$actor['id'],$result['offers']);
        $this->done($request,['success'=>true,'staged'=>$count,'skipped'=>$result['skipped'],'received'=>$result['received'],'message'=>"Đã nhận $count mục vào hàng chờ. Chưa có coupon nào được xuất bản."],url('/admin/integrations'));
    }
    public function review(Request $request,string $id): void
    {
        Auth::requireRole(['ADMIN']);$offer=$this->repo()->find(positiveId($id));if(!$offer)throw new HttpError(404,'Không tìm thấy nguồn.');
        $values=json_decode($offer['payload'],true,64,JSON_THROW_ON_ERROR);
        foreach(['starts_at','ends_at'] as $field)$values[$field]=empty($values[$field])?'':format_db_datetime($values[$field],'Y-m-d\TH:i:s');
        $this->renderReview($offer,$values);
    }
    private function renderReview(array $offer,array $values,array $errors=[]):void
    {
        $this->view('admin/integrations/review',['pageTitle'=>'Đối chiếu nguồn coupon','offer'=>$offer,'values'=>$values,'errors'=>$errors,
            'merchants'=>(new MerchantRepository(Database::connection()))->approvedForImport(),
            'categories'=>(new CampaignRepository(Database::connection()))->publicCategories()]);
    }
    // Dùng validation coupon chung và fingerprint của nguồn admin đã xem trước khi nhập.
    public function import(Request $request,string $id): void
    {
        $actor=Auth::requireRole(['ADMIN']);
        $input=$request->all();
        try {
            if ($request->post('min_order_amount')==='')throw new ValidationException(['min_order_amount'=>'Xác nhận đơn tối thiểu; nhập 0 nếu không yêu cầu.']);
            $data=(new CouponService(new CouponRepository(Database::connection())))->validateSubmission($input);
        } catch (ValidationException $error) {
            if($request->expectsJson())Response::json(['success'=>false,'message'=>'Kiểm tra lại thông tin coupon.','errors'=>$error->errors()],422);
            $offer=$this->repo()->find(positiveId($id));if(!$offer)throw new HttpError(404,'Không tìm thấy nguồn.');
            $values=json_decode($offer['payload'],true,64,JSON_THROW_ON_ERROR);
            $allowed=array_flip(['merchant_id','category_id','title','code','description','terms','discount_type','discount_value','min_order_amount','max_discount_amount','usage_channel','promotion_url','starts_at','ends_at']);
            $offer['fingerprint']=$request->post('source_fingerprint');
            http_response_code(422);$this->renderReview($offer,array_replace($values,array_intersect_key($input,$allowed)),$error->errors());return;
        }
        $coupon=$this->repo()->import((int)$actor['id'],positiveId($id),$data,$request->post('source_fingerprint'));
        $this->done($request,['success'=>true,'coupon_id'=>$coupon,'message'=>'Đã tạo coupon chờ duyệt. Kiểm tra trong hàng kiểm duyệt trước khi xuất bản.'],url('/admin/coupons?status=PENDING'));
    }
    public function dismiss(Request $request,string $id): void
    {
        $actor=Auth::requireRole(['ADMIN']);$this->repo()->dismiss((int)$actor['id'],positiveId($id));
        $this->done($request,['success'=>true,'message'=>'Đã bỏ qua nguồn này. Đồng bộ không tự mở lại.'],url('/admin/integrations'));
    }
    private function done(Request $request,array $data,string $url): void
    {
        if($request->expectsJson())Response::json($data+['redirect'=>$url]);
        Session::flash('success',$data['message']);Response::redirect($url,303);
    }
}

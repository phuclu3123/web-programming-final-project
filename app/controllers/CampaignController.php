<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Repositories\CampaignRepository;
use App\Repositories\CouponRepository;
use App\Repositories\MerchantRepository;
use App\Services\CampaignService;
use Throwable;

final class CampaignController extends Controller
{
    private CampaignRepository $campaigns;
    private MerchantRepository $merchants;
    private CouponRepository $coupons;
    private CampaignService $service;

    public function __construct()
    {
        $pdo = Database::connection();
        $this->campaigns = new CampaignRepository($pdo);
        $this->merchants = new MerchantRepository($pdo);
        $this->coupons = new CouponRepository($pdo);
        $this->service = new CampaignService($this->campaigns);
    }

    public function show(Request $request, string $id): void
    {
        $campaign = $this->campaigns->findPublic((int) $id);
        if ($campaign === null) { $this->renderError(404, 'Campaign không tồn tại hoặc chưa public.'); return; }
        $this->view('campaigns/show', [
            'pageTitle'=>(string) $campaign['name'],
            'campaign'=>$campaign,
            'coupons'=>$this->campaigns->publicCoupons((int) $campaign['id']),
        ]);
    }

    public function merchantIndex(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->view('merchant/campaigns/index', [
            'pageTitle'=>'Campaign của Merchant',
            'campaigns'=>$this->campaigns->merchantCampaigns((int) Auth::id()),
        ]);
    }

    public function merchantCreate(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->view('merchant/campaigns/form', [
            'pageTitle'=>'Tạo Campaign','mode'=>'create','campaign'=>null,'old'=>[],
            'shops'=>$this->merchants->ownedApproved((int) Auth::id()),
        ]);
    }

    public function merchantStore(Request $request): void
    {
        Auth::requireRole(['MERCHANT']); $this->csrf($request);
        try {
            $merchantId = (int) $request->input('merchant_id', 0);
            if ($this->merchants->findOwnedApproved($merchantId, (int) Auth::id()) === null) throw new \RuntimeException('Merchant không thuộc quyền quản lý của bạn.');
            $id = $this->service->create((int) Auth::id(), $merchantId, $request->all());
            Session::flash('success', 'Đã tạo Campaign. Bạn có thể gắn Official Coupon phù hợp vào Campaign.');
            Response::redirect(url('/merchant/campaigns/' . $id));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('/merchant/campaigns/create'));
        }
    }

    public function merchantShow(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $campaign = $this->campaigns->findOwned((int) $id, (int) Auth::id());
        if ($campaign === null) { $this->renderError(404, 'Không tìm thấy Campaign.'); return; }
        $this->view('merchant/campaigns/show', [
            'pageTitle'=>(string) $campaign['name'],
            'campaign'=>$campaign,
            'attached'=>$this->campaigns->attachedCoupons((int) $id),
            'eligible'=>$this->coupons->eligibleForMerchantCampaign((int) $campaign['owner_merchant_id'], (int) Auth::id()),
        ]);
    }

    public function merchantEdit(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $campaign = $this->campaigns->findOwned((int) $id, (int) Auth::id());
        if ($campaign === null) { $this->renderError(404, 'Không tìm thấy Campaign.'); return; }
        $old = $campaign;
        $old['starts_at'] = $this->utcToLocal((string) $campaign['starts_at']);
        $old['ends_at'] = $this->utcToLocal((string) $campaign['ends_at']);
        $this->view('merchant/campaigns/form', [
            'pageTitle'=>'Sửa Campaign','mode'=>'edit','campaign'=>$campaign,'old'=>$old,
            'shops'=>$this->merchants->ownedApproved((int) Auth::id()),
        ]);
    }

    public function merchantUpdate(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']); $this->csrf($request);
        try {
            $this->service->updateOwned((int) $id, (int) Auth::id(), $request->all());
            Session::flash('success', 'Đã cập nhật Campaign.');
            Response::redirect(url('/merchant/campaigns/' . (int) $id));
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
            Response::redirect(url('/merchant/campaigns/' . (int) $id . '/edit'));
        }
    }

    public function attach(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']); $this->csrf($request);
        try {
            if ($this->campaigns->findOwned((int) $id, (int) Auth::id()) === null) throw new \RuntimeException('Bạn không có quyền sửa Campaign này.');
            $this->campaigns->attach((int) Auth::id(), (int) $id, (int) $request->input('coupon_id', 0));
            Session::flash('success', 'Đã gắn coupon vào Campaign.');
        } catch (Throwable $e) { Session::flash('error', $e->getMessage()); }
        Response::redirect(url('/merchant/campaigns/' . (int) $id));
    }

    public function detach(Request $request, string $id, string $couponId): void
    {
        Auth::requireRole(['MERCHANT']); $this->csrf($request);
        try {
            $this->campaigns->detachOwned((int) $id, (int) $couponId, (int) Auth::id());
            Session::flash('success', 'Đã gỡ coupon khỏi Campaign.');
        } catch (Throwable $e) { Session::flash('error', $e->getMessage()); }
        Response::redirect(url('/merchant/campaigns/' . (int) $id));
    }

    private function csrf(Request $request): void
    {
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) { $this->renderError(419, 'Phiên làm việc đã hết hạn.'); exit; }
    }

    private function utcToLocal(string $value): string
    {
        try { return (new \DateTimeImmutable($value,new \DateTimeZone('UTC')))->setTimezone(new \DateTimeZone((string) config('app.timezone','Asia/Ho_Chi_Minh')))->format('Y-m-d\\TH:i'); }
        catch (\Throwable $e) { return ''; }
    }
}

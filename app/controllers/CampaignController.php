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
        $db = Database::connection();

        $this->campaigns = new CampaignRepository($db);
        $this->merchants = new MerchantRepository($db);
        $this->coupons = new CouponRepository($db);
        $this->service = new CampaignService($this->campaigns);
    }

    public function show(Request $request, string $id): void
    {
        if (!ctype_digit($id) || (int) $id <= 0) {
            $this->renderError(404, 'Không tìm thấy chiến dịch.');
            return;
        }

        $campaign = $this->campaigns->findPublic((int) $id,true);

        if ($campaign === null) {
            $this->renderError(
                404,
                'Campaign không tồn tại hoặc chưa public.'
            );
            return;
        }

        $categoryId = positiveId(
            $request->query('category_id', '0'),
            true
        );

        $usageChannel = $request->query('usage_channel', 'ALL');

        if (!in_array(
            $usageChannel,
            ['ALL', 'ONLINE', 'OFFLINE', 'BOTH'],
            true
        )) {
            $usageChannel = 'ALL';
        }

        $platform=positiveId($request->query('platform','0'),true);
        $this->view('campaigns/show', [
            'pageTitle' => (string) $campaign['name'],
            'campaign' => $campaign,
            'coupons' => $this->campaigns->publicCoupons(
                (int) $campaign['id'],
                $categoryId,
                $usageChannel,
                $platform
            ),
            'categories' => $this->campaigns->publicCategories(),
            'selectedCategory' => $categoryId,
            'selectedChannel' => $usageChannel,
            'selectedPlatform'=>$platform,
            'platforms'=>$this->merchants->publicFilterOptions(),
        ]);
    }

    public function index(Request $request): void
    {
        $phase = $request->query('phase', 'ALL');
        if (!in_array($phase, ['ALL','ACTIVE','UPCOMING'], true)) {
            throw new \App\Core\HttpError(422, 'Bộ lọc chiến dịch không hợp lệ.');
        }
        $categoryId = positiveId($request->query('category_id', '0'), true);
        $usageChannel = $request->query('usage_channel', 'ALL');
        if (!in_array($usageChannel, ['ALL','ONLINE','OFFLINE','BOTH'], true)) $usageChannel = 'ALL';
        $platform=positiveId($request->query('platform','0'),true);
        $listing = $this->campaigns->publicFilteredListing($phase, $categoryId, $usageChannel, positiveId($request->query('page', '1')),9,$platform);
        $previewId = positiveId($request->query('preview', '0'), true);
        $campaignPreview = $listing['items'][0] ?? null;
        if ($previewId > 0) {
            $campaignPreview = null;
            foreach ($listing['items'] as $candidate) {
                if ((int)$candidate['id'] === $previewId) { $campaignPreview = $candidate; break; }
            }
            if ($campaignPreview === null) throw new \App\Core\HttpError(404, 'Chiến dịch không có trong lựa chọn hiện tại.');
        }
        $this->view('campaigns/index', [
            'pageTitle'=>'Chiến dịch ưu đãi', 'phase'=>$phase, 'listing'=>$listing,
            'campaignPreview'=>$campaignPreview,
            'campaignCoupons'=>$campaignPreview ? array_slice($this->campaigns->publicCoupons((int)$campaignPreview['id'], $categoryId, $usageChannel,$platform), 0, 3) : [],
            'categories'=>$this->campaigns->publicCategories(), 'selectedCategory'=>$categoryId, 'selectedChannel'=>$usageChannel,
            'selectedPlatform'=>$platform,'platforms'=>$this->merchants->publicFilterOptions(),
        ]);
    }

    public function merchantIndex(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);

        $this->view('merchant/campaigns/index', [
            'pageTitle' => 'Campaign của Merchant',
            'campaigns' => $this->campaigns->merchantCampaigns(
                (int) Auth::id()
            ),
        ]);
    }

    public function merchantCreate(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);

        $this->view('merchant/campaigns/form', [
            'pageTitle' => 'Tạo Campaign',
            'mode' => 'create',
            'campaign' => null,
            'old' => $this->previousInput('create'),
            'shops' => $this->merchants->ownedApproved(
                (int) Auth::id()
            ),
        ]);
    }

    public function merchantStore(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->csrf($request);

        try {
            $merchantId = positiveId($request->post('merchant_id'));

            if (
                $this->merchants->findOwnedApproved(
                    $merchantId,
                    (int) Auth::id()
                ) === null
            ) {
                throw new \RuntimeException(
                    'Merchant không thuộc quyền quản lý của bạn.'
                );
            }

            $id = $this->service->create(
                (int) Auth::id(),
                $merchantId,
                $request->all()
            );

            Session::flash(
                'success',
                'Đã tạo Campaign. Bạn có thể gắn Official Coupon phù hợp vào Campaign.'
            );

            Response::redirect(url('/merchant/campaigns/' . $id));
        } catch (Throwable $e) {
            $this->flashFailure($e);
            $this->rememberInput($request,'create');

            Response::redirect(url('/merchant/campaigns/create'));
        }
    }

    public function merchantShow(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);

        $campaign = $this->campaigns->findOwned(
            (int) $id,
            (int) Auth::id()
        );

        if ($campaign === null) {
            $this->renderError(404, 'Không tìm thấy Campaign.');
            return;
        }

        $this->view('merchant/campaigns/show', [
            'pageTitle' => (string) $campaign['name'],
            'campaign' => $campaign,
            'attached' => $this->campaigns->attachedCoupons(
                (int) $id
            ),
            'eligible' => $this->coupons->eligibleForMerchantCampaign(
                (int) $campaign['owner_merchant_id'],
                (int) Auth::id()
            ),
        ]);
    }

    public function merchantEdit(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);

        $campaign = $this->campaigns->findOwned(
            (int) $id,
            (int) Auth::id()
        );

        if ($campaign === null) {
            $this->renderError(404, 'Không tìm thấy Campaign.');
            return;
        }

        $old = $campaign;

        $old['starts_at'] = $this->utcToLocal(
            (string) $campaign['starts_at']
        );

        $old['ends_at'] = $this->utcToLocal(
            (string) $campaign['ends_at']
        );
        $old=array_replace($old,$this->previousInput('edit-'.(int)$id));

        $this->view('merchant/campaigns/form', [
            'pageTitle' => 'Sửa Campaign',
            'mode' => 'edit',
            'campaign' => $campaign,
            'old' => $old,
            'shops' => $this->merchants->ownedApproved(
                (int) Auth::id()
            ),
        ]);
    }

    public function merchantUpdate(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->csrf($request);

        try {
            $this->service->updateOwned(
                (int) $id,
                (int) Auth::id(),
                $request->all()
            );

            Session::flash('success', 'Đã cập nhật Campaign.');

            Response::redirect(
                url('/merchant/campaigns/' . (int) $id)
            );
        } catch (Throwable $e) {
            $this->flashFailure($e);
            $this->rememberInput($request,'edit-'.(int)$id);

            Response::redirect(
                url('/merchant/campaigns/' . (int) $id . '/edit')
            );
        }
    }

    public function attach(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->csrf($request);

        try {
            if (
                $this->campaigns->findOwned(
                    (int) $id,
                    (int) Auth::id()
                ) === null
            ) {
                throw new \RuntimeException(
                    'Bạn không có quyền sửa Campaign này.'
                );
            }

            $this->campaigns->attach(
                (int) Auth::id(),
                (int) $id,
                (int) $request->input('coupon_id', 0)
            );

            Session::flash('success', 'Đã gắn coupon vào Campaign.');
        } catch (Throwable $e) {
            $this->flashFailure($e);
        }

        Response::redirect(
            url('/merchant/campaigns/' . (int) $id)
        );
    }

    public function detach(
        Request $request,
        string $id,
        string $couponId
    ): void {
        Auth::requireRole(['MERCHANT']);
        $this->csrf($request);

        try {
            $this->campaigns->detachOwned(
                (int) $id,
                (int) $couponId,
                (int) Auth::id()
            );

            Session::flash('success', 'Đã gỡ coupon khỏi Campaign.');
        } catch (Throwable $e) {
            $this->flashFailure($e);
        }

        Response::redirect(
            url('/merchant/campaigns/' . (int) $id)
        );
    }

    private function rememberInput(Request $request, string $key): void
    {
        $old=[];
        foreach (['merchant_id','name','description','banner_path','starts_at','ends_at','status'] as $field) {
            $value=$request->input($field,'');
            if (is_string($value) && mb_check_encoding($value,'UTF-8') && strlen($value)<=40000) $old[$field]=$value;
        }
        Session::put('campaign_form_old',['key'=>$key,'input'=>$old]);
    }

    private function previousInput(string $key): array
    {
        $old=Session::get('campaign_form_old',[]);
        Session::forget('campaign_form_old');
        return ($old['key'] ?? '')===$key ? $old['input'] : [];
    }

    private function flashFailure(Throwable $error): void
    {
        if ($error instanceof \mysqli_sql_exception) error_log('Campaign write failed: '.get_class($error));
        Session::flash('error',$error instanceof \mysqli_sql_exception ? 'Không thể lưu thay đổi chiến dịch lúc này.' : $error->getMessage());
    }

    private function csrf(Request $request): void
    {
        if (
            !Session::verifyCsrf(
                (string) $request->input('_token', '')
            )
        ) {
            $this->renderError(419, 'Phiên làm việc đã hết hạn.');
            exit;
        }
    }

    private function utcToLocal(string $value): string
    {
        try {
            return (
                new \DateTimeImmutable(
                    $value,
                    new \DateTimeZone('UTC')
                )
            )
                ->setTimezone(
                    new \DateTimeZone(
                        (string) config(
                            'app.timezone',
                            'Asia/Ho_Chi_Minh'
                        )
                    )
                )
                ->format('Y-m-d\\TH:i');
        } catch (\Throwable $e) {
            return '';
        }
    }
}

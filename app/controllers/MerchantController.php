<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\ValidationException;
use App\Repositories\CategoryRepository;
use App\Repositories\CouponRepository;
use App\Repositories\MerchantRepository;
use App\Services\CouponService;
use App\Services\MerchantService;
use RuntimeException;
use Throwable;

final class MerchantController extends Controller
{
    private MerchantRepository $merchants;
    private CouponRepository $coupons;
    private CategoryRepository $categories;
    private MerchantService $service;
    private CouponService $couponService;

    public function __construct()
    {
        $pdo = Database::connection();
        $this->merchants = new MerchantRepository($pdo);
        $this->coupons = new CouponRepository($pdo);
        $this->categories = new CategoryRepository($pdo);
        $this->service = new MerchantService($this->merchants);
        $this->couponService = new CouponService($this->coupons);
    }

    public function apply(Request $request): void
    {
        Auth::requireRole(['USER','MERCHANT']);
        $this->view('merchant/apply', [
            'pageTitle' => 'Đăng ký Merchant',
            'claimable' => $this->merchants->claimable(),
            'applications' => $this->merchants->applicationsByUser((int) Auth::id()),
        ]);
    }

    public function submitApplication(Request $request): void
    {
        Auth::requireRole(['USER','MERCHANT']);
        $this->verifyCsrf($request);
        try {
            $this->service->requestMerchant((int) Auth::id(), $request->all());
            Session::flash('success', 'Đã gửi yêu cầu Merchant. Admin sẽ kiểm tra trước khi kích hoạt Merchant Center.');
        } catch (Throwable $e) {
            Session::flash('error', (bool) config('app.debug', false) || $e instanceof RuntimeException ? $e->getMessage() : 'Không thể gửi yêu cầu Merchant.');
        }
        Response::redirect(url('/merchant/apply'));
    }

    public function dashboard(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);
        $shops = $this->merchants->ownedApproved((int) Auth::id());
        $selected = $this->selectShop($shops, (int) $request->input('merchant_id', 0));
        $stats = $selected ? $this->merchants->stats((int) Auth::id(), (int) $selected['id']) : [];
        $this->view('merchant/dashboard', [
            'pageTitle' => 'Merchant Center',
            'shops' => $shops,
            'merchant' => $selected,
            'stats' => $stats,
        ]);
    }

    public function profile(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $merchant = $this->owned((int) $id);
        $this->view('merchant/profile', ['pageTitle'=>'Hồ sơ Merchant','merchant'=>$merchant]);
    }

    public function updateProfile(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->verifyCsrf($request);
        $merchantId = (int) $id;
        try {
            $this->service->updateProfile($merchantId, (int) Auth::id(), $request->all());
            Session::flash('success', 'Đã cập nhật hồ sơ Merchant.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('/merchant/profile/' . $merchantId));
    }

    public function coupons(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->view('merchant/coupons/index', [
            'pageTitle'=>'Official Coupons',
            'coupons'=>$this->coupons->officialByOwner((int) Auth::id()),
            'shops'=>$this->merchants->ownedApproved((int) Auth::id()),
        ]);
    }

    public function createCoupon(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);
        $shops = $this->merchants->ownedApproved((int) Auth::id());
        $this->renderCouponForm('create', [], [], $shops, null);
    }

    public function storeCoupon(Request $request): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->verifyCsrf($request);
        $shops = $this->merchants->ownedApproved((int) Auth::id());
        $input = $request->all();
        try {
            $merchantId = (int) ($input['merchant_id'] ?? 0);
            if ($this->merchants->findOwnedApproved($merchantId, (int) Auth::id()) === null) throw new RuntimeException('Merchant không thuộc quyền quản lý của bạn.');
            $this->couponService->submitOfficial((int) Auth::id(), $input);
            Session::flash('success', 'Đã tạo Official Deal. Coupon đang chờ Admin duyệt trước khi public.');
            Response::redirect(url('/merchant/coupons'));
        } catch (ValidationException $e) {
            $this->renderCouponForm('create', $e->errors(), $input, $shops, null);
        } catch (Throwable $e) {
            $this->renderCouponForm('create', ['auth'=>$e->getMessage()], $input, $shops, null);
        }
    }

    public function editCoupon(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $coupon = $this->coupons->findOwnedOfficialForEdit((int) $id, (int) Auth::id());
        if ($coupon === null) { $this->renderError(404, 'Không tìm thấy Official Coupon.'); return; }
        $old = $coupon;
        $old['starts_at'] = $this->utcToLocalInput((string) $coupon['starts_at']);
        $old['ends_at'] = $this->utcToLocalInput((string) $coupon['ends_at']);
        $this->renderCouponForm('edit', [], $old, $this->merchants->ownedApproved((int) Auth::id()), $coupon);
    }

    public function updateCoupon(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->verifyCsrf($request);
        $couponId = (int) $id;
        $input = $request->all();
        try {
            $this->couponService->updateOfficial((int) Auth::id(), $couponId, $input);
            Session::flash('success', 'Đã cập nhật Official Coupon và đưa về PENDING để Admin duyệt lại. Vote cũ đã được xóa vì nội dung ưu đãi thay đổi.');
            Response::redirect(url('/merchant/coupons'));
        } catch (ValidationException $e) {
            $coupon = $this->coupons->findOwnedOfficialForEdit($couponId, (int) Auth::id());
            $this->renderCouponForm('edit', $e->errors(), $input, $this->merchants->ownedApproved((int) Auth::id()), $coupon);
        } catch (Throwable $e) {
            $coupon = $this->coupons->findOwnedOfficialForEdit($couponId, (int) Auth::id());
            $this->renderCouponForm('edit', ['auth'=>$e->getMessage()], $input, $this->merchants->ownedApproved((int) Auth::id()), $coupon);
        }
    }

    public function disableCoupon(Request $request, string $id): void
    {
        Auth::requireRole(['MERCHANT']);
        $this->verifyCsrf($request);
        try {
            $this->coupons->disableOwnedOfficial((int) Auth::id(), (int) $id);
            Session::flash('success', 'Đã ngừng Official Coupon. Muốn public lại cần chỉnh sửa và gửi Admin duyệt lại.');
        } catch (Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('/merchant/coupons'));
    }

    /** @param array<int,array<string,mixed>> $shops @return array<string,mixed>|null */
    private function selectShop(array $shops, int $id): ?array
    {
        foreach ($shops as $shop) if ((int) $shop['id'] === $id) return $shop;
        return $shops[0] ?? null;
    }

    /** @return array<string,mixed> */
    private function owned(int $id): array
    {
        $merchant = $this->merchants->findOwnedApproved($id, (int) Auth::id());
        if ($merchant === null) { $this->renderError(404, 'Không tìm thấy Merchant thuộc quyền quản lý của bạn.'); exit; }
        return $merchant;
    }

    /** @param array<string,string> $errors @param array<string,mixed> $old @param array<int,array<string,mixed>> $shops */
    private function renderCouponForm(string $mode, array $errors, array $old, array $shops, ?array $coupon): void
    {
        $this->view('merchant/coupons/form', [
            'pageTitle'=>$mode === 'edit' ? 'Sửa Official Coupon' : 'Tạo Official Coupon',
            'mode'=>$mode,'errors'=>$errors,'old'=>$old,'shops'=>$shops,'coupon'=>$coupon,
            'categories'=>$this->categories->activeAll(),
            'pageScripts'=>['/js/coupon-form.js'],
        ]);
    }

    private function verifyCsrf(Request $request): void
    {
        if (!Session::verifyCsrf((string) $request->input('_token', ''))) { $this->renderError(419, 'Phiên làm việc đã hết hạn.'); exit; }
    }

    private function utcToLocalInput(string $value): string
    {
        try {
            return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))
                ->setTimezone(new \DateTimeZone((string) config('app.timezone', 'Asia/Ho_Chi_Minh')))->format('Y-m-d\\TH:i');
        } catch (\Throwable $e) { return ''; }
    }
}

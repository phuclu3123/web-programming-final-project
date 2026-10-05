<?php

namespace App\Controllers;

use App\Core\{Controller, Auth, Database as DB, HttpException};
use App\Services\MerchantService;

final class MerchantController extends Controller
{
    public function index(): void
    {
        $merchants = DB::all("SELECT m.*,COUNT(c.id) AS coupon_count FROM {{merchants}} m LEFT JOIN v_coupon_status c ON c.merchant_id=m.id AND c.effective_status='ACTIVE' WHERE m.deleted_at IS NULL AND m.status IN ('APPROVED','UNCLAIMED') GROUP BY m.id ORDER BY coupon_count DESC,m.name");
        $this->view('merchant/list', compact('merchants'), 'Cửa hàng');
    }
    public function dashboard(): void
    {
        $u = Auth::require();
        $merchants = DB::call('tuq_list_merchants_by_user', [$u->id()])[0] ?? [];
        $selected = null;
        $stats = [];
        $coupons = [];
        if ($merchants) {
            $id = $this->request->input('id') ? (int)$this->request->input('id') : (int)$merchants[0]['id'];
            foreach ($merchants as $m) if ((int)$m['id'] === $id) $selected = $m;
            if (!$selected) throw new HttpException('Không có quyền xem cửa hàng.', 403);
            if ($selected['status'] === 'APPROVED') $stats = DB::call('sp_merchant_stats', [$u->id(), $id]);
            $coupons = DB::all('SELECT * FROM v_coupon_status WHERE merchant_id=? AND deleted_at IS NULL ORDER BY id DESC', [$id]);
        }
        $campaigns = DB::all('SELECT ca.* FROM {{campaigns}} ca JOIN {{merchants}} m ON m.id=ca.owner_merchant_id WHERE m.owner_user_id=? ORDER BY ca.id DESC', [$u->id()]);
        $this->view('merchant/dashboard', compact('merchants', 'selected', 'stats', 'coupons', 'campaigns'), 'Merchant Center');
    }
    public function form(): void
    {
        $u = Auth::require();
        $merchant = [];
        if ($this->request->input('id')) {
            $merchant = DB::one('SELECT * FROM {{merchants}} WHERE id=? AND deleted_at IS NULL', [$this->request->id()]);
            if (!$merchant || (!$u->isAdmin() && (int)$merchant['owner_user_id'] !== $u->id())) throw new HttpException('Không được sửa cửa hàng.', 403);
        }
        $owners = $u->isAdmin() ? DB::all("SELECT id,full_name FROM {{users}} WHERE role='MERCHANT' AND status='ACTIVE' AND deleted_at IS NULL") : [];
        $this->view('merchant/form', compact('merchant', 'owners'), 'Hồ sơ cửa hàng');
    }
    public function save(): void
    {
        (new MerchantService())->save($this->request->post(), $this->request->input('id') ? $this->request->id() : null);
        $this->success('Đã lưu hồ sơ cửa hàng. Hồ sơ mới cần được Admin duyệt.', '/merchant');
    }
}

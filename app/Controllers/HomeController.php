<?php

namespace App\Controllers;

use App\Core\{Controller, Database as DB};

final class HomeController extends Controller
{
    public function index(): void
    {
        $categories = DB::all('SELECT cat.*,COUNT(c.id) AS coupon_count FROM {{categories}} cat LEFT JOIN v_coupon_status c ON c.category_id=cat.id AND c.effective_status=? WHERE cat.is_active=1 GROUP BY cat.id ORDER BY cat.sort_order,cat.id', ['ACTIVE']);
        $coupons = DB::all("SELECT c.*,m.name AS merchant_name,s.trust_score,s.total_votes,(SELECT COUNT(*) FROM {{favorites}} f WHERE f.coupon_id=c.id) AS favorite_count FROM v_coupon_status c JOIN {{merchants}} m ON m.id=c.merchant_id JOIN v_coupon_vote_stats s ON s.coupon_id=c.id WHERE c.effective_status='ACTIVE' ORDER BY favorite_count DESC,c.created_at DESC LIMIT 6");
        $campaigns = DB::all("SELECT ca.* FROM {{campaigns}} ca LEFT JOIN {{merchants}} m ON m.id=ca.owner_merchant_id WHERE ca.status='PUBLISHED' AND ca.ends_at>UTC_TIMESTAMP() AND (ca.owner_merchant_id IS NULL OR (m.status='APPROVED' AND m.deleted_at IS NULL)) ORDER BY ca.starts_at LIMIT 3");
        $stats = DB::one("SELECT (SELECT COUNT(*) FROM v_coupon_status WHERE effective_status='ACTIVE') AS coupons,(SELECT COUNT(*) FROM {{merchants}} WHERE deleted_at IS NULL AND status IN ('APPROVED','UNCLAIMED')) AS merchants,(SELECT COUNT(*) FROM {{votes}} WHERE vote_type='WORKING') AS verifications");
        $this->view('home', compact('categories', 'coupons', 'campaigns', 'stats'), 'CouponHub — Tìm deal tốt, chia sẻ điều hay');
    }
}

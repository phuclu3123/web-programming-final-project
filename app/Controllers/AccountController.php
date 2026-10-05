<?php

namespace App\Controllers;

use App\Core\{Controller, Auth, Database as DB, HttpException};
use App\Services\AuthService;
use App\Repositories\CouponRepository;

final class AccountController extends Controller
{
    public function index(): void
    {
        $u = Auth::require();
        $repo = new CouponRepository();
        $mine = $repo->mine($u->id());
        $favorites = $repo->favorites($u->id());
        $level = DB::one('SELECT * FROM v_user_level WHERE user_id=?', [$u->id()]);
        $points = DB::all('SELECT p.*,c.title FROM {{point_transactions}} p JOIN {{coupons}} c ON c.id=p.coupon_id WHERE p.user_id=? ORDER BY p.id DESC LIMIT 100', [$u->id()]);
        $activity = DB::all('SELECT * FROM {{activity_logs}} WHERE user_id=? ORDER BY id DESC LIMIT 100', [$u->id()]);
        $leaders = DB::all("SELECT l.*,u.full_name FROM v_user_level l JOIN {{users}} u ON u.id=l.user_id WHERE u.deleted_at IS NULL AND u.status='ACTIVE' ORDER BY l.earned_points DESC,u.id LIMIT 10");
        $reputation = DB::one('SELECT * FROM v_contributor_stats WHERE user_id=?', [$u->id()]);
        $this->view('account/index', compact('mine', 'favorites', 'level', 'points', 'activity', 'leaders', 'reputation'), 'Tài khoản của bạn');
    }
    public function profile(): void
    {
        (new AuthService())->profile($this->request->post());
        $this->success('Đã cập nhật hồ sơ.', '/account');
    }
    public function password(): void
    {
        (new AuthService())->changePassword($this->request->post());
        $this->success('Đã đổi mật khẩu.', '/account');
    }
    public function deleteComment(): void
    {
        $u = Auth::require();
        $id = $this->request->id();
        $c = DB::one('SELECT * FROM {{comments}} WHERE id=?', [$id]);
        if (!$c || (!$u->isAdmin() && (int)$c['user_id'] !== $u->id())) throw new HttpException('Không có quyền xóa bình luận.', 403);
        DB::run("UPDATE {{comments}} SET status='DELETED' WHERE id=?", [$id]);
        $this->success('Đã xóa bình luận.', '/coupons/show', ['id' => $c['coupon_id']]);
    }
}

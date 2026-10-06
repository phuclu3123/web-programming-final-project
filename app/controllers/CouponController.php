<?php

namespace App\Controllers;

use App\Core\{Controller, Auth, Database as DB, HttpException, Security, Validator as V};
use App\Repositories\CouponRepository;
use App\Services\CouponService;

final class CouponController extends Controller
{
    private function filters(): array
    {
        $d = ['q' => mb_substr((string)$this->request->input('q', ''), 0, 200), 'category' => (int)$this->request->input('category', 0), 'merchant' => (int)$this->request->input('merchant', 0), 'type' => (string)$this->request->input('type', ''), 'channel' => (string)$this->request->input('channel', ''), 'ending' => (int)(bool)$this->request->input('ending', 0), 'sort' => (string)$this->request->input('sort', 'NEWEST'), 'page' => max(1, min(100000, (int)$this->request->input('page', 1)))];
        return $d;
    }
    private function rows(array $filters): array
    {
        $rows = (new CouponRepository())->search($filters);
        // Fetch full coupon attributes for rendering, preserving order returned by the routine.
        if ($rows) {
            $ids = array_column($rows, 'id');
            $full = DB::all('SELECT c.*,m.name AS merchant_name,s.trust_score,s.total_votes FROM v_coupon_status c JOIN {{merchants}} m ON m.id=c.merchant_id JOIN v_coupon_vote_stats s ON s.coupon_id=c.id WHERE c.id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids);
            $map = array_column($full, null, 'id');
            $rows = array_values(array_filter(array_map(fn($r) => $map[$r['id']] ?? null, $rows)));
        }
        return $rows;
    }
    public function index(): void
    {
        $filters = $this->filters();
        $coupons = $this->rows($filters);
        $categories = DB::all('SELECT * FROM {{categories}} WHERE is_active=1 ORDER BY sort_order,id');
        $merchants = DB::all("SELECT id,name FROM {{merchants}} WHERE deleted_at IS NULL AND status NOT IN ('LOCKED','REJECTED') ORDER BY name");
        $this->view('coupons/index', compact('filters', 'coupons', 'categories', 'merchants'), 'Khám phá coupon');
    }
    public function search(): void
    {
        $filters = $this->filters();
        $coupons = $this->rows($filters);
        ob_start();
        require ROOT . '/app/Views/partials/coupon-grid.php';
        $html = ob_get_clean();
        $this->json(['success' => true, 'html' => $html, 'count' => count($coupons), 'page' => $filters['page'], 'has_next' => count($coupons) === 12]);
    }
    public function show(): void
    {
        $coupon = (new CouponService())->get($this->request->id());
        $id = (int)$coupon['id'];
        $u = Auth::user();
        $saved = $u && (bool)DB::one('SELECT user_id FROM {{favorites}} WHERE coupon_id=? AND user_id=?', [$id, $u->id()]);
        $vote = $u ? DB::one('SELECT vote_type FROM {{votes}} WHERE coupon_id=? AND user_id=?', [$id, $u->id()]) : null;
        $comments = DB::all("SELECT c.*,u.full_name FROM {{comments}} c JOIN {{users}} u ON u.id=c.user_id WHERE c.coupon_id=? AND c.status='VISIBLE' ORDER BY c.created_at DESC LIMIT 100", [$id]);
        $related = (new CouponRepository())->related($coupon);
        if (in_array($coupon['effective_status'], ['ACTIVE', 'SCHEDULED'], true) && time() - ($_SESSION['viewed'][$id] ?? 0) > 1800) {
            Security::limit('view:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 120, 60);
            DB::call('sp_record_coupon_event', [$u?->id(), $id, 'VIEW', bin2hex(random_bytes(16))]);
            $_SESSION['viewed'][$id] = time();
        }
        $this->view('coupons/show', compact('coupon', 'saved', 'vote', 'comments', 'related'), $coupon['title']);
    }
    public function form(): void
    {
        $user = Auth::require();
        $coupon = [];
        if ($this->request->input('id')) {
            $m = (new CouponRepository())->find($this->request->id());
            if (!$m || !$m->isEditableBy($user)) throw new HttpException('Không được sửa coupon này.', 403);
            $coupon = $m->toArray();
        }
        $categories = DB::all('SELECT * FROM {{categories}} WHERE is_active=1 ORDER BY name');
        $merchants = DB::all("SELECT * FROM {{merchants}} WHERE deleted_at IS NULL AND status NOT IN ('LOCKED','REJECTED') ORDER BY name");
        $this->view('coupons/form', compact('coupon', 'categories', 'merchants'), $coupon ? 'Sửa coupon' : 'Chia sẻ coupon');
    }
    public function save(): void
    {
        $id = (new CouponService())->save($this->request->post(), $this->request->input('id') ? $this->request->id() : null);
        $this->success('Đã lưu và gửi coupon đến quản trị viên xét duyệt.', '/coupons/show', ['id' => $id]);
    }
    public function stop(): void
    {
        (new CouponService())->stop($this->request->id(), $this->request->input('delete') === '1');
        $this->success('Đã ngừng hoặc lưu trữ coupon.', '/account');
    }
    public function interact(): void
    {
        $result = (new CouponService())->interact((string)$this->request->input('action'), $this->request->id(), $this->request->post());
        $this->json(['success' => true, 'message' => 'Đã cập nhật.'] + $result);
    }
    public function event(): void
    {
        $id = $this->request->id();
        (new CouponService())->get($id);
        $event = V::choice($this->request->post(), 'event_type', ['COPY', 'SHARE']);
        Security::limit('events:' . ($_SERVER['REMOTE_ADDR'] ?? ''), 40, 60);
        if (time() - ($_SESSION['events'][$id][$event] ?? 0) > 30) {
            DB::call('sp_record_coupon_event', [Auth::user()?->id(), $id, $event, bin2hex(random_bytes(16))]);
            $_SESSION['events'][$id][$event] = time();
        }
        $this->json(['success' => true]);
    }
}

<?php use App\Core\Auth; ?>
<?php
$points = (int) ($rewardSummary['earned_points'] ?? 0);
$nextPoints = $rewardSummary['next_level_points'] ?? null;
$progress = 100;
if ($nextPoints !== null && (int) $nextPoints > 0) {
    $progress = min(100, max(0, (int) floor(($points / (int) $nextPoints) * 100)));
}
?>
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <h1 class="h4">Tài khoản của tôi</h1>
                <hr>
                <dl class="row mb-0">
                    <dt class="col-sm-3">Họ tên</dt><dd class="col-sm-9"><?= e((string) (Auth::user()['full_name'] ?? '')) ?></dd>
                    <dt class="col-sm-3">Email</dt><dd class="col-sm-9"><?= e((string) (Auth::user()['email'] ?? '')) ?></dd>
                    <dt class="col-sm-3">Role</dt><dd class="col-sm-9"><span class="badge badge-info"><?= e((string) Auth::role()) ?></span></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body">
                <div class="small text-muted">CouponPoint</div>
                <div class="point-card-number"><?= $points ?></div>
                <div class="mb-3">Cấp bậc: <strong><?= e((string) ($rewardSummary['level_name'] ?? 'Newbie')) ?></strong></div>
                <?php if ($nextPoints !== null): ?>
                    <div class="small text-muted mb-1">Còn <?= max(0, (int) $nextPoints - $points) ?> điểm để lên <?= e((string) ($rewardSummary['next_level_name'] ?? 'cấp tiếp theo')) ?></div>
                    <div class="progress progress-thin"><div class="progress-bar" role="progressbar" style="width: <?= $progress ?>%" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div></div>
                <?php else: ?>
                    <div class="small text-success">Bạn đang ở cấp cao nhất hiện tại.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (in_array(Auth::role(), ['USER', 'MERCHANT'], true)): ?>
<div class="row mb-4">
    <div class="col-md-4 mb-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Đã gửi</div><div class="h3 mb-0"><?= (int) ($rewardSummary['submitted_count'] ?? 0) ?></div></div></div></div>
    <div class="col-md-4 mb-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Được duyệt</div><div class="h3 mb-0 text-success"><?= (int) ($rewardSummary['approved_count'] ?? 0) ?></div></div></div></div>
    <div class="col-md-4 mb-3"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Bị từ chối</div><div class="h3 mb-0 text-danger"><?= (int) ($rewardSummary['rejected_count'] ?? 0) ?></div></div></div></div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5">Lịch sử CouponPoint</h2>
        <?php if ($pointHistory === []): ?>
            <div class="text-muted small">Chưa có giao dịch điểm.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Thời gian</th><th>Coupon</th><th>Lý do</th><th class="text-right">Điểm</th></tr></thead>
                    <tbody>
                    <?php foreach ($pointHistory as $item): ?>
                        <tr>
                            <td><?= e(format_db_datetime((string) $item['created_at'])) ?></td>
                            <td><?= e((string) $item['coupon_title']) ?></td>
                            <td><?= e((string) ($item['note'] ?: $item['rule_code'])) ?></td>
                            <td class="text-right font-weight-bold text-success">+<?= (int) $item['points'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php

use App\Core\Session; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Báo cáo coupon</h1>
        <p class="text-muted mb-0">Các báo cáo OPEN từ cộng đồng đang chờ Admin xử lý.</p>
    </div>
    <a href="<?= e(url('/admin')) ?>" class="btn btn-outline-secondary">Admin Dashboard</a>
</div>

<?php if ($reports === []): ?>
    <div class="alert alert-info">Hiện không có báo cáo nào đang chờ xử lý.</div>
<?php else: ?>
    <?php foreach ($reports as $report): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="small text-muted mb-1">Report #<?= (int) $report['id'] ?> · gửi
                            <?= e(format_db_datetime((string) $report['created_at'])) ?></div>
                        <h2 class="h5 mb-2"><?= e((string) $report['coupon_title']) ?></h2>
                        <div class="mb-2">
                            Merchant: <strong><?= e((string) $report['merchant_name']) ?></strong>
                            <?php if (!empty($report['code'])): ?> ·
                                <code><?= e((string) $report['code']) ?></code><?php endif; ?>
                        </div>
                        <div class="mb-2"><strong>Lý do:</strong> <?= e(report_reason_label((string) $report['reason'])) ?>
                        </div>
                        <?php if (!empty($report['details'])): ?>
                            <div class="mb-2"><strong>Chi tiết:</strong><br><?= nl2br(e((string) $report['details'])) ?></div>
                        <?php endif; ?>
                        <div class="small text-muted">Người báo cáo: <?= e((string) $report['reporter_name']) ?>
                            (<?= e((string) $report['reporter_email']) ?>)</div>
                        <a class="btn btn-sm btn-outline-primary mt-3"
                            href="<?= e(url('/coupons/' . (int) $report['coupon_id'])) ?>">Mở coupon</a>
                    </div>
                    <div class="col-lg-4 mt-3 mt-lg-0">
                        <form method="post" action="<?= e(url('/admin/reports/' . (int) $report['id'] . '/resolve')) ?>">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <div class="form-group">
                                <label for="resolution-note-<?= (int) $report['id'] ?>">Ghi chú xử lý <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="resolution-note-<?= (int) $report['id'] ?>" name="note"
                                    rows="4" required placeholder="Ví dụ: Đã kiểm tra và cập nhật điều kiện coupon."></textarea>
                            </div>
                            <button class="btn btn-success btn-sm" type="submit" name="action" value="RESOLVED">Đã xử
                                lý</button>
                            <button class="btn btn-outline-secondary btn-sm" type="submit" name="action" value="DISMISSED">Bỏ
                                qua</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
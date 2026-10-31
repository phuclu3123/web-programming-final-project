<?php

use App\Core\Session; ?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e((string)$campaign['name']) ?></h1>
        <div class="text-muted"><?= e((string)($campaign['merchant_name'] ?? 'Campaign nền tảng')) ?> ·
            <?= e(format_db_datetime((string)$campaign['starts_at'])) ?> →
            <?= e(format_db_datetime((string)$campaign['ends_at'])) ?></div>
    </div><a class="btn btn-outline-secondary" href="<?= e(url('/admin/campaigns')) ?>">Danh sách</a>
</div>
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Coupon đã gắn</h2><?php if ($attached === []): ?><p class="text-muted">Chưa có coupon.
                    </p>
                    <?php endif; ?><?php foreach ($attached as $c): ?><div
                        class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                        <div><strong><?= e((string)$c['title']) ?></strong>
                            <div class="small text-muted"><?= e((string)$c['merchant_name']) ?> ·
                                <?= e(coupon_status_label((string)$c['effective_status'])) ?></div>
                        </div>
                        <form method="post"
                            action="<?= e(url('/admin/campaigns/' . (int)$campaign['id'] . '/coupons/' . (int)$c['id'] . '/detach')) ?>">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><button
                                class="btn btn-sm btn-outline-danger">Gỡ</button>
                        </form>
                    </div><?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Gắn coupon ACTIVE</h2>
                <form method="post" action="<?= e(url('/admin/campaigns/' . (int)$campaign['id'] . '/attach')) ?>">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><select name="coupon_id"
                        aria-label="Coupon để gắn vào chiến dịch" class="form-control mb-3" required>
                        <option value="">-- Chọn coupon --</option><?php foreach ($eligible as $c): ?><option
                                value="<?= (int)$c['id'] ?>"><?= e((string)$c['merchant_name']) ?> ·
                                <?= e((string)$c['title']) ?></option><?php endforeach; ?>
                    </select><button class="btn btn-primary w-100">Gắn vào Campaign</button>
                </form><small class="text-muted d-block mt-2">Chiến dịch nền tảng có thể có mã từ nhiều cửa hàng. Coupon
                    cần còn hiệu lực trong thời gian diễn ra chiến dịch.</small>
            </div>
        </div>
    </div>
</div>
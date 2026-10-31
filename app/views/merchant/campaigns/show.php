<?php

use App\Core\Session; ?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 mb-1"><?= e((string)$campaign['name']) ?></h1>
        <div class="text-muted"><?= e((string)$campaign['merchant_name']) ?> ·
            <?= e(format_db_datetime((string)$campaign['starts_at'])) ?> →
            <?= e(format_db_datetime((string)$campaign['ends_at'])) ?></div>
    </div>
    <div><span
            class="badge bg-<?= e(campaign_status_class((string)$campaign['status'])) ?> me-2"><?= e(campaign_status_label((string)$campaign['status'])) ?></span><a
            class="btn btn-outline-primary btn-sm"
            href="<?= e(url('/merchant/campaigns/' . (int)$campaign['id'] . '/edit')) ?>">Sửa</a></div>
</div>
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5">Coupon trong Campaign</h2><?php if ($attached === []): ?><p class="text-muted">Chưa gắn
                        coupon.</p><?php endif; ?><?php foreach ($attached as $c): ?><div
                        class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">
                        <div><strong><?= e((string)$c['title']) ?></strong>
                            <div class="small text-muted"><?= e((string)$c['merchant_name']) ?> ·
                                <?= e(coupon_status_label((string)$c['effective_status'])) ?></div>
                        </div>
                        <form method="post"
                            action="<?= e(url('/merchant/campaigns/' . (int)$campaign['id'] . '/coupons/' . (int)$c['id'] . '/detach')) ?>">
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
                <h2 class="h5">Gắn Official Coupon</h2>
                <form method="post" action="<?= e(url('/merchant/campaigns/' . (int)$campaign['id'] . '/attach')) ?>">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><select name="coupon_id"
                        aria-label="Coupon của cửa hàng để gắn vào chiến dịch" class="form-control mb-3" required>
                        <option value="">-- Chọn coupon --</option><?php foreach ($eligible as $c): ?><option
                                value="<?= (int)$c['id'] ?>"><?= e((string)$c['title']) ?>
                                (<?= e(coupon_status_label((string)$c['effective_status'])) ?>)</option><?php endforeach; ?>
                    </select><button class="btn btn-primary w-100" <?= $eligible === [] ? 'disabled' : '' ?>>Gắn vào
                        chiến dịch</button>
                </form><small class="text-muted d-block mt-2">Chọn mã của cùng cửa hàng, có
                    thời gian hiệu lực trong đợt chiến dịch.</small>
            </div>
        </div>
    </div>
</div>
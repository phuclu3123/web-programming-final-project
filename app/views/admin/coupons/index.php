<?php use App\Core\Session; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Kiểm duyệt coupon</h1>
        <p class="text-muted mb-0">Danh sách coupon đang PENDING. Admin nên kiểm tra đầy đủ điều kiện trước khi duyệt.</p>
    </div>
    <a href="<?= e(url('/admin')) ?>" class="btn btn-outline-secondary">Admin Dashboard</a>
</div>

<?php if ($coupons === []): ?>
    <div class="alert alert-info">Hiện không có coupon nào đang chờ duyệt.</div>
<?php else: ?>
    <?php foreach ($coupons as $coupon): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="small text-muted mb-1">#<?= (int) $coupon['id'] ?> · <?= e((string) $coupon['source']) ?> · gửi <?= e(format_db_datetime((string) $coupon['created_at'])) ?></div>
                        <h2 class="h5 mb-2"><?= e((string) $coupon['title']) ?></h2>
                        <div class="mb-3 d-flex flex-wrap align-items-center" style="gap:.35rem">
                            <span class="font-weight-bold"><?= e((string) $coupon['merchant_name']) ?></span>
                            <span class="badge-soft <?= e(category_badge_class((string) $coupon['category_name'])) ?>"><?= e((string) $coupon['category_name']) ?></span>
                            <span class="badge-soft <?= e(usage_badge_class((string) $coupon['usage_channel'])) ?>"><?= e(usage_channel_label((string) $coupon['usage_channel'])) ?></span>
                        </div>

                        <dl class="row small admin-detail-grid mb-2">
                            <dt class="col-sm-4">Ưu đãi</dt><dd class="col-sm-8"><strong><?= e(format_discount($coupon)) ?></strong></dd>
                            <dt class="col-sm-4">Mã coupon</dt><dd class="col-sm-8"><?= !empty($coupon['code']) ? '<code>' . e((string) $coupon['code']) . '</code>' : '—' ?></dd>
                            <dt class="col-sm-4">Đơn tối thiểu</dt><dd class="col-sm-8"><?= e(format_money($coupon['min_order_amount'])) ?></dd>
                            <dt class="col-sm-4">Giảm tối đa</dt><dd class="col-sm-8"><?= e(format_money($coupon['max_discount_amount'])) ?></dd>
                            <dt class="col-sm-4">Hiệu lực</dt><dd class="col-sm-8"><?= e(format_db_datetime((string) $coupon['starts_at'])) ?> → <?= e(format_db_datetime((string) $coupon['ends_at'])) ?></dd>
                            <dt class="col-sm-4">Người gửi</dt><dd class="col-sm-8"><?= e((string) $coupon['submitter_name']) ?> (<?= e((string) $coupon['submitter_email']) ?>)</dd>
                            <dt class="col-sm-4">Promotion URL</dt>
                            <dd class="col-sm-8">
                                <?php if (!empty($coupon['promotion_url'])): ?>
                                    <a href="<?= e((string) $coupon['promotion_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) $coupon['promotion_url']) ?></a>
                                <?php else: ?>—<?php endif; ?>
                            </dd>
                        </dl>

                        <?php if (!empty($coupon['description'])): ?>
                            <div class="mb-2"><strong>Mô tả</strong><div><?= nl2br(e((string) $coupon['description'])) ?></div></div>
                        <?php endif; ?>
                        <div><strong>Điều kiện sử dụng</strong><div><?= nl2br(e((string) $coupon['terms'])) ?></div></div>
                    </div>

                    <div class="col-lg-4 mt-3 mt-lg-0">
                        <form method="post" action="<?= e(url('/admin/coupons/' . (int) $coupon['id'] . '/moderate')) ?>">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <div class="form-group">
                                <label class="small" for="note-<?= (int) $coupon['id'] ?>">Ghi chú kiểm duyệt</label>
                                <textarea class="form-control" id="note-<?= (int) $coupon['id'] ?>" name="note" rows="4" placeholder="Ghi lý do nếu từ chối; có thể ghi chú khi duyệt."></textarea>
                            </div>
                            <button class="btn btn-success" name="action" value="APPROVE" type="submit">Duyệt</button>
                            <button class="btn btn-outline-danger" name="action" value="REJECT" type="submit">Từ chối</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

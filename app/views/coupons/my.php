<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Coupon đã đóng góp</h1>
        <p class="text-muted mb-0">Theo dõi trạng thái kiểm duyệt các coupon bạn đã gửi.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('/coupons/create')) ?>">+ Chia sẻ coupon</a>
</div>

<?php if ($coupons === []): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">Bạn chưa đóng góp coupon nào.</div>
    </div>
<?php else: ?>
    <div class="table-responsive card shadow-sm">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Coupon</th>
                    <th>Merchant</th>
                    <th>Ưu đãi</th>
                    <th>Kênh dùng</th>
                    <th>Thời hạn</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><strong><?= e((string) $coupon['title']) ?></strong>
                            <div class="small text-muted"><?= e((string) ($coupon['code'] ?? 'Không có mã')) ?></div>
                        </td>
                        <td>
                            <?= e((string) $coupon['merchant_name']) ?>
                            <div class="mt-1"><span
                                    class="badge-soft <?= e(category_badge_class((string) $coupon['category_name'])) ?>"><?= e((string) $coupon['category_name']) ?></span>
                            </div>
                        </td>
                        <td><?= e(format_discount($coupon)) ?></td>
                        <td><span
                                class="badge-soft <?= e(usage_badge_class((string) $coupon['usage_channel'])) ?>"><?= e(usage_channel_label((string) $coupon['usage_channel'])) ?></span>
                        </td>
                        <td class="small"><?= e(format_db_datetime((string) $coupon['starts_at'])) ?><br>→
                            <?= e(format_db_datetime((string) $coupon['ends_at'])) ?></td>
                        <td><span
                                class="badge badge-<?= e(coupon_status_class((string) $coupon['effective_status'])) ?>"><?= e(coupon_status_label((string) $coupon['effective_status'])) ?></span><?php if (!empty($coupon['review_note'])): ?>
                                <div class="small text-muted mt-1"><?= e((string) $coupon['review_note']) ?></div><?php endif; ?>
                        </td>
                        <td><?php if ($coupon['effective_status'] === 'ACTIVE'): ?><a
                                    href="<?= e(url('/coupons/' . (int) $coupon['id'])) ?>"
                                    class="btn btn-sm btn-outline-primary">Xem</a><?php endif; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
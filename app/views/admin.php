<?php
require __DIR__ . '/layouts/head.php';
require __DIR__ . '/layouts/workspace-start.php';

$queueCopy = match ($status) {
    'PENDING' => ['HÀNG CHỜ KIỂM DUYỆT', 'Mã cần xem xét', 'Chọn một mã để xem đầy đủ dữ liệu trước khi quyết định.'],
    'APPROVED' => ['LỊCH SỬ KIỂM DUYỆT', 'Mã đã duyệt', 'Tra cứu các mã đã được phê duyệt.'],
    'REJECTED' => ['LỊCH SỬ KIỂM DUYỆT', 'Mã đã từ chối', 'Tra cứu lý do từ chối để hỗ trợ Merchant chỉnh sửa.'],
};
?>

<div class="page-heading">
    <div>
        <div class="eyebrow">TRUNG TÂM QUẢN TRỊ</div>
        <h1>Kiểm duyệt ưu đãi</h1>
        <p>Đối chiếu thông tin cửa hàng, giá trị và điều kiện sử dụng trước khi công bố mã.</p>
    </div>
</div>

<?php if ($success): ?><div class="notice" role="status"><?= e($success) ?></div><?php endif; ?>
<?php if ($errorMessage): ?><div class="notice error" role="alert"><?= e($errorMessage) ?></div><?php endif; ?>

<div class="stats-grid five" id="overview">
    <?php foreach (['merchants'=>'Cửa hàng đã duyệt','pending'=>'Mã chờ duyệt','approved'=>'Mã đã duyệt','rejected'=>'Mã bị từ chối','total'=>'Tổng mã ưu đãi'] as $key=>$label): ?>
    <div class="stat-card <?= $key === 'pending' ? 'accent' : '' ?>">
        <div class="stat-label"><?= e($label) ?><?= icon(match ($key) { 'merchants'=>'store', 'approved'=>'check', 'rejected'=>'flag', default=>'ticket' }) ?></div>
        <div class="stat-value"><?= (int) $stats[$key] ?></div>
        <div class="stat-foot"><?= $key === 'pending' ? 'Cần xử lý trong hàng chờ' : 'Dữ liệu hiện tại của nền tảng' ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="moderation-grid">
    <section class="panel moderation-queue" id="queue">
        <div class="panel-head queue-head">
            <div>
                <div class="panel-kicker"><?= e($queueCopy[0]) ?></div>
                <h2><?= e($queueCopy[1]) ?></h2>
                <p><?= e($queueCopy[2]) ?></p>
            </div>
            <span class="queue-total"><strong id="admin-visible-count"><?= count($coupons) ?></strong> mã</span>
        </div>

        <nav class="queue-tabs" aria-label="Trạng thái mã ưu đãi">
            <?php foreach (['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','REJECTED'=>'Từ chối'] as $key=>$label): ?>
            <a href="admin.php?status=<?= e($key) ?>" <?= $status === $key ? 'aria-current="page"' : '' ?>>
                <?= e($label) ?> <span><?= (int) $stats[strtolower($key)] ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="queue-search">
            <?= icon('search') ?>
            <label class="visually-hidden" for="admin-queue-search">Tìm trong hàng chờ</label>
            <input id="admin-queue-search" class="form-control" type="search" placeholder="Tìm tên, mã hoặc cửa hàng…">
        </div>

        <div class="queue-list">
            <?php foreach ($coupons as $coupon): ?>
            <a class="queue-item"
                data-queue-item
                data-search="<?= e($coupon['merchant_name'] . ' ' . $coupon['title'] . ' ' . $coupon['code']) ?>"
                href="admin.php?<?= e(http_build_query(['status'=>$status,'id'=>$coupon['id']])) ?>#detail"
                <?= (int) ($selected['id'] ?? 0) === (int) $coupon['id'] ? 'aria-current="true"' : '' ?>>
                <span class="queue-item-top">
                    <span class="store-logo small"><?= e(textPrefix($coupon['merchant_name'])) ?></span>
                    <span class="store-name"><?= e($coupon['merchant_name']) ?></span>
                    <span class="badge-soft <?= match ($coupon['moderation_status']) { 'PENDING'=>'badge-pending', 'REJECTED'=>'badge-rejected', default=>'' } ?>">
                        <?= e(statusLabel($coupon['moderation_status'])) ?>
                    </span>
                </span>
                <span class="queue-item-title"><?= e($coupon['title']) ?></span>
                <span class="queue-item-bottom">
                    <span><strong><?= e($coupon['code']) ?></strong> · <?= e(saving($coupon)) ?></span>
                    <span><?= e(expiryLabel($coupon['ends_at'])) ?></span>
                </span>
            </a>
            <?php endforeach; ?>

            <?php if (!$coupons): ?>
            <div class="empty-state compact-empty">
                <div class="empty-symbol">✓</div>
                <h3>Danh sách đang trống</h3>
                <p><?= match ($status) { 'PENDING'=>'Mã do đối tác gửi sẽ xuất hiện tại đây.', 'APPROVED'=>'Các mã đã phê duyệt sẽ xuất hiện tại đây.', 'REJECTED'=>'Các mã bị từ chối sẽ xuất hiện tại đây.' } ?></p>
            </div>
            <?php endif; ?>
        </div>
        <p class="inline-empty" id="admin-filter-empty" hidden>Không tìm thấy mã phù hợp trong danh sách này.</p>
        <div class="queue-caption">Hiển thị tối đa 100 mã gần nhất trong trạng thái đang chọn.</div>
    </section>

    <section class="panel review-panel" id="detail">
        <div class="panel-head">
            <div>
                <div class="panel-kicker">CHI TIẾT KIỂM DUYỆT</div>
                <h2>Thông tin ưu đãi</h2>
            </div>
            <?php if ($selected): ?>
            <span class="badge-soft <?= match ($selected['moderation_status']) { 'PENDING'=>'badge-pending', 'REJECTED'=>'badge-rejected', default=>'' } ?>">
                <?= e(statusLabel($selected['moderation_status'])) ?>
            </span>
            <?php endif; ?>
        </div>

        <div class="panel-body">
            <?php if (!$selected): ?>
            <div class="empty-state compact-empty">
                <div class="empty-symbol">⌁</div>
                <h3>Chưa chọn mã</h3>
                <p>Chọn một mã trong danh sách bên trái để kiểm tra.</p>
            </div>
            <?php else: ?>
            <div class="review-store-row">
                <span class="store-logo"><?= e(textPrefix($selected['merchant_name'])) ?></span>
                <div>
                    <strong class="store-name"><?= e($selected['merchant_name']) ?></strong>
                    <div class="review-meta">
                        Đăng bởi <?= e($selected['author_name']) ?> · <?= e(sourceLabel($selected['source'])) ?>
                    </div>
                </div>
            </div>

            <h3 class="review-title"><?= e($selected['title']) ?></h3>
            <p class="review-description"><?= e($selected['description']) ?></p>

            <div class="review-code-box">
                <span>Mã ưu đãi</span>
                <strong><?= e($selected['code']) ?></strong>
                <span class="review-saving"><?= e(saving($selected)) ?></span>
            </div>

            <dl class="detail-list">
                <div>
                    <dt>Danh mục</dt>
                    <dd><?= e($selected['category_name']) ?></dd>
                </div>
                <div>
                    <dt>Đơn tối thiểu</dt>
                    <dd><?= e(money($selected['min_order_amount'])) ?></dd>
                </div>
                <div>
                    <dt>Giảm tối đa</dt>
                    <dd><?= $selected['max_discount_amount'] !== null ? e(money($selected['max_discount_amount'])) : 'Không giới hạn / không áp dụng' ?></dd>
                </div>
                <div>
                    <dt>Thời gian</dt>
                    <dd><?= e(day($selected['starts_at'])) ?> → <?= e(day($selected['ends_at'])) ?><br><small><?= e(expiryLabel($selected['ends_at'])) ?></small></dd>
                </div>
                <div>
                    <dt>Trạng thái cửa hàng</dt>
                    <dd><?= e(statusLabel($selected['merchant_status'])) ?></dd>
                </div>
                <div>
                    <dt>Kênh sử dụng</dt>
                    <dd><?= e(channelLabel($selected['usage_channel'])) ?></dd>
                </div>
            </dl>

            <div class="review-checklist">
                <div class="review-checklist-head">
                    <?= icon('shield') ?>
                    <div>
                        <h3>Điều kiện áp dụng</h3>
                        <p>Đọc kỹ trước khi đưa mã lên trang khám phá.</p>
                    </div>
                </div>
                <p class="review-description mb-0"><?= e($selected['terms']) ?></p>
            </div>

            <?php if ($selected['moderation_status'] === 'PENDING' && !$selected['can_approve']): ?>
            <div class="notice error mt-3">Chưa đủ điều kiện duyệt. Kiểm tra trạng thái cửa hàng, danh mục, thời hạn, trạng thái công khai và loại giảm giá.</div>
            <?php endif; ?>

            <?php if ($selected['moderation_status'] === 'APPROVED'): ?>
            <div class="notice mt-3 mb-0">
                <strong>Đã phê duyệt <?= e(dateTimeVi($selected['reviewed_at'])) ?>.</strong><br>
                Mã sẽ hiển thị trên trang khám phá khi đến ngày bắt đầu và vẫn còn hiệu lực.
            </div>
            <?php endif; ?>

            <?php if ($selected['moderation_status'] === 'REJECTED'): ?>
            <div class="notice error mt-3 mb-0">
                <strong>Đã từ chối <?= e(dateTimeVi($selected['reviewed_at'])) ?>.</strong><br>
                <?= e($selected['review_note'] ?: 'Chưa có lý do từ chối được ghi lại.') ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if ($selected && $selected['moderation_status'] === 'PENDING'): ?>
        <div class="panel-foot moderation-actions">
            <form method="post" action="admin.php" class="approval-action">
                <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
                <input type="hidden" name="coupon_id" value="<?= (int) $selected['id'] ?>">
                <input type="hidden" name="action" value="approve">
                <label class="review-confirm">
                    <input type="checkbox" name="review_confirm" value="1" required <?= !$selected['can_approve'] ? 'disabled' : '' ?>>
                    <span>Tôi đã kiểm tra nguồn đăng, giá trị ưu đãi, thời gian và điều kiện sử dụng.</span>
                </label>
                <button class="btn btn-brand" type="submit" <?= !$selected['can_approve'] ? 'disabled' : '' ?>>
                    <?= icon('check') ?> Phê duyệt mã
                </button>
            </form>
            <form method="post" action="admin.php" class="reject-action">
                <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
                <input type="hidden" name="coupon_id" value="<?= (int) $selected['id'] ?>">
                <input type="hidden" name="action" value="reject">
                <label for="review-note">Lý do từ chối</label>
                <textarea id="review-note" class="form-control" name="review_note" minlength="10" maxlength="1000" rows="3" required placeholder="Nêu rõ nội dung Merchant cần chỉnh sửa…"><?= e($reviewNote) ?></textarea>
                <button class="btn btn-danger-soft" type="submit"><?= icon('flag') ?> Từ chối mã</button>
            </form>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/layouts/workspace-end.php'; ?>

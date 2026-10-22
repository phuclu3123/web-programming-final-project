<div class="discovery-page">
    <div class="discovery-intro"><div><span class="eyebrow">CÙNG CỘNG ĐỒNG TIẾT KIỆM</span><h1>Những mã bạn đã chia sẻ.</h1><p>Theo dõi kết quả kiểm duyệt và tình trạng sử dụng của từng coupon.</p></div><a class="btn btn-brand" href="<?= e(url('/coupons/create')) ?>">Chia sẻ coupon <?= icon('arrow') ?></a></div>
    <?php if (!$coupons): ?><div class="member-empty"><strong>Bắt đầu bằng một mã giảm giá hữu ích.</strong><p>Coupon bạn gửi sẽ được kiểm tra trước khi công khai cho cộng đồng.</p></div><?php else: ?>
    <div class="contribution-list">
        <?php foreach ($coupons as $coupon):
            $moderation = $coupon['moderation_status'];
            $moderationLabel = ['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','REJECTED'=>'Bị từ chối'][$moderation] ?? $moderation;
        ?>
        <article class="contribution-card">
            <div><span class="eyebrow"><?= e($coupon['merchant_name']) ?> · <?= e($coupon['category_name']) ?></span><h2><?= e($coupon['title']) ?></h2><p><code><?= e($coupon['code'] ?: 'Ưu đãi không cần mã') ?></code> · <?= e(format_discount($coupon)) ?> · <?= e(usage_channel_label($coupon['usage_channel'])) ?></p>
                <div class="contribution-meta"><span>Đã gửi <?= e(format_db_datetime($coupon['created_at'])) ?></span><span>Hạn dùng <?= e(format_db_datetime($coupon['ends_at'])) ?></span><?php if ($moderation === 'APPROVED'): ?><span><?= e(coupon_status_label($coupon['effective_status'])) ?></span><?php endif; ?><?php if ($coupon['effective_status'] === 'ACTIVE'): ?><a href="<?= e(url('/coupons/' . (int)$coupon['id'])) ?>">Xem chi tiết ↗</a><?php endif; ?></div>
<?php if (!empty($coupon['review_note'])): ?>
    <div class="contribution-review">
        <strong><?= $moderation === 'REJECTED' ? 'Lý do từ chối' : 'Ghi chú kiểm duyệt' ?></strong>
        <div><?= nl2br(e($coupon['review_note'])) ?></div>
    </div>
<?php endif; ?>

<?php if (
    in_array($moderation, ['APPROVED', 'REJECTED'], true)
    && !empty($coupon['reviewed_at'])
): ?>
    <small class="text-muted d-block mt-2">
        <?= $moderation === 'REJECTED' ? 'Thời gian từ chối: ' : 'Thời gian duyệt: ' ?>
        <?= e(format_db_datetime($coupon['reviewed_at'])) ?>
    </small>
<?php endif; ?>            </div>
            <span class="contribution-state is-<?= e(strtolower($moderation)) ?>"><?= e($moderationLabel) ?></span>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

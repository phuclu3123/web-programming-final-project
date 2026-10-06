<?php $remainingDays = daysLeft($coupon['ends_at']); ?>
<article class="coupon-card">
    <div class="coupon-body">
        <div class="coupon-store">
            <span class="store-logo <?= ['','orange','blue','pink','purple'][(int) $coupon['merchant_id'] % 5] ?>">
                <?= e(textPrefix($coupon['merchant_name'])) ?>
            </span>
            <div class="coupon-store-copy">
                <div class="store-name"><?= e($coupon['merchant_name']) ?></div>
                <div class="store-category"><?= e($coupon['category_name']) ?> · Đã kiểm tra</div>
            </div>
            <span class="coupon-status" title="Ưu đãi đã được kiểm tra"><?= icon('check') ?></span>
        </div>

        <div class="coupon-value-block">
            <span class="coupon-value-label">MỨC TIẾT KIỆM</span>
            <div class="coupon-saving"><?= e(saving($coupon)) ?></div>
            <?php if ((float) $coupon['min_order_amount'] > 0): ?>
            <span class="coupon-qualifier">cho đơn từ <?= e(money($coupon['min_order_amount'])) ?></span>
            <?php else: ?>
            <span class="coupon-qualifier">không yêu cầu giá trị đơn tối thiểu</span>
            <?php endif; ?>
        </div>

        <h3><?= e($coupon['title']) ?></h3>
        <p class="coupon-description"><?= e($coupon['description']) ?></p>

        <div class="coupon-essentials">
            <?php if ($coupon['max_discount_amount'] !== null): ?>
            <span><?= icon('flag') ?> Tối đa <strong><?= e(money($coupon['max_discount_amount'])) ?></strong></span>
            <?php endif; ?>
            <span><?= icon('clock') ?> <strong><?= e(expiryLabel($coupon['ends_at'])) ?></strong></span>
        </div>

        <details class="coupon-terms">
            <summary>Xem điều kiện chi tiết</summary>
            <p><?= e($coupon['terms']) ?></p>
            <p>Kênh áp dụng: <?= e(channelLabel($coupon['usage_channel'])) ?>.</p>
        </details>
    </div>

    <div class="coupon-bottom">
        <span class="coupon-code"><small>MÃ ƯU ĐÃI</small><?= e($coupon['code']) ?></span>
        <button type="button" class="btn btn-outline-brand copy-code-button"
            data-copy-code="<?= e($coupon['code']) ?>" title="Sao chép mã <?= e($coupon['code']) ?>">
            <?= icon('copy') ?> <span>Sao chép</span>
        </button>
    </div>
</article>

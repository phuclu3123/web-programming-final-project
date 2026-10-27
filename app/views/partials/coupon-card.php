<?php $remainingDays = daysLeft($coupon['ends_at']); $isSaved = in_array((int)$coupon['id'], $savedCouponIds ?? [], true); ?>
<article class="coupon-card" <?= ($couponIndex ?? 0) >= 6 ? 'hidden' : '' ?>>
    <div class="coupon-photo"><img src="<?= e(couponPhoto($coupon)) ?>" alt="" width="720" height="480" loading="lazy"
            decoding="async"><span><?= e($coupon['category_name']) ?></span><?php if (str_contains($coupon['title'],'[DEMO]')): ?><small
            class="sample-tag">Minh họa</small><?php endif; ?></div>
    <div class="coupon-body">
        <div class="coupon-store">
            <span class="store-logo <?= ['','orange','blue','pink','purple'][(int) $coupon['merchant_id'] % 5] ?>">
                <?= brandLogo($coupon['merchant_name']) ?>
            </span>
            <div class="coupon-store-copy">
                <div class="store-name"><?= e($coupon['merchant_name']) ?></div>
                <div class="store-category"><?= e($coupon['category_name']) ?> ·
                    <?= e(channelLabel($coupon['usage_channel'])) ?></div>
            </div>
            <?php if ($currentUser && in_array($currentUser['role'], ['USER','MERCHANT'], true)): ?>
            <form method="post" action="<?= e(url('/saved-coupons/' . (int)$coupon['id'] . '/save')) ?>"
                class="discovery-save-form" data-save-form data-coupon-id="<?= (int)$coupon['id'] ?>">
                <input type="hidden" name="csrf" value="<?= e(\App\Core\Session::token()) ?>"><input type="hidden"
                    name="saved" value="<?= $isSaved ? '0' : '1' ?>">
                <button class="discovery-save <?= $isSaved ? 'is-saved' : '' ?>" type="submit"
                    aria-pressed="<?= $isSaved ? 'true' : 'false' ?>"
                    aria-label="<?= e(($isSaved ? 'Bỏ lưu ' : 'Lưu ') . $coupon['title']) ?>"
                    title="<?= $isSaved ? 'Đã lưu coupon' : 'Lưu coupon' ?>"><?= icon('bookmark') ?></button>
            </form>
            <?php elseif (!$currentUser): ?>
            <a class="discovery-save"
                href="<?= e(url('/login') . '?return=' . rawurlencode(url('/coupons/' . (int)$coupon['id']))) ?>"
                aria-label="Đăng nhập để lưu coupon" title="Đăng nhập để lưu coupon"><?= icon('bookmark') ?></a>
            <?php else: ?><span class="coupon-status" title="Đã được duyệt"><?= icon('check') ?></span><?php endif; ?>
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

        <h3><a href="<?= e(url('/coupons/' . (int)$coupon['id'])) ?>"><?= e(editorialTitle($coupon['title'])) ?></a>
        </h3>
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
        <?php if (!empty($coupon['code'])): ?>
        <span class="coupon-code"><small>MÃ ƯU ĐÃI</small><?= e($coupon['code']) ?></span>
        <button type="button" class="btn btn-outline-brand copy-code-button" data-copy-code="<?= e($coupon['code']) ?>"
            data-coupon-id="<?= (int)$coupon['id'] ?>" title="Sao chép mã <?= e($coupon['code']) ?>">
            <?= icon('copy') ?> <span>Sao chép</span>
        </button>
        <?php else: ?><a class="btn btn-outline-brand" href="<?= e(url('/coupons/' . (int)$coupon['id'])) ?>">Xem ưu đãi
            <?= icon('arrow') ?></a><?php endif; ?>
    </div>
    <a class="discovery-detail-link" href="<?= e(url('/coupons/' . (int)$coupon['id'])) ?>">Chi tiết & xác minh cộng
        đồng <?= icon('arrow') ?></a>
</article>
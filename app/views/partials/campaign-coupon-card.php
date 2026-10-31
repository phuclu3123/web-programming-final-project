<article class="campaign-deal">
    <div class="campaign-deal-brand">
        <?= brandLogo($previewCoupon['merchant_name']) ?><span><?= e($previewCoupon['merchant_name']) ?></span><?php if (str_contains($previewCoupon['title'],'[DEMO]')): ?><small>Minh
            họa</small><?php endif; ?></div>
    <strong class="campaign-deal-value"><?= e(saving($previewCoupon)) ?></strong>
    <h3><a
            href="<?= e(url('/coupons/'.(int)$previewCoupon['id'])) ?>"><?= e(editorialTitle($previewCoupon['title'])) ?></a>
    </h3>
    <dl class="campaign-deal-conditions">
        <div>
            <dt>Đơn tối thiểu</dt>
            <dd><?= (float)$previewCoupon['min_order_amount']>0 ? e(money($previewCoupon['min_order_amount'])) : 'Không yêu cầu' ?>
            </dd>
        </div>
        <div>
            <dt>Kênh dùng</dt>
            <dd><?= e(channelLabel($previewCoupon['usage_channel'])) ?></dd>
        </div><?php if ($previewCoupon['max_discount_amount']!==null): ?><div>
            <dt>Giảm tối đa</dt>
            <dd><?= e(money($previewCoupon['max_discount_amount'])) ?></dd>
        </div><?php endif; ?><div>
            <dt>Hạn dùng</dt>
            <dd><?= e(format_db_datetime($previewCoupon['ends_at'],'d/m/Y H:i')) ?></dd>
        </div>
    </dl>
    <div class="campaign-deal-footer"><code><?= e($previewCoupon['code'] ?: 'Không cần mã') ?></code><a
            href="<?= e(url('/coupons/'.(int)$previewCoupon['id'])) ?>">Lấy mã <?= icon('arrow') ?></a></div>
</article>
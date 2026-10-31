<?php if (!empty($brandMerchants)): ?>
<section id="<?= e($brandSectionId ?? 'brands') ?>"
    class="brand-section <?= count($brandMerchants)<5 ? 'brand-static' : '' ?>"
    aria-labelledby="<?= e(($brandSectionId ?? 'brands').'-title') ?>">
    <div class="editorial-section-head">
        <div><span class="eyebrow">KHÁM PHÁ THEO THƯƠNG HIỆU</span>
            <h2 id="<?= e(($brandSectionId ?? 'brands').'-title') ?>">Những cái tên quen thuộc.</h2>
            <p>Chọn cửa hàng để xem các ưu đãi đang có trong CouponHub.</p>
        </div>
    </div>
    <div class="brand-window">
        <div class="brand-track">
            <?php for ($copy=0;$copy<2;$copy++): ?>
            <div class="brand-group" <?= $copy ? 'aria-hidden="true"' : '' ?>>
                <?php foreach ($brandMerchants as $brand): ?>
                <a class="brand-tile" href="<?= e(url('/coupons').'?merchant_id='.(int)$brand['id']) ?>"
                    <?= $copy ? 'tabindex="-1"' : '' ?>><?= brandLogo($brand['name']) ?><span><?= e($brand['name']) ?></span></a>
                <?php endforeach; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>
<?php endif; ?>
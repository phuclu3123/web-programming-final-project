<?php
$heroCoupons=$featuredCoupon ? [$featuredCoupon] : [];
$heroBrands=$featuredCoupon ? [slugify($featuredCoupon['merchant_name'])=>true] : [];
foreach (['grab','traveloka','fpt-shop','lazada','shopee','highlands-coffee','highlands','hasaki','cgv','tiki'] as $heroBrand) {
    if (isset($heroBrands[$heroBrand])) continue;
    foreach ($coupons as $candidate) {
        if (slugify($candidate['merchant_name'])!==$heroBrand) continue;
        $heroCoupons[]=$candidate;
        $heroBrands[$heroBrand]=true;
        break;
    }
    if (count($heroCoupons)>=10) break;
}
$heroCount=count($heroCoupons);
?>
<div class="hero-art studio-hero-art hero-carousel" data-hero-carousel role="region" aria-roledescription="băng chuyền" aria-label="Ưu đãi nổi bật">
    <?php foreach ($heroCoupons as $heroIndex=>$heroCoupon): $heroMedia=couponHeroMedia($heroCoupon); ?>
    <div class="hero-slide" data-hero-slide data-coupon-id="<?= (int)$heroCoupon['id'] ?>" data-merchant="<?= e($heroCoupon['merchant_name']) ?>" role="group" aria-roledescription="trang" aria-label="<?= e(($heroIndex+1).' / '.$heroCount.' — '.$heroCoupon['merchant_name']) ?>" <?= $heroIndex ? 'hidden' : '' ?>>
        <img class="studio-hero-photo" src="<?= e($heroMedia['src']) ?>" <?= $heroMedia['srcset'] ? 'srcset="'.e($heroMedia['srcset']).'" sizes="(max-width:600px) calc(100vw - 32px), (max-width:900px) calc(100vw - 40px), (max-width:1344px) calc(100vw - 64px), 1280px"' : '' ?> alt="" width="<?= (int)$heroMedia['width'] ?>" height="<?= (int)$heroMedia['height'] ?>" loading="<?= $heroIndex ? 'lazy' : 'eager' ?>" fetchpriority="<?= $heroIndex ? 'low' : 'high' ?>" decoding="async">
        <span class="studio-photo-credit"><?= e($heroCoupon['merchant_name']) ?> / Ưu đãi đang có<?= str_contains($heroCoupon['title'],'[DEMO]') ? ' · Minh họa' : '' ?></span>
        <div class="hero-stage"><article class="hero-feature" aria-label="<?= e('Ưu đãi '.$heroCoupon['merchant_name']) ?>">
            <div class="hero-feature-main">
                <span class="hero-feature-merchant"><?= brandLogo($heroCoupon['merchant_name']) ?><?= e($heroCoupon['merchant_name']) ?></span>
                <div class="hero-feature-saving <?= mb_strlen(saving($heroCoupon))>10 ? 'hero-saving-long' : '' ?>"><?= e(saving($heroCoupon)) ?></div>
                <h2><a href="<?= e(url('/coupons/'.(int)$heroCoupon['id'])) ?>"><?= e(editorialTitle($heroCoupon['title'])) ?></a></h2>
                <?php if ($heroCoupon['max_discount_amount']!==null): ?><span class="hero-discount-cap">Giảm tối đa <?= e(money($heroCoupon['max_discount_amount'])) ?></span><?php endif; ?>
            </div>
            <div class="hero-feature-bottom"><span><?= (float)$heroCoupon['min_order_amount']>0 ? 'Đơn từ '.e(money($heroCoupon['min_order_amount'])).'<br>' : 'Không yêu cầu đơn tối thiểu<br>' ?><?= e(expiryLabel($heroCoupon['ends_at'])) ?></span>
                <?php if (!empty($heroCoupon['code'])): ?><button type="button" class="hero-feature-copy" data-copy-code="<?= e($heroCoupon['code']) ?>" data-coupon-id="<?= (int)$heroCoupon['id'] ?>"><?= icon('copy') ?><span>Sao chép mã</span></button><?php else: ?><a class="hero-feature-copy" href="<?= e(url('/coupons/'.(int)$heroCoupon['id'])) ?>">Xem ưu đãi <?= icon('arrow') ?></a><?php endif; ?>
            </div>
        </article></div>
    </div>
    <?php endforeach; ?>
    <?php if (!$heroCount): ?><div class="hero-stage"><div class="hero-feature hero-feature-empty"><strong>CouponHub</strong><span>Thử từ khóa hoặc nhu cầu khác để tìm ưu đãi phù hợp.</span></div></div><?php endif; ?>
    <?php if ($heroCount>1): ?>
    <button type="button" class="hero-carousel-arrow hero-carousel-prev" data-hero-prev aria-label="Coupon trước" hidden><?= icon('arrow') ?></button>
    <button type="button" class="hero-carousel-arrow hero-carousel-next" data-hero-next aria-label="Coupon tiếp theo" hidden><?= icon('arrow') ?></button>
    <div class="hero-carousel-toolbar" data-hero-toolbar hidden><span class="hero-carousel-counter"><strong data-hero-current>01</strong><span>/ <?= str_pad((string)$heroCount,2,'0',STR_PAD_LEFT) ?></span></span><div class="hero-carousel-dots" aria-label="Chọn coupon"><?php foreach ($heroCoupons as $heroIndex=>$heroCoupon): ?><button type="button" data-hero-go="<?= $heroIndex ?>" aria-label="<?= e('Xem coupon '.($heroIndex+1).' — '.$heroCoupon['merchant_name']) ?>" <?= !$heroIndex ? 'aria-current="true"' : '' ?>></button><?php endforeach; ?></div></div>
    <p class="visually-hidden" data-hero-status role="status" aria-live="polite" aria-atomic="true"></p>
    <?php endif; ?>
</div>

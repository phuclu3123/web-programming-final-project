<?php
[$campaignPhoto,$campaignTone]=campaignArtwork($campaign);
$campaignImage=url('/assets/images/hero/category-'.($campaignPhoto==='fashion' ? 'fashion' : ($campaignPhoto==='travel' ? 'travel' : 'technology')).'.webp');
$timing=campaignTiming($campaign);
$campaignEnded=time()>=strtotime($campaign['ends_at'].' UTC');
?>
<div class="discovery-page campaign-reading">
    <div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><a href="<?= e(url('/campaigns')) ?>">Chiến dịch</a><span>/</span><span><?= e($campaign['merchant_name'] ?: 'CouponHub') ?></span></div>
    <header class="campaign-detail-cover campaign-detail-<?= e($campaignTone) ?>">
        <img src="<?= e($campaignImage) ?>" alt="" width="2560" height="1707" fetchpriority="high" decoding="async">
        <div class="campaign-detail-copy"><span class="campaign-detail-brand"><?= brandLogo($campaign['merchant_name'] ?: 'CouponHub') ?><?= e($campaign['merchant_name'] ?: 'CouponHub') ?><span><?= $campaignEnded ? 'Đã kết thúc' : ($timing['upcoming'] ? 'Sắp bắt đầu' : 'Đang diễn ra') ?></span></span>
        <h1><?= e(editorialTitle($campaign['name'])) ?></h1>
        <?php if (str_contains($campaign['name'],'[DEMO]')): ?><span class="campaign-demo-note">Chiến dịch minh họa · Ưu đãi dùng trong bản trải nghiệm CouponHub</span><?php elseif ($campaign['description']): ?><p><?= e($campaign['description']) ?></p><?php endif; ?>
        <?php require __DIR__.'/../partials/campaign-countdown.php'; ?>
        <a class="btn campaign-detail-action" href="#campaign-offers">Xem <?= count($coupons) ?> ưu đãi <?= icon('arrow') ?></a></div>
    </header>
    <div class="campaign-date-strip"><span><small>Bắt đầu</small><time><?= e(format_db_datetime($campaign['starts_at'],'d/m/Y · H:i')) ?></time></span><span><small>Kết thúc</small><time><?= e(format_db_datetime($campaign['ends_at'],'d/m/Y · H:i')) ?></time></span><p>Giờ Việt Nam. Mỗi coupon có điều kiện và hạn dùng riêng.</p></div>
    <section id="campaign-offers" class="campaign-offers-section"><div class="studio-section-title"><div><span class="eyebrow">TRONG CHIẾN DỊCH NÀY</span><h2>Chọn mã cho kế hoạch của bạn.</h2></div><span><?= count($coupons) ?> ưu đãi còn hiệu lực</span></div>
    <?php if (!$coupons): ?><div class="member-empty"><strong>Chưa có coupon còn hiệu lực.</strong><p>Khám phá các mã đang có trong lúc chờ đợt ưu đãi tiếp theo.</p><a class="btn btn-outline-brand" href="<?= e(url('/coupons')) ?>">Khám phá coupon <?= icon('arrow') ?></a></div><?php else: ?>
    <div class="campaign-deal-grid"><?php foreach ($coupons as $previewCoupon): require __DIR__.'/../partials/campaign-coupon-card.php'; endforeach; ?></div>
    <?php endif; ?></section>
    <a class="campaign-back-link" href="<?= e(url('/campaigns')) ?>">Tất cả chiến dịch <?= icon('arrow') ?></a>
</div>

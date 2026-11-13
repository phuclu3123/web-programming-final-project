<?php
// Thẻ chiến dịch dùng chung nhận ảnh, nhãn và đường dẫn từ dữ liệu trình bày.
$visual=campaignVisual($campaign);
$timing=campaignTiming($campaign);
$ended=time() >= $timing['end']/1000;
$isDemo=str_contains($campaign['name'],'[DEMO]');
$detailMode=$detailMode ?? false;
$campaignHref=$detailMode ? '#campaign-offers' : url('/campaigns/'.(int)$campaign['id']);
$campaignLabel=$campaign['merchant_name'] ?: 'CouponHub tuyển chọn';
$featureTitle=editorialTitle($campaign['name']);
if (!empty($campaign['merchant_name']) && str_starts_with($featureTitle,$campaign['merchant_name'].' · ')) $featureTitle=substr($featureTitle,strlen($campaign['merchant_name'].' · '));
?>
<article class="event-feature event-tone-<?= e($visual['tone']) ?> <?= $detailMode ? 'event-feature-detail' : '' ?>" data-phase="<?= $ended ? 'ended' : ($timing['upcoming'] ? 'upcoming' : 'active') ?>">
    <div class="event-feature-copy">
        <div class="event-identity"><?= brandLogo($campaign['merchant_name'] ?: 'CouponHub') ?><span><?= e($campaignLabel) ?></span><span class="event-status"><?= $ended ? 'Đã kết thúc' : ($timing['upcoming'] ? 'Sắp bắt đầu' : 'Đang diễn ra') ?></span></div>
        <?= $detailMode ? '<h1>' : '<h2>' ?><?= e($featureTitle) ?><?= $detailMode ? '</h1>' : '</h2>' ?>
        <?php if ($isDemo && (empty($campaign['description']) || str_starts_with($campaign['description'],'[DEMO]'))): ?>
            <p>Khám phá các mã trong bộ sưu tập. Xem mức giảm, đơn tối thiểu và kênh áp dụng trước khi chọn.</p>
        <?php elseif (!empty($campaign['description'])): ?><p><?= e($campaign['description']) ?></p><?php endif; ?>
        <?php if (isset($campaign['coupon_count']) || $detailMode): ?><div class="event-availability"><strong><?= $detailMode ? (int)$campaignOverview['count'] : (int)$campaign['coupon_count'] ?></strong><span>coupon còn hiệu lực<?= !$detailMode && (!empty($selectedCategory) || !empty($selectedPlatform) || ($selectedChannel ?? 'ALL')!=='ALL') ? ' phù hợp bộ lọc' : '' ?></span></div><?php endif; ?>
        <?php require __DIR__.'/campaign-countdown.php'; ?>
        <div class="event-feature-bottom"><div><small><?= $timing['upcoming'] ? 'Bắt đầu' : 'Đến ngày' ?></small><time datetime="<?= e(gmdate('c',($timing['upcoming'] ? $timing['start'] : $timing['end'])/1000)) ?>"><?= e(format_db_datetime($timing['upcoming'] ? $campaign['starts_at'] : $campaign['ends_at'],'d.m.Y')) ?></time></div><a class="btn event-feature-action <?= $detailMode ? 'campaign-detail-action' : '' ?>" data-campaign-open href="<?= e($campaignHref) ?>"><?= $detailMode ? 'Chọn coupon' : 'Xem chiến dịch' ?> <?= icon('arrow') ?></a></div>
        <?php if ($isDemo): ?><small class="event-demo"><?= $detailMode ? 'Bản trải nghiệm · Coupon minh họa, không dùng thanh toán.' : 'Coupon minh họa · Không dùng thanh toán' ?></small><?php endif; ?>
    </div>
    <figure class="event-feature-media <?= isset($visual['secondary']) ? 'event-collage' : '' ?> event-media-<?= e($visual['layout'] ?? 'photo') ?>">
        <img src="<?= e($visual['image']) ?>" alt="<?= e($visual['credit'] ? 'Hình ảnh thương hiệu '.$visual['credit'] : '') ?>" width="1920" height="1280" <?= !empty($posterPriority) ? 'fetchpriority="high"' : 'loading="lazy"' ?> decoding="async">
        <a class="event-media-open" href="<?= e($campaignHref) ?>" aria-label="<?= e('Xem chiến dịch '.$featureTitle) ?>"></a>
        <?php if (isset($visual['secondary'])): ?><img src="<?= e($visual['secondary']) ?>" alt="<?= ($visual['layout'] ?? '')==='products' ? 'Sản phẩm chăm sóc da tại Hasaki' : 'Bộ sưu tập Coolmate' ?>" width="1200" height="800" loading="lazy" decoding="async"><?php endif; ?>
        <?php if (($visual['layout'] ?? '')==='identity'): ?><span class="event-identity-word" aria-hidden="true">Một ly trà.<br>Một cuộc hẹn.</span><?php endif; ?>
        <?php if ($visual['credit']): ?><figcaption><?= ($visual['layout'] ?? '')==='identity' ? 'Nhận diện: ' : 'Ảnh: ' ?><a href="<?= e($visual['source']) ?>" target="_blank" rel="noopener noreferrer"><?= e(($visual['layout'] ?? '')==='collage' ? 'Grab' : $visual['credit']) ?></a><?php if (($visual['layout'] ?? '')==='collage'): ?> · <a href="https://www.coolmate.me/" target="_blank" rel="noopener noreferrer">Coolmate</a> · Minh họa bộ sưu tập<?php endif; ?><?= $visual['tone']==='berry' ? ' · Tư liệu 11.11/2025' : '' ?></figcaption><?php elseif (!empty($visual['caption'])): ?><figcaption><?= e($visual['caption']) ?></figcaption><?php endif; ?>
    </figure>
</article>

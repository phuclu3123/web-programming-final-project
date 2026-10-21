<?php
[$posterPhoto, $posterTone] = campaignArtwork($campaign);
$upcoming = strtotime($campaign['starts_at'] . ' UTC') > time();
$objectPoster = empty($isMainPoster) && $posterPhoto === 'technology';
$posterImage = $objectPoster ? url('/assets/images/editorial/headphones-cutout.webp') : editorialPhoto($posterPhoto, false);
?>

<article class="campaign-poster campaign-art-poster poster-<?= e($posterTone) ?> <?= !empty($isMainPoster) ? 'poster-main' : 'poster-small' ?> <?= $objectPoster ? 'poster-object' : '' ?>">
    <!-- Ảnh chụp chiến dịch -->
    <img class="campaign-poster-photo" src="<?= e($posterImage) ?>" alt="" width="<?= $objectPoster ? '479' : '1200' ?>" height="<?= $objectPoster ? '499' : '800' ?>" loading="<?= !empty($posterPriority) ? 'eager' : 'lazy' ?>" decoding="async">
    
    <div class="poster-scenery" aria-hidden="true"><span></span><span></span></div>

    <div class="campaign-poster-content">
        <!-- Badges thương hiệu & trạng thái -->
        <div class="poster-badges">
            <span class="poster-phase"><?= icon('clock') ?><?= $upcoming ? 'Sắp bắt đầu' : 'Đang diễn ra' ?></span>
            <span class="poster-merchant"><?= brandLogo($campaign['merchant_name'] ?: 'CouponHub') ?><?= e($campaign['merchant_name'] ?: 'CouponHub') ?></span>
            <?php if (str_contains($campaign['name'], '[DEMO]')): ?>
                <span class="poster-demo">Minh họa</span>
            <?php endif; ?>
        </div>

        <h3><?= e(editorialTitle($campaign['name'])) ?></h3>
        
        <?php if (!empty($isMainPoster) && !empty($campaign['description'])): ?>
            <p><?= e($campaign['description']) ?></p>
        <?php endif; ?>

        <!-- Đồng hồ đếm ngược -->
        <?php require __DIR__ . '/campaign-countdown.php'; ?>

        <!-- Nút bấm và thời gian diễn ra -->
        <div class="poster-actions">
            <a class="btn" data-campaign-open href="<?= e(url('/campaigns/' . (int)$campaign['id'])) ?>">
                Khám phá ngay <?= icon('arrow') ?>
            </a>
            <span>
                <?= e(format_db_datetime($campaign['starts_at'], 'H:i d/m')) ?><br>
                → <?= e(format_db_datetime($campaign['ends_at'], 'H:i d/m/Y')) ?>
            </span>
        </div>
    </div>
</article>
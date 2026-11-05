
<?php
[$campaignPhoto, $campaignTone] = campaignArtwork($campaign);

$defaultCampaignImage = url(
    '/assets/images/hero/category-' .
    ($campaignPhoto === 'fashion'
        ? 'fashion'
        : ($campaignPhoto === 'travel' ? 'travel' : 'technology')) .
    '.webp'
);
$campaignImage=campaignBannerUrl($campaign) ?? $defaultCampaignImage;

$timing = campaignTiming($campaign);
$campaignEnded = time() >= strtotime($campaign['ends_at'] . ' UTC');
?>

<div class="discovery-page campaign-reading">

    <div class="member-breadcrumb">
        <a href="<?= e(url('/')) ?>">CouponHub</a>
        <span>/</span>
        <a href="<?= e(url('/campaigns')) ?>">Chiến dịch</a>
        <span>/</span>
        <span><?= e($campaign['merchant_name'] ?: 'CouponHub') ?></span>
    </div>

    <header class="campaign-detail-cover campaign-detail-<?= e($campaignTone) ?>">
        <img
            src="<?= e($campaignImage) ?>"
            data-campaign-fallback="<?= e($defaultCampaignImage) ?>" referrerpolicy="no-referrer"
            alt=""
            width="2560"
            height="1707"
            fetchpriority="high"
            decoding="async"
        >

        <div class="campaign-detail-copy">
            <span class="campaign-detail-brand">
                <?= brandLogo($campaign['merchant_name'] ?: 'CouponHub') ?>
                <?= e($campaign['merchant_name'] ?: 'CouponHub') ?>

                <span>
                    <?= $campaignEnded
                        ? 'Đã kết thúc'
                        : ($timing['upcoming'] ? 'Sắp bắt đầu' : 'Đang diễn ra') ?>
                </span>
            </span>

            <h1><?= e(editorialTitle($campaign['name'])) ?></h1>

            <?php if (str_contains($campaign['name'], '[DEMO]')): ?>
                <span class="campaign-demo-note">
                    Chiến dịch minh họa · Ưu đãi dùng trong bản trải nghiệm CouponHub
                </span>
            <?php elseif ($campaign['description']): ?>
                <p><?= e($campaign['description']) ?></p>
            <?php endif; ?>

            <?php require __DIR__ . '/../partials/campaign-countdown.php'; ?>

            <a class="btn campaign-detail-action" href="#campaign-offers">
                Xem <?= count($coupons) ?> ưu đãi <?= icon('arrow') ?>
            </a>
        </div>
    </header>

    <div class="campaign-date-strip">
        <span>
            <small>Bắt đầu</small>
            <time>
                <?= e(format_db_datetime($campaign['starts_at'], 'd/m/Y · H:i')) ?>
            </time>
        </span>

        <span>
            <small>Kết thúc</small>
            <time>
                <?= e(format_db_datetime($campaign['ends_at'], 'd/m/Y · H:i')) ?>
            </time>
        </span>

        <p>Giờ Việt Nam. Mỗi coupon có điều kiện và hạn dùng riêng.</p>
    </div>

    <section id="campaign-offers" class="campaign-offers-section">

    <div class="studio-section-title">
        <div>
            <span class="eyebrow">TRONG CHIẾN DỊCH NÀY</span>
            <h2>Chọn mã cho kế hoạch của bạn.</h2>
        </div>

        <span><?= count($coupons) ?> ưu đãi còn hiệu lực</span>
    </div>

    

    <form id="campaign-coupon-filter" class="campaign-coupon-filters" method="get" action="<?= e(url('/campaigns/'.(int)$campaign['id'])) ?>#campaign-offers">
        <div class="campaign-coupon-filter-field"><label for="campaign-category">Danh mục coupon</label><select name="category_id" id="campaign-category"><option value="0">Tất cả danh mục</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= $selectedCategory===(int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
        <div class="campaign-coupon-filter-field"><label for="campaign-channel">Kênh sử dụng</label><select name="usage_channel" id="campaign-channel"><?php foreach (['ALL'=>'Tất cả kênh','ONLINE'=>'Online','OFFLINE'=>'Offline','BOTH'=>'Online và Offline'] as $key=>$label): ?><option value="<?= $key ?>" <?= $selectedChannel===$key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
        <div class="campaign-coupon-filter-field"><?php $platformSelectId='campaign-platform'; require __DIR__.'/../partials/campaign-platform-filter.php'; ?></div>
        <div class="campaign-coupon-filter-actions"><button type="submit" class="campaign-coupon-filter-submit">Lọc coupon</button><a href="<?= e(url('/campaigns/'.(int)$campaign['id'])) ?>#campaign-offers" id="reset-campaign-filter" class="campaign-coupon-filter-reset">Đặt lại</a></div>
    </form>
    <p class="campaign-filter-feedback" role="status" hidden></p>
    <!-- Khu vực được cập nhật bằng AJAX -->
    <div id="campaign-coupon-results" aria-live="polite">

        <?php if (!$coupons): ?>

            <div class="member-empty">
                <strong><?= $campaignEnded ? 'Chiến dịch đã kết thúc.' : 'Không tìm thấy coupon phù hợp.' ?></strong>
                <p><?= $campaignEnded ? 'Ưu đãi còn hạn vẫn có thể được tìm thấy ngoài chiến dịch.' : 'Thử chọn danh mục, nền tảng hoặc kênh sử dụng khác nhé.' ?></p>
                <?php if ($campaignEnded): ?><a href="<?= e(url('/coupons')) ?>">Khám phá coupon còn hiệu lực <?= icon('arrow') ?></a><?php endif; ?>
            </div>

        <?php else: ?>

            <p class="text-muted">
                <?= count($coupons) ?> ưu đãi phù hợp
            </p>

            <div class="campaign-deal-grid">
                <?php
                foreach ($coupons as $previewCoupon):
                    require __DIR__ . '/../partials/campaign-coupon-card.php';
                endforeach;
                ?>
            </div>

        <?php endif; ?>

    </div>
</section>

    <a class="campaign-back-link" href="<?= e(url('/campaigns')) ?>">
        Tất cả chiến dịch <?= icon('arrow') ?>
    </a>

</div>

<script src="<?= e(url('/assets/js/csp/campaign-filter.js')) ?>" defer></script>

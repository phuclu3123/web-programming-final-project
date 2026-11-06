
<?php if ($campaignPreview): ?>
<section id="campaign-deals"
         class="campaign-deals-section"
         aria-labelledby="campaign-deals-title"
         data-campaign-preview="<?= (int) $campaignPreview['id'] ?>">

    <div class="studio-section-title">
        <div>
            <span class="eyebrow">ĐI TỪ CHIẾN DỊCH ĐẾN ƯU ĐÃI</span>
            <h2 id="campaign-deals-title">Có mã gì cho bạn?</h2>
            <p>Chọn thương hiệu để xem mã và điều kiện áp dụng.</p>
        </div>

        <a href="<?= e(url('/campaigns/' . (int) $campaignPreview['id']).'?'.http_build_query(['category_id'=>$selectedCategory,'usage_channel'=>$selectedChannel,'platform'=>$selectedPlatform])) ?>">
            Xem toàn bộ chiến dịch <?= icon('arrow') ?>
        </a>
    </div>

    <nav class="campaign-deal-tabs" aria-label="Chọn chiến dịch để xem coupon">
        <?php foreach ($listing['items'] as $previewOption): ?>
            <a
                href="<?= e(url('/campaigns') . '?' . http_build_query([
                    'phase' => $phase,
                    'page' => $listing['page'],
                    'preview' => (int) $previewOption['id'],
                    'category_id' => $selectedCategory ?? 0,
                    'usage_channel' => $selectedChannel ?? 'ALL',
                    'platform' => $selectedPlatform ?? 0,
                ])) ?>#campaign-deals"
                <?= (int) $previewOption['id'] === (int) $campaignPreview['id']
                    ? 'aria-current="page"'
                    : '' ?>
            >
                <?= brandLogo($previewOption['merchant_name'] ?: 'CouponHub') ?>
                <span><?= e($previewOption['merchant_name'] ?: editorialTitle($previewOption['name'])) ?></span>
                <small><?= (int) $previewOption['coupon_count'] ?> mã</small>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($campaignCoupons): ?>
        <div class="campaign-deal-grid <?= count($campaignCoupons) === 1
            ? 'campaign-deal-grid-single'
            : '' ?>">
            <?php foreach ($campaignCoupons as $previewCoupon): ?>
                <?php require __DIR__ . '/campaign-coupon-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <p class="campaign-deal-note">
            <?= icon('clock') ?>
            Ngày giờ theo giờ Việt Nam. Hạn của coupon có thể khác ngày kết thúc chiến dịch.
        </p>
    <?php else: ?>
        <div class="member-empty">
            <strong>Không tìm thấy coupon phù hợp.</strong>
            <p>Thử chọn danh mục hoặc kênh sử dụng khác để xem thêm ưu đãi.</p>
            <a
                href="<?= e(url('/campaigns') . '?' . http_build_query([
                    'phase' => $phase,
                    'page' => $listing['page'],
                    'preview' => (int) $campaignPreview['id'],
                ])) ?>#campaign-deals"
            >
                Xóa bộ lọc <?= icon('arrow') ?>
            </a>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

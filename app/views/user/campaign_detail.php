<?php
require_once __DIR__ . '/data/coupons.php';

$campaignId = $_GET['id'] ?? 'sale-hang-thang';
$campaign = findCampaign($campaignId);

if (!$campaign) {
    $campaigns = allCampaigns();
    $campaign = reset($campaigns);
}

$pageTitle = htmlspecialchars($campaign['title']) . ' - CouponHub';
$activePage = 'campaigns';

$campaignCoupons = getCampaignCoupons($campaign['id']);

require_once __DIR__ . '/layouts/header.php';
?>

<main class="coupon-detail-page">
    <main class="coupon-detail-page">
    <!-- ĐÃ BỎ INLINE STYLE GRADIENT ĐỂ CHẠY THEO CSS CHUẨN -->
    <div class="campaign-hero">
        <span class="detail-eyebrow">
            <?= htmlspecialchars($campaign['badge']) ?> · CHIẾN DỊCH KHUYẾN MÃI
        </span>
        <h1><?= htmlspecialchars($campaign['title']) ?></h1>
        <p><?= htmlspecialchars($campaign['description']) ?></p>
        
        <p class="campaign-date-text">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            Thời gian diễn ra: <strong><?= $campaign['start_date'] ?> - <?= $campaign['end_date'] ?></strong>
        </p>
    </div>

    <section class="category-results" style="margin-top: 32px;">
        <div class="category-section-heading">
            <div class="heading-text-group">
                <p><?= count($campaignCoupons) ?> ưu đãi sẵn sàng áp dụng</p>
            </div>
        </div>

        <?php if (empty($campaignCoupons)): ?>
            <div class="category-empty">
                <h3>Chưa có mã giảm giá cho chiến dịch này</h3>
                <p>Vui lòng quay lại sau hoặc tham khảo các đợt khuyến mãi khác.</p>
                <a href="/app/views/user/campaigns.php">Xem chiến dịch khác</a>
            </div>
        <?php else: ?>
            <div class="category-coupon-list">
                <?php foreach ($campaignCoupons as $coupon): ?>
                    <article class="category-coupon-card">
                        <div class="category-coupon-top">
                            <span class="category-store-name"><?= htmlspecialchars($coupon['store']) ?></span>
                            <span class="category-status"><?= htmlspecialchars($coupon['status']) ?></span>
                        </div>

                        <span class="category-coupon-discount"><?= htmlspecialchars($coupon['discount']) ?></span>
                        <h3><?= htmlspecialchars($coupon['title']) ?></h3>

                        <div class="category-coupon-bottom">
                            <span>Hạn dùng: <?= date('d/m/Y', strtotime($coupon['expiry'])) ?></span>
                            <a href="/app/views/user/coupon_detail.php?code=<?= urlencode($coupon['code']) ?>" class="category-view-button">
                                Xem ưu đãi
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
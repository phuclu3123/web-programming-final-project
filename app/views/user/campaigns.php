<?php
$pageTitle = 'Chiến dịch khuyến mãi - CouponHub';
$activePage = 'campaigns';

require_once __DIR__ . '/data/coupons.php';

$campaigns = array_values(allCampaigns());

require_once __DIR__ . '/layouts/header.php';
?>

<div class="category-page">
    <section class="category-heading">
        <div class="heading-text-group">
            <span class="category-eyebrow">COUPONHUB CAMPAIGNS</span>
            <h1>Chiến dịch khuyến mãi</h1>
            <p>Tổng hợp các sự kiện và đợt giảm giá lớn nhất trong tháng.</p>
        </div>
    </section>

    <div class="campaign-grid" style="margin-top: 32px;">
        <?php foreach ($campaigns as $camp): ?>
            <article class="campaign-card">
                <div class="campaign-banner">
                    <img src="<?= htmlspecialchars($camp['banner']) ?>" alt="<?= htmlspecialchars($camp['title']) ?>">
                    <span class="campaign-badge <?= $camp['status'] ?>">
                        <?= htmlspecialchars($camp['badge']) ?>
                    </span>
                </div>
                <div class="campaign-body">
                    <span class="campaign-time" style="display: inline-flex; align-items: center; gap: 5px;">
                        <!-- Icon đồng hồ đường nét đơ sắc (Monochrome SVG) -->
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <?= $camp['start_date'] ?> - <?= $camp['end_date'] ?>
                    </span>
                    
                    <h3><?= htmlspecialchars($camp['title']) ?></h3>
                    <p><?= htmlspecialchars($camp['description']) ?></p>
                    
                    <a href="/app/views/user/campaign_detail.php?id=<?= urlencode($camp['id']) ?>" class="category-view-button">
                        Khám phá ưu đãi ↗
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
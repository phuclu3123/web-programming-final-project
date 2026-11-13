<?php
// Merchant only sees campaigns belonging to shops they are authorized to manage.
$campaignCount = count($campaigns);
?>
<section class="merchant-campaigns-page" aria-labelledby="merchant-campaigns-title">
    <header class="merchant-campaigns-header">
        <div class="merchant-campaigns-header__copy">
            <h1 id="merchant-campaigns-title">Chiến dịch của cửa hàng</h1>
            <p>Tạo, quản lý và liên kết các Official Coupon vào từng chiến dịch ưu đãi.</p>
        </div>
        <a class="btn btn-primary merchant-campaigns-create" href="<?= e(url('/merchant/campaigns/create')) ?>">
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            <span>Tạo chiến dịch</span>
        </a>
    </header>

    <div class="merchant-campaigns-list-heading">
        <h2>Danh sách chiến dịch</h2>
        <span class="merchant-campaigns-count"><?= $campaignCount ?> chiến dịch</span>
    </div>

    <?php if ($campaigns === []): ?>
    <div class="merchant-campaigns-empty" role="status">
        <i class="fa-regular fa-calendar-plus" aria-hidden="true"></i>
        <h3>Chưa có chiến dịch</h3>
        <p>Tạo chiến dịch đầu tiên để nhóm các coupon của cửa hàng theo từng chương trình ưu đãi.</p>
    </div>
    <?php else: ?>
    <div class="merchant-campaigns-grid">
        <?php foreach ($campaigns as $campaign):
                $status = strtoupper((string)$campaign['status']);
                $statusTone = match ($status) {
                    'PUBLISHED' => 'published',
                    'DRAFT' => 'draft',
                    'ARCHIVED' => 'archived',
                    'DISABLED' => 'disabled',
                    default => 'neutral',
                };
                $campaignId = (int)$campaign['id'];
            ?>
        <article class="merchant-campaign-card" aria-labelledby="merchant-campaign-title-<?= $campaignId ?>">
            <div class="merchant-campaign-card__top">
                <span class="merchant-campaign-card__shop">
                    <i class="fa-solid fa-store" aria-hidden="true"></i>
                    <?= e((string)$campaign['merchant_name']) ?>
                </span>
                <span class="merchant-campaign-status merchant-campaign-status--<?= $statusTone ?>">
                    <?= e(campaign_status_label((string)$campaign['status'])) ?>
                </span>
            </div>

            <h3 class="merchant-campaign-card__title" id="merchant-campaign-title-<?= $campaignId ?>">
                <?= e((string)$campaign['name']) ?>
            </h3>
            <p class="merchant-campaign-card__coupons">
                <i class="fa-solid fa-ticket" aria-hidden="true"></i>
                <?= (int)$campaign['coupon_count'] ?> coupon đã liên kết
            </p>

            <div class="merchant-campaign-card__dates">
                <div class="merchant-campaign-card__date">
                    <span>Bắt đầu</span>
                    <time datetime="<?= e(format_db_datetime((string)$campaign['starts_at'], 'Y-m-d\\TH:i:sP')) ?>">
                        <?= e(format_db_datetime((string)$campaign['starts_at'])) ?>
                    </time>
                </div>
                <div class="merchant-campaign-card__date">
                    <span>Kết thúc</span>
                    <time datetime="<?= e(format_db_datetime((string)$campaign['ends_at'], 'Y-m-d\\TH:i:sP')) ?>">
                        <?= e(format_db_datetime((string)$campaign['ends_at'])) ?>
                    </time>
                </div>
            </div>

            <footer class="merchant-campaign-card__footer">
                <a class="merchant-campaign-card__action" href="<?= e(url('/merchant/campaigns/' . $campaignId)) ?>"
                    aria-label="Quản lý chiến dịch <?= e((string)$campaign['name']) ?>">
                    Quản lý chiến dịch
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </footer>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>
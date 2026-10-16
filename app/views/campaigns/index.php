<div class="discovery-page editorial-campaign-page">
    <div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Chiến dịch</span></div>
    <div class="editorial-section-head campaign-page-heading"><div><span class="eyebrow">04 / NHỮNG DỊP MUA SẮM ĐÁNG CHỜ</span><h1>Đúng thời điểm.<br><em>Thêm nhiều giá trị.</em></h1></div><a href="<?= e(url('/campaign-calendar')) ?>">Lịch ưu đãi <?= icon('arrow') ?></a></div>
    <nav class="discovery-tabs" aria-label="Lọc chiến dịch">
        <?php foreach (['ALL'=>'Tất cả','ACTIVE'=>'Đang diễn ra','UPCOMING'=>'Sắp bắt đầu'] as $key=>$label): ?><a href="<?= e(url('/campaigns') . '?phase=' . $key) ?>" <?= $phase === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?>
        <span class="discovery-result-count"><?= (int)$listing['total'] ?> chiến dịch phù hợp</span>
    </nav>
    <?php $campaignSelection=$listing['items']; $posterPriority=true; require __DIR__.'/../partials/campaign-bento.php'; ?>
    <?php if (!$listing['items']): ?><div class="member-empty"><strong>Chưa có chiến dịch trong nhóm này.</strong><p>Khám phá các coupon đang hoạt động trong khi chờ đợt ưu đãi tiếp theo.</p><a class="btn btn-outline-brand" href="<?= e(url('/coupons')) ?>">Xem coupon <?= icon('arrow') ?></a></div><?php else: ?>
    <?php if (count($listing['items'])>3): ?><div class="editorial-section-head"><div><span class="eyebrow">TIẾP TỤC KHÁM PHÁ</span><h2>Những chiến dịch khác.</h2></div></div>
    <div class="discovery-campaign-grid campaign-secondary-grid">
        <?php foreach (array_slice($listing['items'],3) as $campaignIndex=>$campaign): ?>
        <article class="discovery-campaign-card">
            <div class="discovery-campaign-cover"><img src="<?= e(editorialPhoto($campaignIndex % 2 ? 'fashion' : 'shopping')) ?>" alt="" width="720" height="480" loading="lazy" decoding="async"><span class="discovery-phase"><?= $campaign['upcoming'] ? 'Sắp bắt đầu' : 'Đang diễn ra' ?></span></div>
            <div class="discovery-campaign-body"><small><?= e($campaign['merchant_name'] ?: 'Chiến dịch CouponHub') ?></small><h2><?= e($campaign['name']) ?></h2><p><?= e($campaign['description'] ?: 'Khám phá các ưu đãi được tuyển chọn trong chiến dịch.') ?></p><p><?= e(format_db_datetime($campaign['starts_at'])) ?><br>→ <?= e(format_db_datetime($campaign['ends_at'])) ?></p><a href="<?= e(url('/campaigns/' . (int)$campaign['id'])) ?>"><span><?= (int)$campaign['coupon_count'] ?> coupon đang hoạt động</span><?= icon('arrow') ?></a></div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <?php if ($listing['pages'] > 1): ?><nav class="discovery-pager" aria-label="Phân trang chiến dịch"><?php for ($page=max(1,$listing['page']-2);$page<=min($listing['pages'],$listing['page']+2);++$page): ?><a href="<?= e(url('/campaigns') . '?phase=' . $phase . '&page=' . $page) ?>" <?= $listing['page']===$page ? 'aria-current="page"' : '' ?>><?= $page ?></a><?php endfor; ?></nav><?php endif; ?>
    <?php require __DIR__.'/../partials/campaign-deals.php'; ?>
</div>

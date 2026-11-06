<div class="discovery-page editorial-campaign-page event-directory">
    <div class="member-breadcrumb">
        <a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Chiến dịch</span>
    </div>

    <header class="event-masthead"><div><h1>Chiến dịch ưu đãi.</h1><p>Một điểm hẹn cho những lần mua sắm có kế hoạch.</p></div><a class="event-calendar-link" href="<?= e(url('/campaign-calendar')) ?>"><?= icon('clock') ?> Lịch ưu đãi <?= icon('arrow') ?></a></header>
    <div class="event-directory-meta"><span><?= (int)$listing['total'] ?> chiến dịch để khám phá</span><?php if ($selectedCategory || $selectedPlatform || $selectedChannel !== 'ALL' || $phase !== 'ALL'): ?><a href="<?= e(url('/campaigns')) ?>">Xem tất cả chiến dịch <?= icon('arrow') ?></a><?php endif; ?></div>

    <?php if (!empty($listing['items'])): ?>
        <?php $mainCampaign = $listing['items'][0]; ?>

        <!-- CHIẾN DỊCH ĐANG DIỄN RA (BANNER LỚN NỔI BẬT) -->
        <section class="campaign-section-block">


            <?php
                $campaign = $mainCampaign;
                $isMainPoster = true; $detailMode=false;
                $posterPriority = true;
                require __DIR__ . '/../partials/campaign-feature.php';
            ?>
        </section>

        <!-- CÁC CHIẾN DỊCH KHÁC + THẺ LỊCH SĂN MÃ -->
        <?php $otherCampaigns = array_slice($listing['items'], 1); ?>
        <?php if ($otherCampaigns): ?><section class="campaign-section-block event-more-section" data-event-gallery>
            <div class="event-gallery-heading"><div><h2 class="event-more-title">Theo thương hiệu bạn thích.</h2><p>Một buổi tập, một cuộc hẹn, một khoảng nghỉ.</p></div></div>
            <div class="event-gallery-frame">
                <div class="event-gallery-controls">
                    <button type="button" data-event-prev aria-label="Xem thương hiệu trước" aria-controls="event-brand-gallery"><?= icon('arrow') ?></button>
                    <button type="button" data-event-next aria-label="Xem thương hiệu tiếp theo" aria-controls="event-brand-gallery"><?= icon('arrow') ?></button>
                </div>
            <div class="event-more-list" id="event-brand-gallery" tabindex="0" aria-label="Các chiến dịch theo thương hiệu">
                <?php foreach ($otherCampaigns as $campaign): ?>
                    <?php
                        $isMainPoster = false; $detailMode=false; $posterPriority=false;
                        require __DIR__ . '/../partials/campaign-feature.php';
                    ?>
                <?php endforeach; ?>


            </div>
            </div>
        </section><?php endif; ?>

    <?php else: ?>
        <div class="member-empty">
            <strong>Chưa tìm thấy chiến dịch phù hợp.</strong>
            <p>Bạn có thể xem lịch ưu đãi hoặc quay lại danh sách đầy đủ.</p>
            <a href="<?= e(url('/campaigns')) ?>" class="btn-reset-filter">Xóa bộ lọc</a>
        </div>
    <?php endif; ?>

    <!-- DEALS TRONG CHIẾN DỊCH -->
    <?php require __DIR__ . '/../partials/campaign-deals.php'; ?>
    <?php if ($listing['pages'] > 1): ?><nav class="campaign-pagination" aria-label="Phân trang chiến dịch">
        <?php for ($target=max(1,$listing['page']-2); $target<=min($listing['pages'],$listing['page']+2); ++$target): ?>
        <a href="<?= e(url('/campaigns').'?'.http_build_query(['phase'=>$phase,'category_id'=>$selectedCategory,'usage_channel'=>$selectedChannel,'platform'=>$selectedPlatform,'page'=>$target])) ?>" <?= $target===$listing['page'] ? 'aria-current="page"' : '' ?>><?= $target ?></a>
        <?php endfor; ?>
    </nav><?php endif; ?>
</div>

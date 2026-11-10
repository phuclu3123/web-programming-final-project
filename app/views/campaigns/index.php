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
    <?php if(!empty($partnerCampaigns['items'])): ?>
    <section class="campaign-section-block" aria-labelledby="partner-campaign-title">
        <div class="event-gallery-heading"><div><h2 class="event-more-title" id="partner-campaign-title">Chương trình từ đối tác.</h2><p>Thông tin từ ACCESSTRADE, được CouponHub duyệt trước khi hiển thị.</p></div></div>
        <div class="row g-3">
        <?php foreach($partnerCampaigns['items'] as $source): $partner=json_decode($source['payload'],true,64,JSON_THROW_ON_ERROR);$partnerUrl=safeHttpUrl($partner['url']);$partnerLogo=safeHttpUrl($partner['logo']); ?>
            <div class="col-12 col-md-6 col-xl-4"><article class="card h-100"><div class="card-body d-flex flex-column align-items-start gap-3">
                <?php if($partnerLogo): ?><img src="<?= e($partnerLogo) ?>" alt="" width="72" height="72" class="object-fit-contain rounded" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php endif; ?>
                <h3 class="h5 mb-0"><?= e($partner['name']) ?></h3>
                <p class="text-muted mb-0"><?= e(mb_substr($partner['description']?:'Khám phá thông tin chương trình trên website đối tác.',0,180)) ?></p>
                <p class="small text-muted mb-0"><?= $source['ends_at']?'Đến '.e(format_db_datetime($source['ends_at'],'d/m/Y')):'Đối tác chưa công bố ngày kết thúc.' ?></p>
                <?php if($source['starts_at']!==null && $source['starts_at']>gmdate('Y-m-d H:i:s')): ?><p class="small mb-0">Bắt đầu <?= e(format_db_datetime($source['starts_at'],'d/m/Y')) ?></p><?php endif; ?>
                <?php if($partnerUrl): ?><a class="btn btn-outline-primary mt-auto" href="<?= e($partnerUrl) ?>" target="_blank" rel="noopener noreferrer">Xem tại đối tác <?= icon('arrow') ?><span class="visually-hidden"> (mở tab mới)</span></a><?php endif; ?>
            </div></article></div>
        <?php endforeach; ?>
        </div>
        <?php if($partnerCampaigns['pages']>1): ?><nav class="d-flex flex-wrap gap-2 mt-3" aria-label="Trang chương trình đối tác"><?php for($n=max(1,$partnerCampaigns['page']-2);$n<=min($partnerCampaigns['pages'],$partnerCampaigns['page']+2);++$n): ?><a class="btn btn-outline-secondary" href="<?= e(url('/campaigns').'?'.http_build_query(['partner_page'=>$n,'phase'=>$phase,'page'=>$listing['page']]).'#partner-campaign-title') ?>" <?= $n===$partnerCampaigns['page']?'aria-current="page"':'' ?>><?= $n ?></a><?php endfor; ?></nav><?php endif; ?>
    </section>
    <?php endif; ?>
    <?php if ($listing['pages'] > 1): ?><nav class="campaign-pagination" aria-label="Phân trang chiến dịch">
        <?php for ($target=max(1,$listing['page']-2); $target<=min($listing['pages'],$listing['page']+2); ++$target): ?>
        <a href="<?= e(url('/campaigns').'?'.http_build_query(['phase'=>$phase,'category_id'=>$selectedCategory,'usage_channel'=>$selectedChannel,'platform'=>$selectedPlatform,'page'=>$target])) ?>" <?= $target===$listing['page'] ? 'aria-current="page"' : '' ?>><?= $target ?></a>
        <?php endfor; ?>
    </nav><?php endif; ?>
</div>

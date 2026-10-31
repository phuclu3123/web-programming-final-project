<?php if ($homeCampaigns): ?>
<section class="editorial-block" aria-labelledby="home-campaigns-title">
    <div class="editorial-section-head">
        <div><span class="eyebrow">NHỮNG DỊP MUA SẮM ĐÁNG CHỜ</span>
            <h2 id="home-campaigns-title">Chiến dịch nổi bật.</h2>
            <p>Các ưu đãi đang diễn ra và sắp bắt đầu. Chọn đúng thời điểm cho bạn.</p>
        </div><a href="<?= e(url('/campaigns')) ?>">Xem tất cả <?= icon('arrow') ?></a>
    </div>
    <?php $campaignSelection=$homeCampaigns; $posterPriority=false; require __DIR__.'/../partials/campaign-bento.php'; ?>
</section>
<?php endif; ?>
<section class="studio-section" aria-labelledby="home-guides-title">
    <div class="studio-section-title">
        <div><span class="eyebrow">THE COUPONHUB EDIT</span>
            <h2 id="home-guides-title">Tìm mã giỏi. Dùng mã đúng.</h2>
        </div><a href="<?= e(url('/articles')) ?>">Góc đọc CouponHub <?= icon('arrow') ?></a>
    </div>
    <div class="guide-card-grid">
        <?php foreach (productGuides() as $slug=>$item): require __DIR__.'/../partials/guide-card.php'; endforeach; ?>
    </div>
</section>
<?php if ($homeArticles): ?>
<section class="editorial-block" aria-labelledby="home-articles-title">
    <div class="editorial-section-head">
        <div><span class="eyebrow">GÓC ĐỌC COUPONHUB</span>
            <h2 id="home-articles-title">Thêm một góc nhìn, mua sắm tốt hơn.</h2>
        </div><a href="<?= e(url('/articles')) ?>">Xem bài viết <?= icon('arrow') ?></a>
    </div>
    <div class="editorial-articles">
        <?php foreach ($homeArticles as $a): require __DIR__.'/../partials/editorial-article.php'; endforeach; ?></div>
</section>
<?php endif; ?>
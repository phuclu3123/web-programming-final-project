<?php $guides = productGuides();
$guideGroups = ['doc-dieu-kien-ma' => 'dung-ma', 'so-sanh-muc-tiet-kiem' => 'tinh-toan', 'kiem-chung-cung-cong-dong' => 'cong-dong']; ?>
<div class="editorial-article-page studio-journal">
    <div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Góc đọc</span></div>
    <header class="journal-page-heading"><h1>Góc đọc.</h1></header>
    <?php if ($guideTopic === 'all'): ?>
    <section class="journal-lead" aria-labelledby="journal-lead-title">
        <div class="journal-lead-copy">
            <span>4 phút đọc</span>
            <h2 id="journal-lead-title">Một mã tốt.<br>Bốn điều cần rõ.</h2>
            <a class="btn btn-brand" href="<?= e(url('/guides/doc-dieu-kien-ma')) ?>">Đọc bài <?= icon('arrow') ?></a>
        </div>
        <a class="journal-lead-image" href="<?= e(url('/guides/doc-dieu-kien-ma')) ?>" aria-label="Đọc hướng dẫn về điều kiện coupon">
            <img src="<?= e(editorialLargePhoto('shopping')) ?>" alt="" width="1200" height="800" fetchpriority="high">
        </a>
    </section>
    <?php endif; ?>
    <section class="journal-next" aria-labelledby="guide-library-title">
        <div class="journal-next-heading"><h2 id="guide-library-title"><?= $guideTopic === 'all' ? 'Đọc tiếp.' : 'Bài theo chủ đề.' ?></h2><?php if ($guideTopic !== 'all'): ?><a href="<?= e(url('/articles')) ?>">Tất cả bài đọc <?= icon('arrow') ?></a><?php endif; ?></div>
        <div class="journal-reading-list">
            <?php
            $shortTitles = ['doc-dieu-kien-ma' => 'Đọc đúng điều kiện.', 'so-sanh-muc-tiet-kiem' => '20% hay 40.000đ?', 'kiem-chung-cung-cong-dong' => 'Cùng kiểm chứng mã.'];
            foreach ($guides as $slug => $item):
                if ($guideTopic === 'all' && $slug === 'doc-dieu-kien-ma') continue;
                if ($guideTopic !== 'all' && $guideGroups[$slug] !== $guideTopic) continue;
            ?>
            <a class="journal-quick-read" href="<?= e(url('/guides/'.$slug)) ?>">
                <div class="journal-quick-art">
                    <?php if ($slug === 'so-sanh-muc-tiet-kiem'): ?><strong>20<span>%</span><small>hay</small>40<span>k</span></strong>
                    <?php else: ?><img src="<?= e($slug === 'kiem-chung-cung-cong-dong' ? brandPhoto('grab-delivery') : editorialPhoto('shopping')) ?>" alt="" width="720" height="480" loading="lazy"><?php endif; ?>
                </div>
                <div class="journal-quick-copy"><small><?= (int)$item['minutes'] ?> phút đọc</small><h3><?= e($shortTitles[$slug]) ?></h3><span>Đọc bài <?= icon('arrow') ?></span></div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php if ($articles): ?><section class="studio-section">
            <div class="studio-section-title">
                <div><h2>Bài viết mới.</h2></div>
            </div>
            <div class="editorial-articles">
                <?php foreach ($articles as $a): require __DIR__ . '/../partials/editorial-article.php';
                endforeach; ?></div>
        </section><?php endif; ?>
    <section class="journal-playground" aria-labelledby="journal-try-title">
        <div class="journal-brand-column journal-brand-column-left" aria-hidden="true">
            <?php foreach (['Nike', 'Phúc Long', 'CGV'] as $brand): ?><span><?= brandLogo($brand) ?><b><?= e($brand) ?></b></span><?php endforeach; ?>
        </div>
        <div class="journal-playground-copy">
            <h2 id="journal-try-title">Chọn đúng mã.<br><em>Giữ lại nhiều hơn.</em></h2>
            <a class="btn btn-brand" href="<?= e(url('/index.php')) ?>#estimate">Thử tính ưu đãi <?= icon('arrow') ?></a>
        </div>
        <div class="journal-brand-column journal-brand-column-right" aria-hidden="true">
            <?php foreach (['Coolmate', 'Hasaki', 'Highlands Coffee'] as $brand): ?><span><?= brandLogo($brand) ?><b><?= e($brand) ?></b></span><?php endforeach; ?>
        </div>
        <span class="journal-orbit" aria-hidden="true"></span>
        <svg class="journal-spark" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M32 4v56M4 32h56M12 12l40 40M12 52l40-40"/></svg>
    </section>
</div>

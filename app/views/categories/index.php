<?php
$total = array_sum(array_column($categories,'coupon_count'));
$previewCategory = $categories[0] ?? null;
foreach ($categories as $candidate) if ((int)$candidate['id']===$selectedCategory) $previewCategory=$candidate;
?>
<div class="discovery-page">
    <div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Danh mục</span></div>
    <header class="category-directory-title">
        <div><span class="eyebrow">DANH MỤC CUỘC SỐNG</span>
            <h1>Có gì<br><em>trong ngày bạn?</em></h1>
        </div>
        <p><?= count($categories) ?> nhu cầu, <?= (int)$total ?> ưu đãi đang hoạt động. Từ bữa trưa quen thuộc đến một
            chuyến đi bất chợt. Bắt đầu bằng điều bạn đang tìm.</p>
    </header>
    <?php if (!$categories): ?><div class="member-empty"><strong>Danh mục đang được cập nhật.</strong>
        <p>Bạn có thể quay lại trang khám phá để xem các ưu đãi hiện có.</p>
    </div><?php else: ?>
    <div class="category-directory">
        <nav class="category-directory-list" aria-label="Khám phá ưu đãi theo nhu cầu">
            <?php foreach ($categories as $categoryIndex=>$category): [$categoryHeadline,$categoryDescription]=categoryStory($category['name']); ?>
            <a class="category-directory-link" href="<?= e(url('/coupons') . '?category_id=' . (int)$category['id']) ?>"
                data-category-visual="<?= e(editorialLargePhoto($category['slug'])) ?>"
                data-category-name="<?= e($category['name']) ?>" data-category-caption="<?= e($categoryHeadline) ?>">
                <small><?= str_pad((string)($categoryIndex+1),2,'0',STR_PAD_LEFT) ?></small>
                <div>
                    <h2><?= e($category['name']) ?></h2>
                    <p><?= e($categoryDescription) ?></p>
                </div>
                <span><?= (int)$category['coupon_count'] ?> mã</span><?= icon('arrow') ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <figure class="category-directory-visual">
            <img data-category-preview src="<?= e(editorialLargePhoto($previewCategory['slug'])) ?>" alt="" width="1200"
                height="800" fetchpriority="high">
            <figcaption><strong data-category-preview-name><?= e($previewCategory['name']) ?></strong><span
                    data-category-preview-caption><?= e(categoryStory($previewCategory['name'])[0]) ?></span>
            </figcaption>
        </figure>
    </div>
    <?php endif; ?>
    <section class="editorial-block category-picks" id="category-picks" aria-labelledby="category-picks-title">
        <div class="editorial-section-head">
            <div><span class="eyebrow">TỪ NHU CẦU ĐẾN ƯU ĐÃI</span>
                <h2 id="category-picks-title">Chọn một nhóm, xem mã đang có.</h2>
                <p>Những coupon còn hiệu lực trong lựa chọn của bạn.</p>
            </div><a href="<?= e(url('/coupons').($selectedCategory ? '?category_id='.$selectedCategory : '')) ?>">Xem
                tất cả <?= icon('arrow') ?></a>
        </div>
        <nav class="category-pick-tabs" aria-label="Xem trước ưu đãi theo danh mục"><a
                href="<?= e(url('/categories')) ?>#category-picks"
                <?= !$selectedCategory ? 'aria-current="true"' : '' ?>>Tất
                cả</a><?php foreach ($categories as $category): ?><a
                href="<?= e(url('/categories').'?category='.(int)$category['id']) ?>#category-picks"
                <?= $selectedCategory===(int)$category['id'] ? 'aria-current="true"' : '' ?>><?= e($category['name']) ?></a><?php endforeach; ?>
        </nav>
        <?php if ($categoryCoupons): ?><div class="community-ticket-grid">
            <?php foreach ($categoryCoupons as $coupon): require __DIR__.'/../partials/community-coupon-card.php'; endforeach; ?>
        </div><?php else: ?><div class="member-empty"><strong>Các ưu đãi trong nhóm này đang được cập nhật.</strong>
            <p>Thử một nhu cầu khác để tiếp tục khám phá.</p>
        </div><?php endif; ?>
    </section>
    <?php $brandSectionId='category-brands'; require __DIR__.'/../partials/brand-marquee.php'; ?>
</div>
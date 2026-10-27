<div class="editorial-article-page">
    <div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Bài viết</span></div>
    <?php if ($articles): $featuredArticle=$articles[0]; [$featuredPhoto,$featuredTopic]=articleTopic($featuredArticle['title']); ?>
    <article class="editorial-article-feature"><img src="<?= e(editorialPhoto($featuredPhoto,false)) ?>" alt=""
            width="1200" height="800" fetchpriority="high">
        <div><span class="eyebrow">GÓC ĐỌC · <?= e($featuredTopic) ?></span>
            <h1><?= e($featuredArticle['title']) ?></h1><?php if ($featuredArticle['summary']): ?><p>
                <?= e($featuredArticle['summary']) ?></p><?php endif; ?><small><?= e($featuredArticle['author_name']) ?>
                · <?= e(format_db_datetime($featuredArticle['published_at'],'d/m/Y')) ?></small><a class="btn btn-brand"
                href="<?= e(url('/articles/'.$featuredArticle['slug'])) ?>">Đọc bài viết <?= icon('arrow') ?></a>
        </div>
    </article>
    <div class="editorial-section-head">
        <div><span class="eyebrow">KINH NGHIỆM & CẢM HỨNG</span>
            <h2>Đọc thêm, chọn tốt hơn.</h2>
            <p>Hướng dẫn sử dụng coupon và những góc nhìn mua sắm từ CouponHub.</p>
        </div>
    </div>
    <?php if (count($articles)>1): ?><div class="editorial-articles">
        <?php foreach (array_slice($articles,1) as $a): require __DIR__.'/../partials/editorial-article.php'; endforeach; ?>
    </div><?php else: ?><p class="muted">Những bài viết tiếp theo đang được chuẩn bị.</p><?php endif; ?>
    <?php else: ?>
    <section class="photo-page-hero"><img src="<?= e(editorialPhoto('technology',false)) ?>" alt="" width="1200"
            height="800">
        <div><span class="eyebrow">GÓC ĐỌC COUPONHUB</span>
            <h1>Mua sắm thông minh,<br>bắt đầu từ hiểu rõ.</h1>
            <p>Các bài viết đang được biên tập. Khám phá ưu đãi đang hoạt động trong lúc chờ nội dung mới.</p><a
                class="btn" href="<?= e(url('/coupons')) ?>">Khám phá coupon <?= icon('arrow') ?></a>
        </div>
    </section>
    <?php endif; ?>
    <section class="editorial-block" aria-labelledby="reading-tools-title">
        <div class="editorial-section-head">
            <div><span class="eyebrow">TRƯỚC KHI SĂN ƯU ĐÃI</span>
                <h2 id="reading-tools-title">Một chút hiểu rõ, thêm nhiều an tâm.</h2>
            </div>
        </div>
        <div class="reading-tools"><a href="<?= e(url('/community-guidelines')) ?>"><?= icon('shield') ?><strong>Chia sẻ
                    & kiểm chứng</strong><span>Cách cộng đồng giữ ưu đãi hữu ích.</span><?= icon('arrow') ?></a><a
                href="<?= e(url('/index.php')) ?>#estimate"><?= icon('chart') ?><strong>Thử tính khoản tiết
                    kiệm</strong><span>So mức giảm với đơn hàng của bạn.</span><?= icon('arrow') ?></a><a
                href="<?= e(url('/coupons')) ?>"><?= icon('ticket') ?><strong>Xem điều kiện trước khi
                    dùng</strong><span>Khám phá các coupon đang hoạt động.</span><?= icon('arrow') ?></a></div>
    </section>
</div>
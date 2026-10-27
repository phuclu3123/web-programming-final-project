<?php $guides=productGuides(); $guideGroups=['doc-dieu-kien-ma'=>'dung-ma','so-sanh-muc-tiet-kiem'=>'tinh-toan','kiem-chung-cung-cong-dong'=>'cong-dong']; ?>
<div class="editorial-article-page studio-journal">
    <div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Góc đọc</span></div>
    <header class="journal-heading journal-masthead">
        <div><span class="eyebrow">THE COUPONHUB EDIT</span>
            <h1>Góc <em>đọc.</em></h1>
        </div>
        <p>Hiểu thêm một chút.<br>Chọn tốt hơn nhiều.<small>CẨM NANG MUA SẮM / <?= count($guides) ?> HƯỚNG DẪN</small>
        </p>
    </header>
    <section class="journal-cover">
        <a href="<?= e(url('/guides/doc-dieu-kien-ma')) ?>" class="journal-cover-image"><img
                src="<?= e(editorialLargePhoto('shopping')) ?>" alt="" width="1200" height="800"
                fetchpriority="high"><span>CẨM NANG / 01</span></a>
        <div class="journal-cover-copy">
            <nav class="journal-shelf" aria-label="Ba hướng dẫn trên kệ đọc">
                <a class="shelf-spine shelf-spine-blue"
                    href="<?= e(url('/guides/doc-dieu-kien-ma')) ?>"><small>01</small><span>ĐỌC ĐIỀU KIỆN</span></a>
                <a class="shelf-spine shelf-spine-paper"
                    href="<?= e(url('/guides/so-sanh-muc-tiet-kiem')) ?>"><small>02</small><span>TÍNH TIẾT
                        KIỆM</span></a>
                <a class="shelf-spine shelf-spine-olive"
                    href="<?= e(url('/guides/kiem-chung-cung-cong-dong')) ?>"><small>03</small><span>CÙNG KIỂM
                        CHỨNG</span></a>
                <img src="<?= e(url('/assets/images/editorial/headphones-cutout.webp')) ?>" alt="" width="479"
                    height="499">
            </nav>
            <span class="eyebrow">BẮT ĐẦU TỪ ĐIỀU CỐT LÕI</span>
            <h2>Một mã tốt.<br>Bốn điều cần rõ.</h2>
            <p>Đơn tối thiểu. Trần giảm. Kênh áp dụng. Hạn dùng. Dành vài phút để hiểu trước khi lấy mã.</p><a
                class="btn btn-brand" href="<?= e(url('/guides/doc-dieu-kien-ma')) ?>">Đọc hướng dẫn
                <?= icon('arrow') ?></a><small>Hướng dẫn CouponHub · 4 phút đọc</small>
        </div>
    </section>
    <section class="studio-section" aria-labelledby="guide-library-title">
        <div class="studio-section-title">
            <div><span class="eyebrow">THƯ VIỆN HƯỚNG DẪN</span>
                <h2 id="guide-library-title">Đọc theo điều bạn cần.</h2>
            </div>
        </div>
        <nav class="journal-topics" aria-label="Lọc chủ đề hướng dẫn">
            <?php foreach (['all'=>'Tất cả','dung-ma'=>'Dùng mã','tinh-toan'=>'Tính tiết kiệm','cong-dong'=>'Cộng đồng'] as $topic=>$label): ?><a
                href="<?= e(url('/articles').'?topic='.$topic) ?>#guide-library-title"
                <?= $guideTopic===$topic ? 'aria-current="page"' : '' ?>><?= e($label) ?></a><?php endforeach; ?></nav>
        <div class="guide-card-grid">
            <?php foreach ($guides as $slug=>$item): if ($guideTopic!=='all' && $guideGroups[$slug]!==$guideTopic) continue; require __DIR__.'/../partials/guide-card.php'; endforeach; ?>
        </div>
    </section>
    <?php if ($articles): ?><section class="studio-section">
        <div class="studio-section-title">
            <div><span class="eyebrow">TỪ BAN BIÊN TẬP</span>
                <h2>Bài viết mới.</h2>
            </div><span><?= count($articles) ?> bài đã xuất bản</span>
        </div>
        <div class="editorial-articles">
            <?php foreach ($articles as $a): require __DIR__.'/../partials/editorial-article.php'; endforeach; ?></div>
    </section><?php endif; ?>
    <section class="studio-manifesto"><span class="eyebrow">ĐỌC XONG, THỬ NGAY</span>
        <h2>Khoản giảm nên hợp<br>với đơn hàng của bạn.</h2><a href="<?= e(url('/index.php')) ?>#estimate">Mở công cụ
            ước tính <?= icon('arrow') ?></a>
    </section>
</div>
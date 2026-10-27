<?php [$articlePhoto, $articleTopic] = articleTopic($article['title']); ?>
<article class="article-reading">
    <a class="text-link" href="<?= e(url('/articles')) ?>">← Tất cả bài viết</a>
    <h1><?= e($article['title']) ?></h1>
    <div class="small muted"><?= e($article['author_name']) ?> ·
        <?= e(format_db_datetime($article['published_at'], 'd/m/Y')) ?></div>
    <img src="<?= e(editorialLargePhoto($articlePhoto)) ?>" alt="" width="1200" height="800" fetchpriority="high">
    <?php if ($article['summary']): ?><p class="lead"><?= e($article['summary']) ?></p><?php endif; ?>
    <div class="article-body"><?= nl2br(e($article['body'])) ?></div>
</article>
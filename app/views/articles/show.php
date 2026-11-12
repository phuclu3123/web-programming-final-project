<?php
// Escape nội dung bài và nhúng vùng bình luận dùng dữ liệu lưu trong MySQL.
 [$articlePhoto,$articleTopic]=articleTopic($article['title']); ?>
<div class="article-detail-page">
<article class="article-reading article-story">
    <a class="text-link" href="<?= e(url('/articles')) ?>">← Góc đọc</a>
    <header><p class="article-story-topic"><?= e($articleTopic) ?></p><h1><?= e($article['title']) ?></h1>
    <p class="article-byline"><?= e($article['author_name']) ?> · <?= e(format_db_datetime($article['published_at'],'d/m/Y')) ?></p></header>
    <img src="<?= e(editorialLargePhoto($articlePhoto)) ?>" alt="" width="1200" height="800" fetchpriority="high">
    <?php if($article['summary']): ?><p class="lead"><?= e($article['summary']) ?></p><?php endif; ?>
    <div class="article-body"><?php foreach(preg_split('/\n\s*\n/u',trim($article['body'])) as $paragraph): ?>
        <?php if(preg_match('/^\d{2}\. [^\n]+$/u',$paragraph)): ?><h2><?= e($paragraph) ?></h2><?php else: ?><p><?= nl2br(e($paragraph)) ?></p><?php endif; ?>
    <?php endforeach; ?></div>
</article>
<?php require __DIR__.'/../partials/article-comments.php'; ?>
</div>
<link rel="stylesheet" href="<?= e(url('/assets/css/article-comments.css')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/css/article-comments.css') ?>">
<script src="<?= e(url('/assets/js/article-comments.js')) ?>?v=<?= filemtime(__DIR__.'/../../../assets/js/article-comments.js') ?>" defer></script>

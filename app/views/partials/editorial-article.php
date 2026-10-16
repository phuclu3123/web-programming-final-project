<?php [$photo,$topic] = articleTopic($a['title']); $articleUrl = url('/articles/'.$a['slug']); ?>
<article class="editorial-article">
    <a class="editorial-article-image" href="<?= e($articleUrl) ?>" tabindex="-1" aria-hidden="true"><img src="<?= e(editorialPhoto($photo)) ?>" alt="" width="720" height="480" loading="lazy" decoding="async"></a>
    <div class="editorial-article-copy"><span><?= e($topic) ?></span><h2><a href="<?= e($articleUrl) ?>"><?= e($a['title']) ?></a></h2><?php if (!empty($a['summary'])): ?><p><?= e($a['summary']) ?></p><?php endif; ?><div class="editorial-article-foot"><span><?= e($a['author_name']) ?> · <?= e(format_db_datetime($a['published_at'],'d/m/Y')) ?></span><a href="<?= e($articleUrl) ?>" aria-label="<?= e('Đọc '.$a['title']) ?>"><?= icon('arrow') ?></a></div></div>
</article>

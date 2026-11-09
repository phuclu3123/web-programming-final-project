<?php /* Phân trang bình luận cho admin; thao tác ẩn/hiện gửi POST có CSRF. */ ?><link rel="stylesheet" href="<?= e(url('/assets/css/article-comments.css')) ?>?v=<?= filemtime(__DIR__.'/../../../../assets/css/article-comments.css') ?>">
<header class="workspace-heading"><div><h1>Bình luận bài viết</h1><p>Ẩn nội dung không phù hợp. Bình luận đã xóa được giữ trong lịch sử, không xuất hiện công khai.</p></div><a class="btn btn-outline-primary" href="<?= e(url('/admin/articles')) ?>">Quản lý bài viết</a></header>
<div class="workspace-panel">
<?php if(!$comments): ?><p>Chưa có bình luận cần xem.</p><?php endif; ?>
<?php foreach($comments as $comment): ?>
<article class="article-moderation-item">
    <a href="<?= e(url('/admin/articles/'.(int)$comment['article_id'].'/edit')) ?>"><?= e($comment['title']) ?></a>
    <p><strong><?= e($comment['author_name']) ?></strong> · <?= e(format_db_datetime($comment['created_at'])) ?> · <?= $comment['status']==='VISIBLE'?'Đang hiển thị':'Đã ẩn' ?></p>
    <p class="article-comment-body"><?= e($comment['body']) ?></p>
    <form method="post" action="<?= e(url('/admin/article-comments/'.(int)$comment['id'].'/status')) ?>">
        <input type="hidden" name="csrf" value="<?= e(\App\Core\Session::csrfToken()) ?>">
        <input type="hidden" name="status" value="<?= $comment['status']==='VISIBLE'?'HIDDEN':'VISIBLE' ?>">
        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $comment['status']==='VISIBLE'?'Ẩn bình luận':'Hiển thị lại' ?></button>
    </form>
</article>
<?php endforeach; ?>
<nav aria-label="Phân trang bình luận"><?php if($page>1): ?><a class="btn btn-outline-secondary" href="<?= e(url('/admin/article-comments').'?page='.($page-1)) ?>">Trang trước</a><?php endif; ?><?php if($more): ?><a class="btn btn-outline-secondary" href="<?= e(url('/admin/article-comments').'?page='.($page+1)) ?>">Trang tiếp</a><?php endif; ?></nav>
</div>

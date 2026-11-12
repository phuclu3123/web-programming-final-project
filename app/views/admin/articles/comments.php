<?php /* Phân trang bình luận cho admin; thao tác ẩn/hiện gửi POST có CSRF. */ ?><link rel="stylesheet" href="<?= e(url('/assets/css/article-comments.css')) ?>?v=<?= filemtime(__DIR__.'/../../../../assets/css/article-comments.css') ?>">
<header class="workspace-heading"><div><h1>Bình luận bài viết</h1><p>Ẩn nội dung không phù hợp. Bình luận đã xóa được giữ trong lịch sử, không xuất hiện công khai.</p></div><a class="btn btn-outline-primary" href="<?= e(url('/admin/articles')) ?>">Quản lý bài viết</a></header>
<div class="workspace-panel">
<?php if(!$comments): ?><p>Chưa có bình luận cần xem.</p><?php endif; ?>
<?php foreach($comments as $comment): ?>
<?php
    $editUrl = url('/admin/articles/'.(int)$comment['article_id'].'/edit');
    $articleUrl = url('/articles/'.$comment['slug']);
    $published = (bool)$comment['article_published'];
    // Permalink resolves the thread and reply cursor on the server.
    $commentUrl = $articleUrl.'?comment='.(int)$comment['id'].'#article-comment-'.(int)$comment['id'];
?>
<article class="article-moderation-item">
    <a href="<?= e($published ? $articleUrl : $editUrl) ?>"><?= e($comment['title']) ?></a>
    <p class="article-comment-meta"><strong><?= e($comment['author_name']) ?></strong><span class="article-comment-role article-comment-role--<?= e(strtolower($comment['author_role'])) ?>"><?= e($comment['author_role_label']) ?></span><span><?= e(format_db_datetime($comment['created_at'])) ?> · <?= $comment['status']==='VISIBLE'?'Đang hiển thị':'Đã ẩn' ?></span></p>
    <p class="article-comment-body"><?= e($comment['body']) ?></p>
    <?php if($comment['parent_comment_id']): ?><p class="text-muted small">Phản hồi trong một cuộc trao đổi.</p><?php endif; ?>
    <nav class="article-moderation-links" aria-label="Liên kết bài viết và bình luận">
        <?php if($published): ?>
        <a href="<?= e($articleUrl) ?>">Xem bài viết</a>
        <?php if($comment['status']==='VISIBLE'): ?><a href="<?= e($commentUrl) ?>">Đến bình luận</a><?php endif; ?>
        <?php endif; ?>
        <a href="<?= e($editUrl) ?>">Chỉnh sửa bài viết</a>
    </nav>
    <?php if(!$published): ?><p class="text-muted small">Bài viết chưa được xuất bản công khai.</p>
    <?php elseif($comment['status']!=='VISIBLE'): ?><p class="text-muted small">Bình luận đã ẩn, chỉ xem được trong quản trị.</p><?php endif; ?>
    <form method="post" action="<?= e(url('/admin/article-comments/'.(int)$comment['id'].'/status')) ?>">
        <input type="hidden" name="csrf" value="<?= e(\App\Core\Session::csrfToken()) ?>">
        <input type="hidden" name="status" value="<?= $comment['status']==='VISIBLE'?'HIDDEN':'VISIBLE' ?>">
        <button class="btn btn-sm btn-outline-secondary" type="submit"><?= $comment['status']==='VISIBLE'?'Ẩn bình luận':'Hiển thị lại' ?></button>
    </form>
</article>
<?php endforeach; ?>
<nav aria-label="Phân trang bình luận"><?php if($page>1): ?><a class="btn btn-outline-secondary" href="<?= e(url('/admin/article-comments').'?page='.($page-1)) ?>">Trang trước</a><?php endif; ?><?php if($more): ?><a class="btn btn-outline-secondary" href="<?= e(url('/admin/article-comments').'?page='.($page+1)) ?>">Trang tiếp</a><?php endif; ?></nav>
</div>

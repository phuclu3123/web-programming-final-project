<?php
// Giữ form POST hoạt động khi tắt JS; AJAX dùng cùng CSRF và request ID.
 $commentEndpoint=url('/api/articles/'.(int)$article['id'].'/comments'); ?>
<section class="article-discussion" id="article-comments" aria-labelledby="article-comments-title" data-article-comments data-endpoint="<?= e($commentEndpoint) ?>" data-delete-base="<?= e(url('/api/article-comments')) ?>" data-csrf="<?= e(\App\Core\Session::csrfToken()) ?>">
    <header class="article-discussion-heading"><h2 id="article-comments-title">Cùng trao đổi.</h2><span><b data-comment-count><?= (int)$commentPage['total'] ?></b> bình luận</span></header>
    <?php if($commentViewer): ?>
    <form class="article-comment-form" method="post" action="<?= e($commentEndpoint) ?>" data-comment-form>
        <input type="hidden" name="csrf" value="<?= e(\App\Core\Session::csrfToken()) ?>"><input type="hidden" name="request_id" value="<?= bin2hex(random_bytes(16)) ?>">
        <label for="article-comment-body">Bình luận với tên <?= e($commentViewer['full_name']) ?></label>
        <textarea id="article-comment-body" name="body" required maxlength="2000" rows="3" placeholder="Đặt câu hỏi hoặc chia sẻ điều bạn thấy hữu ích…" aria-describedby="article-comment-help article-comment-feedback"></textarea>
        <div class="article-comment-form-foot"><small id="article-comment-help">Tối đa 2.000 ký tự. Trao đổi lịch sự, không đăng thông tin cá nhân.</small><button type="submit" class="btn btn-brand">Gửi bình luận <?= icon('arrow') ?></button></div>
    </form>
    <?php else: ?><p class="article-comment-login"><a class="btn btn-brand" data-auth-open href="<?= e(url('/login').'?return='.rawurlencode('/articles/'.$article['slug'].'#article-comments')) ?>">Đăng nhập để bình luận</a><span>Bạn vẫn có thể đọc các trao đổi bên dưới.</span></p><?php endif; ?>
    <p id="article-comment-feedback" class="article-comment-feedback" role="status" aria-live="polite" tabindex="-1"></p>
    <div class="article-comment-list" data-comment-list>
    <?php if(!$commentPage['items']): ?><p class="article-comment-empty">Chưa có bình luận. Bạn có câu hỏi về bài viết này?</p><?php endif; ?>
    <?php foreach($commentPage['items'] as $comment): ?>
    <article class="article-comment" data-comment-id="<?= $comment['id'] ?>">
        <div class="article-comment-meta"><strong><?= e($comment['author_name']) ?></strong><time datetime="<?= e(str_replace(' ','T',$comment['created_at']).'Z') ?>"><?= e($comment['created_at_display']) ?></time></div>
        <p class="article-comment-body"><?= e($comment['body']) ?></p>
        <?php if($comment['can_delete']): ?><form method="post" action="<?= e(url('/api/article-comments/'.$comment['id'].'/delete')) ?>" data-comment-delete><input type="hidden" name="csrf" value="<?= e(\App\Core\Session::csrfToken()) ?>"><button type="submit" class="article-comment-delete">Xóa bình luận của tôi</button></form><?php endif; ?>
    </article>
    <?php endforeach; ?>
    </div>
    <a class="article-comments-more btn btn-outline-secondary" data-comments-more <?= $commentPage['next_before']?'':'hidden' ?> href="<?= e(url('/articles/'.$article['slug']).'?before='.(int)($commentPage['next_before']??0).'#article-comments') ?>" data-before="<?= (int)($commentPage['next_before']??0) ?>">Xem bình luận trước</a>
</section>

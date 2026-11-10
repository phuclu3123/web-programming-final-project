<?php
// Giữ form POST hoạt động khi tắt JS; AJAX dùng cùng CSRF và request ID.
 $commentEndpoint=url('/api/articles/'.(int)$article['id'].'/comments'); ?>
<section class="article-discussion" id="article-comments" aria-labelledby="article-comments-title" data-article-comments data-endpoint="<?= e($commentEndpoint) ?>" data-delete-base="<?= e(url('/api/article-comments')) ?>" data-article-url="<?= e(url('/articles/'.$article['slug'])) ?>" data-login-url="<?= e(url('/login')) ?>" data-authenticated="<?= $commentViewer?'1':'0' ?>" data-csrf="<?= e(\App\Core\Session::csrfToken()) ?>">
    <header class="article-discussion-heading"><h2 id="article-comments-title">Cùng trao đổi.</h2><span><b data-comment-count><?= (int)$commentPage['total'] ?></b> bình luận</span></header>
    <?php if($commentViewer): ?>
    <form class="article-comment-form" id="article-comment-form" method="post" action="<?= e($commentEndpoint) ?>" data-comment-form>
        <input type="hidden" name="csrf" value="<?= e(\App\Core\Session::csrfToken()) ?>"><input type="hidden" name="request_id" value="<?= bin2hex(random_bytes(16)) ?>">
        <input type="hidden" name="parent_comment_id" value="<?= (int)($replyTarget['id']??0)?:'' ?>">
        <?php if($replyTarget??null): ?><p class="article-reply-context">Đang trả lời <?= e($replyTarget['author_name']) ?> · <a href="<?= e(url('/articles/'.$article['slug']).'#article-comments') ?>">Hủy trả lời</a></p><?php endif; ?>
        <label for="article-comment-body">Bình luận với tên <?= e($commentViewer['full_name']) ?></label>
        <textarea id="article-comment-body" name="body" required maxlength="2000" rows="3" placeholder="Đặt câu hỏi hoặc chia sẻ điều bạn thấy hữu ích…" aria-describedby="article-comment-help article-comment-feedback"></textarea>
        <div class="article-comment-form-foot"><small id="article-comment-help">Tối đa 2.000 ký tự. Trao đổi lịch sự, không đăng thông tin cá nhân.</small><button type="submit" class="btn btn-brand">Gửi bình luận <?= icon('arrow') ?></button></div>
    </form>
    <?php else: ?><p class="article-comment-login"><a class="btn btn-brand" data-auth-open href="<?= e(url('/login').'?return='.rawurlencode('/articles/'.$article['slug'].'#article-comments')) ?>">Đăng nhập để bình luận</a><span>Bạn vẫn có thể đọc các trao đổi bên dưới.</span></p><?php endif; ?>
    <p id="article-comment-feedback" class="article-comment-feedback" role="status" aria-live="polite" tabindex="-1"></p>
    <div class="article-comment-list" data-comment-list>
    <?php if(!$commentPage['items']): ?><p class="article-comment-empty">Chưa có bình luận. Bạn có câu hỏi về bài viết này?</p><?php endif; ?>
    <?php foreach($commentPage['items'] as $thread): ?>
    <div class="article-thread" data-thread-id="<?= $thread['id'] ?>">
        <?php $comment=$thread;require __DIR__.'/article-comment-item.php'; ?>
        <?php $replyExpanded=(int)($commentFocusId??0)!==$thread['id'] && in_array((int)($commentFocusId??0),array_column($thread['replies'],'id'),true); ?>
        <details class="article-reply-disclosure" data-reply-disclosure <?= $replyExpanded?'open':'' ?> <?= $thread['reply_count']?'':'hidden' ?>>
        <summary><span class="article-replies-show">Xem <?= (int)$thread['reply_count'] ?> câu trả lời</span><span class="article-replies-hide">Ẩn câu trả lời</span></summary>
        <div class="article-comment-replies" data-comment-replies>
            <?php foreach($thread['replies'] as $comment)require __DIR__.'/article-comment-item.php'; ?>
        </div>
        <div class="article-reply-pagination">
            <?php if(!$thread['replies_from_start']): ?><a href="<?= e(url('/articles/'.$article['slug']).'?comment='.$thread['id'].'#article-comment-'.$thread['id']) ?>" data-replies-start data-root-id="<?= $thread['id'] ?>">Xem cuộc trao đổi từ đầu</a><?php endif; ?>
            <a href="<?= e(url('/articles/'.$article['slug']).'?comment='.(int)$thread['next_reply_after'].'#article-comment-'.$thread['id']) ?>" data-replies-more data-root-id="<?= $thread['id'] ?>" data-after="<?= (int)($thread['next_reply_after']??0) ?>" <?= $thread['next_reply_after']?'':'hidden' ?>>Xem thêm phản hồi</a>
        </div>
        </details>
    </div>
    <?php endforeach; ?>
    </div>
    <a class="article-comments-more btn btn-outline-secondary" data-comments-more <?= $commentPage['next_before']?'':'hidden' ?> href="<?= e(url('/articles/'.$article['slug']).'?before='.(int)($commentPage['next_before']??0).'#article-comments') ?>" data-before="<?= (int)($commentPage['next_before']??0) ?>">Xem bình luận trước</a>
</section>

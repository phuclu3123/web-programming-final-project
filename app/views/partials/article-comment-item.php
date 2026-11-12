<?php /* Nội dung và hành động dùng chung cho bình luận gốc và phản hồi. */
$commentId=(int)$comment['id'];
$commentLink=url('/articles/'.$article['slug']).'?comment='.$commentId;
?>
<article class="article-comment<?= $comment['root_comment_id']?' article-comment--reply':'' ?>" id="article-comment-<?= $commentId ?>" data-comment-id="<?= $commentId ?>" data-comment-author="<?= e($comment['author_name']) ?>">
    <?php if(!$comment['removed']): ?>
    <div class="article-comment-meta"><strong><?= e($comment['author_name']) ?></strong><span class="article-comment-role article-comment-role--<?= e(strtolower($comment['author_role'])) ?>"><?= e($comment['author_role_label']) ?></span><time datetime="<?= e(str_replace(' ','T',$comment['created_at']).'Z') ?>"><?= e($comment['created_at_display']) ?></time></div>
    <?php if($comment['parent_comment_id']): ?><p class="article-reply-context">Trả lời <?= e($comment['reply_to_name']) ?></p><?php endif; ?>
    <?php endif; ?>
    <p class="article-comment-body<?= $comment['removed']?' text-muted':'' ?>"><?= e($comment['body']) ?></p>
    <?php if(!$comment['removed']): ?><div class="article-comment-actions">
        <?php if($commentViewer): ?>
        <form method="post" action="<?= e(url('/api/article-comments/'.$commentId.'/react')) ?>" data-comment-react>
            <input type="hidden" name="csrf" value="<?= e(\App\Core\Session::csrfToken()) ?>"><input type="hidden" name="liked" value="<?= $comment['liked']?'0':'1' ?>">
            <button type="submit" class="article-comment-like<?= $comment['liked']?' is-liked':'' ?>" aria-pressed="<?= $comment['liked']?'true':'false' ?>"><span aria-hidden="true"><?= $comment['liked']?'♥':'♡' ?></span> <span data-like-label><?= $comment['liked']?'Đã thích':'Thích' ?></span> <span data-like-count><?= (int)$comment['like_count'] ?></span></button>
        </form>
        <a href="<?= e($commentLink.'&reply='.$commentId.'#article-comment-form') ?>" data-comment-reply>Trả lời</a>
        <?php else: ?>
        <?php $signIn=url('/login').'?return='.rawurlencode('/articles/'.$article['slug'].'?comment='.$commentId.'&reply='.$commentId.'#article-comment-form'); ?>
        <a href="<?= e($signIn) ?>" data-auth-open="login" aria-label="Đăng nhập để thích bình luận"><span aria-hidden="true">♡</span> Thích <span><?= (int)$comment['like_count'] ?></span></a>
        <a href="<?= e($signIn) ?>" data-auth-open="login">Trả lời</a>
        <?php endif; ?>
        <?php if($comment['can_delete']): ?><form method="post" action="<?= e(url('/api/article-comments/'.$commentId.'/delete')) ?>" data-comment-delete><input type="hidden" name="csrf" value="<?= e(\App\Core\Session::csrfToken()) ?>"><button type="submit" class="article-comment-delete">Xóa</button></form><?php endif; ?>
    </div><?php endif; ?>
</article>

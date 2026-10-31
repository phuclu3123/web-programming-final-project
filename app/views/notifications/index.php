<section class="discovery-page notification-page">
    <div class="studio-section-title">
        <div><span class="eyebrow">TÀI KHOẢN CỦA BẠN</span>
            <h1>Thông báo</h1>
            <p>Kết quả kiểm duyệt và những việc cần theo dõi.</p>
        </div>
    </div>
    <?php if ($notifications): ?>
    <form method="post" action="<?= e(url('/notifications/read')) ?>"><input type="hidden" name="csrf"
            value="<?= e(\App\Core\Session::token()) ?>"><button class="btn btn-outline-brand" type="submit">Đánh dấu
            tất cả đã đọc</button></form>
    <div class="notification-list">
        <?php foreach ($notifications as $item): ?><a class="notif-item <?= $item['unread'] ? 'unread' : '' ?>"
            href="<?= e(url($item['path'])) ?>"><strong><?= e($item['title']) ?></strong><time><?= e(format_db_datetime($item['occurred_at'])) ?></time><?= icon('arrow') ?></a><?php endforeach; ?>
    </div>
    <?php else: ?><div class="member-empty"><strong>Bạn đã cập nhật mọi thứ.</strong>
        <p>Thông báo mới sẽ xuất hiện khi coupon hoặc yêu cầu shop được xử lý.</p>
    </div><?php endif; ?>
</section>
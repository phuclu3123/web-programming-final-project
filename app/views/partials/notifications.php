<?php
$notificationItems = $currentUser
    ? (new \App\Services\NotificationService(new \App\Repositories\NotificationRepository(\App\Core\Database::connection())))->feed($currentUser)
    : [];
$unreadCount = count(array_filter($notificationItems, static fn($item) => $item['unread']));
?>
<details class="header-notification-wrapper" data-account-dropdown>
    <summary class="header-search header-notification"
        aria-label="Thông báo<?= $unreadCount ? ' · '.$unreadCount.' chưa đọc' : '' ?>" title="Thông báo">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
            aria-hidden="true">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9M10 21h4" />
        </svg>
        <?php if ($unreadCount): ?><span class="notification-dot"></span><?php endif; ?>
    </summary>
    <div class="notification-dropdown">
        <div class="notif-header"><strong>Thông báo</strong>
            <?php if ($unreadCount): ?><form method="post" action="<?= e(url('/notifications/read')) ?>"
                data-notifications-read><input type="hidden" name="csrf"
                    value="<?= e(\App\Core\Session::token()) ?>"><button type="submit" class="mark-as-read">Đánh dấu đã
                    đọc</button></form><?php endif; ?>
        </div>
        <div class="notif-body">
            <?php foreach (array_slice($notificationItems,0,5) as $item): ?>
            <a href="<?= e(url($item['path'])) ?>" class="notif-item <?= $item['unread'] ? 'unread' : '' ?>">
                <p class="notif-title"><?= e($item['title']) ?></p><time
                    class="notif-time"><?= e(format_db_datetime($item['occurred_at'])) ?></time>
            </a>
            <?php endforeach; ?>
            <?php if (!$notificationItems): ?><p class="notification-empty">
                <?= $currentUser ? 'Bạn chưa có thông báo mới.' : 'Đăng nhập để theo dõi coupon và kết quả duyệt của bạn.' ?>
            </p><?php endif; ?>
        </div>
        <div class="notif-footer"><a href="<?= e(url($currentUser ? '/notifications' : '/login.php')) ?>"
                <?= !$currentUser ? 'data-auth-open="login"' : '' ?>><?= $currentUser ? 'Xem tất cả thông báo' : 'Đăng nhập' ?></a>
        </div>
        <p class="notification-feedback" role="status" hidden></p>
    </div>
</details>
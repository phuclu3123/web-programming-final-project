<?php

declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

use App\Contracts\NotificationRepositoryInterface;
use App\Core\Session;
use App\Services\NotificationService;
use App\Services\PublicHeaderComposer;

function verifyHeader(bool $ok, string $description): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $description);
}

$repo = new class implements NotificationRepositoryInterface {
    public int $calls = 0;
    public function forActor(array $actor): array
    {
        $this->calls++;
        verifyHeader((int)$actor['id'] === 7, 'actor identity passed through');
        return [
            ['event_key' => 'a', 'title' => 'Đã đọc', 'path' => '/notifications', 'occurred_at' => '2026-11-01 12:00:00'],
            ['event_key' => 'b', 'title' => '<script>danger</script>', 'path' => '/notifications', 'occurred_at' => '2026-11-02 13:00:00'],
        ];
    }
};

$composer = new PublicHeaderComposer(new NotificationService($repo));
verifyHeader($composer->compose(null) === [] && $repo->calls === 0, 'guest does not query DB');
Session::put('notification_read_7', ['a']);
$actor = ['id' => 7, 'role' => 'USER'];
$items = $composer->compose($actor);
verifyHeader($repo->calls === 1 && count($items) === 2, 'one feed for logged-in actor');
verifyHeader($items[0]['unread'] === false && $items[1]['unread'] === true, 'read flags preserved');

// The partial must render precomputed data and escape notification titles.
$currentUser = $actor;
$notificationItems = $items;
ob_start();
try {
    require __DIR__ . '/../app/views/partials/notifications.php';
    $html = (string)ob_get_contents();
} finally {
    ob_end_clean();
}
verifyHeader($repo->calls === 1, 'rendering does not trigger a second query');
verifyHeader(str_contains($html, '1 chưa đọc'), 'unread badge reflects feed');
verifyHeader(!str_contains($html, '<script>danger</script>'), 'unsafe text is escaped');
verifyHeader(str_contains($html, '&lt;script&gt;danger&lt;/script&gt;'), 'safe escaped title rendered');

$currentUser = null;
$notificationItems = $composer->compose(null);
ob_start();
try {
    require __DIR__ . '/../app/views/partials/notifications.php';
    $guestHtml = (string)ob_get_contents();
} finally {
    ob_end_clean();
}
verifyHeader(str_contains($guestHtml, 'Đăng nhập'), 'guest notification panel retained');
verifyHeader($repo->calls === 1, 'guest view does not query DB');

echo "PASS: public header notifications are composed outside the View (no database).\n";

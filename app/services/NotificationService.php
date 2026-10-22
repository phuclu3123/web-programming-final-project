<?php
declare(strict_types=1);
namespace App\Services;

use App\Core\Session;
use App\Contracts\NotificationRepositoryInterface;

final class NotificationService
{
    public function __construct(private NotificationRepositoryInterface $notifications) {}

    public function feed(array $actor): array
    {
        $read = Session::get('notification_read_' . (int)$actor['id'], []);
        return array_map(static function (array $item) use ($read): array {
            $item['unread'] = !in_array($item['event_key'], $read, true);
            return $item;
        }, $this->notifications->forActor($actor));
    }

    public function markAllRead(array $actor): void
    {
        // Session-scoped receipts survive navigation, never mix different actors.
        $key = 'notification_read_' . (int)$actor['id'];
        $read = array_unique(array_merge(Session::get($key, []), array_column($this->feed($actor), 'event_key')));
        Session::put($key, array_slice(array_values($read), -100));
    }
}

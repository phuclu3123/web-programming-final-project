<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\NotificationRepositoryInterface;

// Lọc và trình bày thông báo theo actor; trạng thái đã đọc được lưu phía server.
final class NotificationService
{
    public function __construct(private NotificationRepositoryInterface $notifications) {}

    public function feed(array $actor): array
    {
        return array_map(static function (array $item): array {
            $item['unread'] = empty($item['read_at']);
            return $item;
        }, $this->notifications->forActor($actor));
    }

    public function markAllRead(array $actor): void
    {
        $this->notifications->markRead($actor,array_column($this->feed($actor),'event_key'),true);
    }
    public function markOneRead(array $actor,string $eventKey):void
    {
        // Chỉ nhận sự kiện đang thuộc feed của actor, không nhận recipient ID từ client.
        if(!in_array($eventKey,array_column($this->feed($actor),'event_key'),true))throw new \App\Core\HttpError(404,'Không tìm thấy thông báo.');
        $this->notifications->markRead($actor,[$eventKey]);
    }
}

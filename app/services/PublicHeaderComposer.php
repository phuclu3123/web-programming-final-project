<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\NotificationRepository;

/** Provides data to the shared public header outside the rendering templates. */
final class PublicHeaderComposer
{
    public function __construct(private ?NotificationService $notifications = null) {}

    /** @return array<int, array<string, mixed>> */
    public function compose(?array $actor): array
    {
        // Guests must not trigger a database connection for the notification menu.
        if ($actor === null) return [];

        $service = $this->notifications
            ?? new NotificationService(new NotificationRepository(Database::connection()));
        return $service->feed($actor);
    }
}

<?php
declare(strict_types=1);
namespace App\Repositories;

use RuntimeException;

/** Recheck and hold the actor role for the duration of an administrative write. */
trait AdminWriteGuard
{
    private function requireActiveAdmin(int $actorId): void
    {
        $actor = $this->db->prepare("SELECT id FROM users WHERE id=? AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL LOCK IN SHARE MODE");
        $actor->execute([$actorId]);
        if (!$actor->fetch()) throw new RuntimeException('Chỉ quản trị viên đang hoạt động được thực hiện thao tác này.');
    }
}

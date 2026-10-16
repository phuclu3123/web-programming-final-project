<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\MysqliConnection;

final class PasswordResetRepository
{
    public function __construct(private MysqliConnection $db) {}

    /** Prevent abuse of unknown email addresses and of a particular account. */
    public function canRequest(string $ipHash, ?int $userId): bool
    {
        $ip = $this->db->prepare(
            'SELECT COUNT(*) FROM password_reset_tokens WHERE request_ip_hash=? '
            . 'AND created_at > UTC_TIMESTAMP() - INTERVAL 1 HOUR'
        );
        $ip->execute([$ipHash]);
        if ((int) $ip->fetchColumn() >= 10) {
            return false;
        }
        if ($userId === null) {
            return true;
        }
        $user = $this->db->prepare(
            'SELECT COUNT(*) FROM password_reset_tokens WHERE user_id=? '
            . 'AND created_at > UTC_TIMESTAMP() - INTERVAL 5 MINUTE'
        );
        $user->execute([$userId]);
        return (int) $user->fetchColumn() === 0;
    }

    public function save(int $userId, string $tokenHash, string $ipHash): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO password_reset_tokens (user_id, token_hash, request_ip_hash, expires_at) '
            . 'VALUES (?, ?, ?, UTC_TIMESTAMP() + INTERVAL 30 MINUTE)'
        );
        $stmt->execute([$userId, $tokenHash, $ipHash]);
    }

    public function delete(string $tokenHash): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_reset_tokens WHERE token_hash=? AND used_at IS NULL');
        $stmt->execute([$tokenHash]);
    }

    public function isUsable(string $tokenHash): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM password_reset_tokens p JOIN users u ON u.id=p.user_id "
            . "WHERE p.token_hash=? AND p.used_at IS NULL AND p.expires_at>UTC_TIMESTAMP() "
            . "AND u.status='ACTIVE' AND u.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$tokenHash]);
        return (bool) $stmt->fetchColumn();
    }

    public function consume(string $tokenHash, string $newPasswordHash): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'SELECT p.user_id FROM password_reset_tokens p JOIN users u ON u.id=p.user_id '
                . "WHERE p.token_hash=? AND p.used_at IS NULL AND p.expires_at>UTC_TIMESTAMP() "
                . "AND u.status='ACTIVE' AND u.deleted_at IS NULL FOR UPDATE"
            );
            $stmt->execute([$tokenHash]);
            $userId = $stmt->fetchColumn();
            $stmt->closeCursor();
            if ($userId === false) {
                $this->db->rollBack();
                return false;
            }
            $update = $this->db->prepare(
                'UPDATE users SET password_hash=?, auth_version=auth_version+1 WHERE id=? AND status=\'ACTIVE\' AND deleted_at IS NULL'
            );
            $update->execute([$newPasswordHash, (int) $userId]);
            if ($update->rowCount() !== 1) {
                $this->db->rollBack();
                return false;
            }
            $invalidate = $this->db->prepare(
                'UPDATE password_reset_tokens SET used_at=UTC_TIMESTAMP() WHERE user_id=? AND used_at IS NULL'
            );
            $invalidate->execute([(int) $userId]);
            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}

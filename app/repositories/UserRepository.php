<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class UserRepository implements \App\Contracts\UserProfileRepositoryInterface
{
    public function __construct(private Database $db)
    {
    }

    // ---- AUTH CHUNG: giữ account=email/username và các trường UI hiện tại ----
    public function findByLogin(string $account): ?array
    {
        $statement = $this->db->prepare('CALL ch_find_user_by_login(?)');
        $statement->execute([trim($account)]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }
    public function create(array $data): int
    {
        $statement = $this->db->prepare('CALL ch_create_user(?,?,?,?,?)');
        $statement->execute([$data['full_name'], $data['username'], $data['email'], $data['phone'], $data['password_hash']]);
        return (int) $statement->fetch()['user_id'];
    }

    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, username, phone, password_hash, full_name, avatar_path, role, status, auth_version, deleted_at, created_at, updated_at ' .
            'FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, email, username, phone, full_name, avatar_path, role, status, auth_version, deleted_at, created_at, updated_at ' .
            'FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return (bool) $stmt->fetchColumn();
    }

    public function updateProfile(int $userId, string $name, string $phone): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET full_name=?, phone=? WHERE id=? AND status='ACTIVE' AND deleted_at IS NULL");
        $stmt->execute([$name, $phone, $userId]);
        $stmt->closeCursor();
        // Zero affected rows can also mean the data was unchanged.
        return $this->findById($userId) !== null;
    }

    public function passwordHashForUser(int $userId): ?string
    {
        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id=? AND status='ACTIVE' AND deleted_at IS NULL");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        return is_string($hash) ? $hash : null;
    }

    public function changePassword(int $userId, string $oldHash, string $newHash): bool
    {
        // Guard against a concurrent password change and invalidate all sessions.
        $stmt = $this->db->prepare("UPDATE users SET password_hash=?, auth_version=auth_version+1 WHERE id=? AND password_hash=? AND status='ACTIVE' AND deleted_at IS NULL");
        $stmt->execute([$newHash, $userId, $oldHash]);
        return $stmt->rowCount() === 1;
    }

    /** @return array<string,mixed> */
    public function rewardSummary(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT ul.earned_points, ul.level_name, cs.submitted_count, cs.approved_count, cs.rejected_count ' .
            'FROM v_user_level ul JOIN v_contributor_stats cs ON cs.user_id = ul.user_id WHERE ul.user_id = ? LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!is_array($row)) {
            return [
                'earned_points' => 0,
                'level_name' => 'Newbie',
                'submitted_count' => 0,
                'approved_count' => 0,
                'rejected_count' => 0,
                'next_level_name' => null,
                'next_level_points' => null,
            ];
        }

        $next = $this->db->prepare(
            'SELECT name, min_points FROM levels WHERE min_points > ? ORDER BY min_points ASC LIMIT 1'
        );
        $next->execute([(int) $row['earned_points']]);
        $nextRow = $next->fetch();

        $row['next_level_name'] = is_array($nextRow) ? (string) $nextRow['name'] : null;
        $row['next_level_points'] = is_array($nextRow) ? (int) $nextRow['min_points'] : null;
        return $row;
    }

    /** @return array<int,array<string,mixed>> */
    public function pointHistory(int $userId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = $this->db->prepare(
            'SELECT p.id, p.points, p.rule_code, p.note, p.created_at, c.id AS coupon_id, c.title AS coupon_title ' .
            'FROM point_transactions p JOIN coupons c ON c.id = p.coupon_id ' .
            'WHERE p.user_id = ? ORDER BY p.created_at DESC, p.id DESC LIMIT ' . $limit
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

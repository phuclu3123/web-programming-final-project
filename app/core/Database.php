<?php
declare(strict_types=1);

namespace App\Core;

use App\Contracts\RoutineGateway;
use LogicException;
use mysqli;

/** Một kết nối mysqli cho repository, stored procedure và transaction trong request. */
final class Database implements RoutineGateway
{
    private const ROUTINES = [
        'ch_public_catalog_v3', 'ch_shop_dashboard_v2',
        'ch_create_coupon', 'ch_admin_dashboard_v2',
        'ch_approve_coupon', 'ch_reject_coupon', 'ch_find_user_by_login',
        // ---- PHẦN ĐĂNG KÝ (BỔ SUNG): procedure do bạn tự tạo trong MySQL ----
        'ch_create_user',
        // ---- SESSION/GUARD: định nghĩa chung trong database/schema.sql ----
        'ch_auth_user', 'ch_owned_merchant',
    ];

    private static ?self $shared = null;
    private static ?array $sharedConfig = null;
    private bool $transaction = false;

    private function __construct(private readonly mysqli $connection) {}

    // ---- KẾT NỐI: chỉ mở khi code thực sự cần truy cập database ----
    public static function connection(?array $config = null): self
    {
        $config ??= require __DIR__ . '/../../config/database.php';
        if (self::$shared !== null) {
            if (self::$sharedConfig !== $config) throw new LogicException('Chỉ dùng một cấu hình DB trong request.');
            return self::$shared;
        }
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $connection = new mysqli($config['host'], $config['username'], $config['password'], $config['database'], (int) $config['port']);
        $connection->set_charset('utf8mb4');
        $connection->query("SET collation_connection = 'utf8mb4_vietnamese_ci'");
        $connection->query("SET time_zone = '+00:00'");
        self::$sharedConfig = $config;
        return self::$shared = new self($connection);
    }

    public function prepare(string $sql): Statement
    {
        return new Statement($this->connection->prepare($sql));
    }
    public function query(string $sql): Statement
    {
        $statement = $this->prepare($sql);
        $statement->execute();
        return $statement;
    }
    public function beginTransaction(): bool
    {
        if ($this->transaction) throw new LogicException('Không mở transaction lồng nhau.');
        $result = $this->connection->begin_transaction();
        if ($result) $this->transaction = true;
        return $result;
    }
    public function commit(): bool
    {
        $result = $this->connection->commit();
        if ($result) $this->transaction = false;
        return $result;
    }
    public function rollBack(): bool
    {
        $result = $this->connection->rollback();
        if ($result) $this->transaction = false;
        return $result;
    }
    public function inTransaction(): bool { return $this->transaction; }
    public function lastInsertId(): string { return (string) $this->connection->insert_id; }

    // ---- ROUTINE: chỉ gọi tên procedure nằm trong allowlist ----
    public function call(string $routine, array $parameters = []): array
    {
        if (!in_array($routine, self::ROUTINES, true)) throw new LogicException('Routine không được phép gọi.');
        $placeholders = implode(',', array_fill(0, count($parameters), '?'));
        $statement = $this->prepare("CALL {$routine}({$placeholders})");
        try {
            $statement->execute($parameters);
            $sets = [];
            do { $sets[] = $statement->fetchAll(); } while ($statement->nextRowset());
            return $sets;
        } finally { $statement->closeCursor(); }
    }
}

<?php

declare(strict_types=1);

namespace App\Core;

use mysqli;
use mysqli_sql_exception;

/**
 * Shared MySQLi connection. Domain repositories do not own connections.
 * SQL is executed by native MySQLi prepared statements (driver MySQLi only).
 */
final class MysqliConnection
{
    private bool $transactionActive = false;

    public function __construct(private mysqli $link) {}

    public function prepare(string $sql): MysqliStatement
    {
        return new MysqliStatement($this->link->prepare($sql));
    }

    public function query(string $sql): MysqliStatement
    {
        $statement = $this->prepare($sql);
        $statement->execute();
        return $statement;
    }

    public function exec(string $sql): int
    {
        $statement = $this->query($sql);
        $affected = $statement->rowCount();
        $statement->closeCursor();
        return $affected;
    }

    public function lastInsertId(): string
    {
        return (string) $this->link->insert_id;
    }

    public function beginTransaction(): void
    {
        if ($this->transactionActive) {
            throw new \RuntimeException('Một giao dịch MySQL đang được thực hiện.');
        }
        $this->link->begin_transaction();
        $this->transactionActive = true;
    }

    public function commit(): void
    {
        $this->link->commit();
        $this->transactionActive = false;
    }

    public function rollBack(): void
    {
        $this->link->rollback();
        $this->transactionActive = false;
    }

    public function inTransaction(): bool
    {
        return $this->transactionActive;
    }
}

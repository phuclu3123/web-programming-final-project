<?php

declare(strict_types=1);

namespace App\Core;

use mysqli_result;
use mysqli_stmt;

/**
 * Repository-facing statement facade backed exclusively by mysqli_stmt.
 * Result sets are buffered so stored procedures can safely return multiple
 * result sets without "commands out of sync" on the shared connection.
 */
final class MysqliStatement
{
    /** @var array<int,array<string,mixed>> */
    private array $rows = [];
    private int $rowPosition = 0;
    private int $affectedRows = 0;
    /** @var array<int,mixed> */
    private array $boundValues = [];
    private bool $closed = false;

    public function __construct(private mysqli_stmt $statement) {}

    /**
     * Supports 1-based positional bindings used for paginated LIMIT/OFFSET.
     * Actual parameter typing is determined by bind_param in execute().
     */
    public function bindValue(int $position, mixed $value): void
    {
        if ($position < 1) {
            throw new \InvalidArgumentException('Vị trí tham số phải bắt đầu từ 1.');
        }
        $this->boundValues[$position] = $value;
    }

    /** @param array<int,mixed>|null $parameters */
    public function execute(?array $parameters = null): bool
    {
        if ($this->closed) {
            throw new \RuntimeException('Không thể thực thi statement đã đóng.');
        }
        // Also handles reuse of a prepared statement in seed scripts.
        $this->drainRemainingResults();
        $values = $parameters !== null ? array_values($parameters) : $this->orderedBoundValues();
        if ($values !== []) {
            if (count($values) !== $this->statement->param_count) {
                throw new \InvalidArgumentException('Số tham số SQL không khớp với câu lệnh đã chuẩn bị.');
            }
            $types = '';
            foreach ($values as $value) {
                $types .= is_int($value) || is_bool($value) ? 'i' : (is_float($value) ? 'd' : 's');
            }
            // mysqli_stmt::bind_param uses references; unpacking this local array
            // provides mutable slots for each parameter until execute completes.
            $this->statement->bind_param($types, ...$values);
        } elseif ($this->statement->param_count > 0) {
            throw new \InvalidArgumentException('Thiếu tham số cho câu lệnh SQL.');
        }
        $ok = $this->statement->execute();
        $this->affectedRows = (int) $this->statement->affected_rows;
        $this->loadCurrentResultSet();
        return $ok;
    }

    /** @return array<string,mixed>|false */
    public function fetch(): array|false
    {
        if (!isset($this->rows[$this->rowPosition])) {
            return false;
        }
        return $this->rows[$this->rowPosition++];
    }

    /** @return array<int,array<string,mixed>> */
    public function fetchAll(): array
    {
        $remaining = array_slice($this->rows, $this->rowPosition);
        $this->rowPosition = count($this->rows);
        return $remaining;
    }

    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->fetch();
        if ($row === false) {
            return false;
        }
        $values = array_values($row);
        return $values[$column] ?? false;
    }

    public function rowCount(): int
    {
        return $this->affectedRows;
    }

    public function nextRowset(): bool
    {
        if ($this->closed) {
            return false;
        }
        $this->rows = [];
        $this->rowPosition = 0;
        $hasNext = $this->statement->next_result();
        if ($hasNext) {
            $this->loadCurrentResultSet();
        }
        return $hasNext;
    }

    public function closeCursor(): void
    {
        if ($this->closed) {
            return;
        }
        try {
            $this->drainRemainingResults();
        } finally {
            $this->statement->close();
            $this->closed = true;
            $this->rows = [];
            $this->rowPosition = 0;
        }
    }

    private function drainRemainingResults(): void
    {
        if ($this->closed) {
            return;
        }
        while ($this->statement->more_results() && $this->statement->next_result()) {
            $this->loadCurrentResultSet();
        }
        $this->rows = [];
        $this->rowPosition = 0;
    }

    /** @return array<int,mixed> */
    private function orderedBoundValues(): array
    {
        if ($this->boundValues === []) {
            return [];
        }
        ksort($this->boundValues, SORT_NUMERIC);
        return array_values($this->boundValues);
    }

    private function loadCurrentResultSet(): void
    {
        $this->rows = [];
        $this->rowPosition = 0;
        $result = $this->statement->get_result();
        if ($result instanceof mysqli_result) {
            $this->rows = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
        }
    }

    public function __destruct()
    {
        try {
            $this->closeCursor();
        } catch (\Throwable) {
            // Destructors must not mask an earlier database exception.
        }
    }
}

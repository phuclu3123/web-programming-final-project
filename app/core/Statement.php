<?php
declare(strict_types=1);
namespace App\Core;

use InvalidArgumentException;
use mysqli_stmt;
use mysqli_result;

/** ---- PREPARED STATEMENT: bind dữ liệu, đọc hết CALL để tránh commands out of sync ---- */
final class Statement
{
    private array $bound = [];
    private array $types = [];
    private array $sets = [];
    private int $set = 0;
    private int $row = 0;
    private int $affected = 0;
    public function __construct(private readonly mysqli_stmt $statement) {}

    public function bindValue(int $position, mixed $value, ?string $type = null): bool
    {
        if ($position < 1 || ($type !== null && !in_array($type, ['i', 'd', 's'], true))) {
            throw new InvalidArgumentException('Vị trí hoặc kiểu bind không hợp lệ.');
        }
        $this->bound[$position] = $value;
        $this->types[$position] = $type;
        return true;
    }
    public function execute(?array $parameters = null): bool
    {
        $this->closeCursor();
        if ($parameters === null) {
            ksort($this->bound);
            $parameters = array_values($this->bound);
            $explicit = array_map(fn ($key) => $this->types[$key], array_keys($this->bound));
        } else { $explicit = []; }
        if (count($parameters) !== $this->statement->param_count) throw new InvalidArgumentException('Số tham số SQL không khớp.');
        if ($parameters !== []) {
            $types = '';
            foreach ($parameters as $i => $value) {
                $types .= $explicit[$i] ?? (is_int($value) ? 'i' : (is_float($value) ? 'd' : 's'));
            }
            $this->statement->bind_param($types, ...$parameters);
        }
        $this->statement->execute();
        $this->affected = $this->statement->affected_rows;
        // Buffer từng tập kết quả rồi xả toàn bộ kết quả MySQL trước câu query tiếp.
        do {
            $result = $this->statement->get_result();
            if ($result instanceof mysqli_result) {
                $this->sets[] = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
            }
        } while ($this->statement->more_results() && $this->statement->next_result());
        return true;
    }
    public function fetch(): array|false { return $this->sets[$this->set][$this->row++] ?? false; }
    public function fetchAll(): array
    {
        $rows = array_slice($this->sets[$this->set] ?? [], $this->row);
        $this->row += count($rows);
        return $rows;
    }
    public function fetchColumn(int $column = 0): mixed
    {
        $row = $this->fetch();
        return $row === false ? false : (array_values($row)[$column] ?? false);
    }
    public function nextRowset(): bool
    {
        if (!array_key_exists($this->set + 1, $this->sets)) return false;
        ++$this->set; $this->row = 0;
        return true;
    }
    public function closeCursor(): bool { $this->sets = []; $this->set = $this->row = 0; return true; }
    public function rowCount(): int { return $this->affected; }
    public function __destruct() { $this->statement->close(); }
}

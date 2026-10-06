<?php
declare(strict_types=1);

require_once __DIR__ . '/../contracts/RoutineGateway.php';

final class Database implements RoutineGateway
{
    private mysqli $connection;
    private const ROUTINES = [
        'ch_actor', 'ch_public_catalog_v2', 'ch_public_catalog_v3',
        'ch_shop_dashboard', 'ch_shop_dashboard_v2',
        'ch_create_coupon', 'ch_admin_dashboard', 'ch_admin_dashboard_v2',
        'ch_approve_coupon', 'ch_reject_coupon',
    ];

    public function __construct(array $config)
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $this->connection = new mysqli(
            $config['host'], $config['username'], $config['password'],
            $config['database'], $config['port']
        );
        $this->connection->set_charset('utf8mb4');
    }

    /** Every application query is a prepared CALL; SQL lives in MySQL routines. */
    public function call(string $routine, array $parameters = []): array
    {
        if (!in_array($routine, self::ROUTINES, true)) {
            throw new LogicException('Routine không được phép gọi.');
        }
        $placeholders = implode(',', array_fill(0, count($parameters), '?'));
        $statement = $this->connection->prepare("CALL {$routine}({$placeholders})");
        try {
            if ($parameters !== []) {
                $types = implode('', array_map(
                    static fn ($value): string => is_int($value) ? 'i' : 's', $parameters
                ));
                $statement->bind_param($types, ...$parameters);
            }
            $statement->execute();
            $sets = [];
            // CALL can return several result sets plus an empty final result.
            // Drain all of them before the next CALL to avoid "Commands out of sync".
            do {
                $result = $statement->get_result();
                if ($result instanceof mysqli_result) {
                    $sets[] = $result->fetch_all(MYSQLI_ASSOC);
                    $result->free();
                }
            } while ($statement->more_results() && $statement->next_result());
            return $sets;
        } finally {
            $statement->close();
        }
    }
}

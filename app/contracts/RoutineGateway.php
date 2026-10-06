<?php
declare(strict_types=1);

/**
 * Contract between the Model layer and the database infrastructure.
 * Models depend on this interface instead of a concrete mysqli connection.
 */
interface RoutineGateway
{
    /** @return array<int, array<int, array<string, mixed>>> */
    public function call(string $routine, array $parameters = []): array;
}

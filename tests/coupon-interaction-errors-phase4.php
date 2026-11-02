<?php

declare(strict_types=1);

/**
 * Controller error response regression contract. No database connection required.
 * Runs each probe in a subprocess because Response::json() terminates execution.
 */
if (($argv[1] ?? '') === '--probe') {
    $kind = $argv[2] ?? 'unexpected';
    $debug = $argv[3] ?? '0';
    putenv('COUPONHUB_DEBUG=' . $debug);
    require __DIR__ . '/../app/bootstrap.php';

    $error = $kind === 'expected'
        ? new RuntimeException('Dữ liệu không hợp lệ.')
        : new Exception('Sensitive infrastructure details');

    $controllerClass = new ReflectionClass(App\Controllers\CouponInteractionController::class);
    // Skip the database-backed constructor; only test the error-response boundary.
    $controller = $controllerClass->newInstanceWithoutConstructor();
    $handler = $controllerClass->getMethod('respondFailure');
    register_shutdown_function(static function (): void {
        fwrite(STDERR, '__HTTP_STATUS__=' . http_response_code() . "\n");
    });
    $handler->invoke($controller, $error, 'Không thể thao tác.');
    exit(99); // A correct handler exits via Response::json() before reaching here.
}

if (!function_exists('proc_open')) {
    fwrite(STDERR, "SKIP: proc_open is unavailable; unable to test Response::json exit semantics.\n");
    exit(2);
}

$cases = [
    ['expected', '0', 422, 'Dữ liệu không hợp lệ.'],
    ['expected', '1', 422, 'Dữ liệu không hợp lệ.'],
    ['unexpected', '0', 500, 'Không thể thao tác.'],
    ['unexpected', '1', 500, 'Sensitive infrastructure details'],
];
foreach ($cases as [$kind, $debug, $expectedStatus, $expectedMessage]) {
    $command = [PHP_BINARY, __FILE__, '--probe', $kind, $debug];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to create child PHP process.');
    }
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    $payload = json_decode($output, true);
    $expected = ['success' => false, 'message' => $expectedMessage];
    if ($exitCode !== 0 || $payload !== $expected || !str_contains($errors, '__HTTP_STATUS__=' . $expectedStatus)) {
        throw new RuntimeException("FAIL: {$kind} debug={$debug}\nstdout={$output}\nstderr={$errors}\nexit={$exitCode}");
    }
}

echo "PASS: 4 interaction controller error responses preserve status, payload and debug privacy (no database).\n";

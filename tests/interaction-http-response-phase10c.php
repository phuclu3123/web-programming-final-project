<?php

declare(strict_types=1);

// Subprocess needed: Response::json() calls exit. No database is initialized.
if (($argv[1] ?? '') === '--probe') {
    $status = (int) ($argv[2] ?? '429');
    require __DIR__ . '/../app/bootstrap.php';
    $controller = (new ReflectionClass(App\Controllers\CouponInteractionController::class))->newInstanceWithoutConstructor();
    register_shutdown_function(static function (): void {
        fwrite(STDERR, '__HTTP_STATUS__=' . http_response_code() . "\n");
    });
    (new ReflectionClass($controller))->getMethod('respondFailure')->invoke(
        $controller,
        new App\Core\HttpError($status, $status === 429 ? 'Bạn thao tác quá nhiều lần. Vui lòng thử lại sau.' : 'Dịch vụ đang bận.'),
        'Không thể gửi bình luận.'
    );
    exit(99);
}

foreach ([429, 503] as $status) {
    $process = proc_open([PHP_BINARY, __FILE__, '--probe', (string) $status], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start subprocess');
    $body = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $exit = proc_close($process);
    $json = json_decode($body, true);
    if ($exit !== 0 || ($json['success'] ?? null) !== false || !isset($json['message']) || !str_contains($stderr, '__HTTP_STATUS__=' . $status)) {
        throw new RuntimeException("FAIL: HTTP {$status} exit={$exit} body={$body} stderr={$stderr}");
    }
    echo "PASS: interaction controller preserves intentional HTTP {$status} JSON status\n";
}

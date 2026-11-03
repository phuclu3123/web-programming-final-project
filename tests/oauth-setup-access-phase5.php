<?php

declare(strict_types=1);

/** OAuth setup access gate: unauthorized visitors retain HTTP 401; no DB required. */
if (($argv[1] ?? '') === '--probe') {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_HOST'] = '127.0.0.1';
    // Proxy header disables the localhost-only bypass; no session user => 401.
    $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7';
    register_shutdown_function(static function (): void {
        fwrite(STDERR, '__HTTP_STATUS__=' . http_response_code() . "\n");
    });
    require __DIR__ . '/../oauth-setup.php';
    exit(99);
}

if (!function_exists('proc_open')) {
    fwrite(STDERR, "SKIP: proc_open disabled; cannot assert exit status.\n");
    exit(2);
}

$process = proc_open([PHP_BINARY, __FILE__, '--probe'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($process)) {
    throw new RuntimeException('Could not start OAuth setup probe.');
}
$output = stream_get_contents($pipes[1]);
$errors = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit = proc_close($process);
if ($exit !== 0 || !str_contains($errors, '__HTTP_STATUS__=401')
    || !str_contains($output, 'Không thể truy cập công cụ chuẩn bị OAuth.')) {
    throw new RuntimeException("FAIL: OAuth setup unauthorized response\nstdout={$output}\nstderr={$errors}\nexit={$exit}");
}
echo "PASS: OAuth setup returns HTTP 401 for unauthorized access without database.\n";

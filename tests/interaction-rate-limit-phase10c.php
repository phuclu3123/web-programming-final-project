<?php

declare(strict_types=1);

// Independent sandbox: no database, no network, no repository state changes.
$cache = sys_get_temp_dir() . '/couponhub-interaction-limit-' . bin2hex(random_bytes(8));
if (!mkdir($cache, 0700)) {
    throw new RuntimeException('Unable to create test cache');
}
putenv('COUPONHUB_RATE_LIMIT_DIR=' . $cache);
require __DIR__ . '/../app/bootstrap.php';
ob_start();

use App\Core\HttpError;
use App\Core\InteractionRateLimit;

function assertRate(bool $ok, string $description): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $description);
    echo 'PASS: ' . $description . PHP_EOL;
}

function expect429(callable $action): void
{
    try {
        $action();
    } catch (HttpError $error) {
        if ($error->status !== 429) throw new RuntimeException('Incorrect HTTP status: ' . $error->status);
        return;
    }
    throw new RuntimeException('Expected HTTP 429');
}

try {
    $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
    for ($i = 0; $i < 6; $i++) InteractionRateLimit::check('comment', 101);
    expect429(static fn() => InteractionRateLimit::check('comment', 101));
    assertRate(true, 'comment burst limited at 6 requests / 60s');

    // Multiple PHP sessions for one authenticated actor share the same bucket.
    $_SESSION = ['session_marker' => 'another-browser'];
    expect429(static fn() => InteractionRateLimit::check('comment', 101));
    InteractionRateLimit::check('comment', 102);
    assertRate(true, 'per-user counters persist across sessions and separate actors');

    for ($i = 0; $i < 3; $i++) InteractionRateLimit::check('report', 101);
    expect429(static fn() => InteractionRateLimit::check('report', 101));
    assertRate(true, 'report submission limited at 3 requests / 15min');

    for ($i = 0; $i < 40; $i++) InteractionRateLimit::check('vote', 101);
    expect429(static fn() => InteractionRateLimit::check('vote', 101));
    for ($i = 0; $i < 60; $i++) InteractionRateLimit::check('favorite', 101);
    expect429(static fn() => InteractionRateLimit::check('favorite', 101));
    assertRate(true, 'vote/favorite limits are independent and use generous burst ceilings');

    // A wide shared-IP limit catches many distinct accounts without trusting X-Forwarded-For.
    $_SERVER['REMOTE_ADDR'] = '192.0.2.100';
    for ($i = 0; $i < 80; $i++) InteractionRateLimit::check('comment', 2000 + $i);
    expect429(static fn() => InteractionRateLimit::check('comment', 9999));
    $_SERVER['REMOTE_ADDR'] = '192.0.2.101';
    InteractionRateLimit::check('comment', 9999);
    assertRate(true, 'shared-IP comment ceiling and separation between IPs');

    try {
        InteractionRateLimit::check('unknown', 101);
        throw new RuntimeException('Invalid action unexpectedly accepted');
    } catch (InvalidArgumentException) {}
    assertRate(true, 'unknown action rejected rather than silently disabling limits');
} finally {
    foreach (new DirectoryIterator($cache) as $entry) {
        if ($entry->isDot()) continue;
        if ($entry->isFile()) unlink($entry->getPathname());
    }
    rmdir($cache);
}

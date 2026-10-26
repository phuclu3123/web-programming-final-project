<?php

declare(strict_types=1);

use App\Contracts\CouponEventRepositoryInterface;
use App\Core\Session;
use App\Services\CouponEventService;

require __DIR__ . '/../app/bootstrap.php';

final class CouponEventFakeRepository implements CouponEventRepositoryInterface
{
    public array $events = [];
    public bool $fail = false;

    public function recordCopy(?int $actorId, int $couponId, string $requestKey): void
    {
        $this->record('COPY', $actorId, $couponId, $requestKey);
    }

    public function recordView(?int $actorId, int $couponId, string $requestKey): void
    {
        $this->record('VIEW', $actorId, $couponId, $requestKey);
    }

    public function recordShare(?int $actorId, int $couponId, string $requestKey): void
    {
        $this->record('SHARE', $actorId, $couponId, $requestKey);
    }

    private function record(string $type, ?int $actorId, int $couponId, string $requestKey): void
    {
        if ($this->fail) {
            throw new RuntimeException('Simulated database error');
        }
        $this->events[] = [$type, $actorId, $couponId, $requestKey];
    }
}

function verify(bool $ok, string $description): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $description);
}

session_save_path(sys_get_temp_dir());
Session::start();
unset($_SESSION['coupon_view_last'], $_SESSION['coupon_copy_last'], $_SESSION['coupon_share_last']);
$repo = new CouponEventFakeRepository();
$service = new CouponEventService($repo);

verify($service->recordView(null, 34), 'anonymous view recorded');
verify(!$service->recordView(null, 34), 'refresh not double counted within 15 minutes');
verify(count($repo->events) === 1, 'only one view persisted');
verify($service->recordView(8, 33), 'different coupon recorded');
verify($repo->events[1][1] === 8, 'authenticated actor id passed to repository');
verify($repo->events[1][2] === 33, 'correct coupon passed');

$_SESSION['coupon_view_last'][34] = time() - 901;
verify($service->recordView(null, 34), 'view recorded after 15-minute window');
verify(count($repo->events) === 3, 'second view after expiry persisted');

verify($service->recordCopy(8, 34), 'copy recorded');
verify(!$service->recordCopy(8, 34), 'copy duplicate within 15 seconds suppressed');
$_SESSION['coupon_copy_last'][34] = time() - 16;
verify($service->recordCopy(8, 34), 'copy recorded after short window');
verify(count($repo->events) === 5, 'copy and view throttle are independent');
verify($service->recordShare(8, 34), 'share event recorded');
verify(!$service->recordShare(8, 34), 'share deduplicated within 60 seconds');
$_SESSION['coupon_share_last'][34] = time() - 61;
verify($service->recordShare(8, 34), 'share event recorded after window');
verify(count($repo->events) === 7, 'share, copy and view independently tracked');
verify(count(array_unique(array_column($repo->events, 3))) === 7, 'event keys unique');

$repo->fail = true;
try {
    $service->recordView(8, 35);
    throw new RuntimeException('FAIL: database error must propagate to controller');
} catch (RuntimeException $e) {
    verify($e->getMessage() === 'Simulated database error', 'database error propagated');
}
verify(!isset($_SESSION['coupon_view_last'][35]), 'failed write does not throttle retry');
$repo->fail = false;
verify($service->recordView(8, 35), 'retry after database recovery');

$_SESSION['coupon_view_last'] = 'malformed';
verify($service->recordView(null, 50), 'invalid session state recovers safely');

try {
    $service->recordView(null, 0);
    throw new RuntimeException('FAIL: invalid coupon id accepted');
} catch (InvalidArgumentException $e) {
    // Expected.
}

echo "PASS: coupon view/copy event throttling, isolated session state, retry and request keys (no database).\n";

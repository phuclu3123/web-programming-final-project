<?php

declare(strict_types=1);

/** Read-only MySQLi smoke test for an existing CouponHub database. */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $parts = explode('\\', substr($class, strlen($prefix)));
    for ($i = 0; $i < count($parts) - 1; $i++) $parts[$i] = strtolower($parts[$i]);
    $path = $root . '/app/' . implode('/', $parts) . '.php';
    if (is_file($path)) require_once $path;
});
require_once $root . '/app/helpers.php';

\App\Core\App::init([
    'app' => require $root . '/config/app.php',
    'database' => require $root . '/config/database.php',
]);

try {
    $db = \App\Core\Database::connection();
    $collation = $db->query('SELECT @@collation_connection')->fetchColumn();
    $timezone = $db->query('SELECT @@session.time_zone')->fetchColumn();
    echo "PASS connection / mysqlnd; collation={$collation}; timezone={$timezone}\n";

    $users = $db->prepare('SELECT COUNT(*) FROM users WHERE id > ?');
    $users->execute([0]);
    echo 'PASS prepared SELECT; users=' . (int) $users->fetchColumn() . "\n";

    $search = $db->prepare('CALL sp_search_coupons(?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $search->execute([null, null, null, null, null, 0, 'NEWEST', 1, 3]);
    echo 'PASS sp_search_coupons; returned=' . count($search->fetchAll()) . "\n";
    $search->closeCursor();

    $owner = $db->query(
        "SELECT mm.user_id, mm.merchant_id FROM merchant_memberships mm " .
        "JOIN merchants m ON m.id=mm.merchant_id " .
        "WHERE mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL LIMIT 1"
    )->fetch();
    if (is_array($owner)) {
        $stats = $db->prepare('CALL sp_merchant_stats(?, ?)');
        $stats->execute([(int) $owner['user_id'], (int) $owner['merchant_id']]);
        $stats->fetch();
        if (!$stats->nextRowset()) throw new RuntimeException('Thiếu result set thứ hai.');
        $stats->fetch();
        if (!$stats->nextRowset()) throw new RuntimeException('Thiếu result set thứ ba.');
        $stats->fetch();
        $stats->closeCursor();
        echo "PASS sp_merchant_stats (3 result sets)\n";
    } else {
        echo "SKIP sp_merchant_stats (chưa có quyền Merchant ACTIVE)\n";
    }

    $db->beginTransaction();
    if (!$db->inTransaction()) throw new RuntimeException('Không vào được transaction.');
    $db->rollBack();
    echo "PASS transaction begin/rollback (không ghi dữ liệu)\n";
    echo "PASS ALL (read-only)\n";
} catch (Throwable $e) {
    fwrite(STDERR, "FAIL: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

<?php

declare(strict_types=1);

/** No-DB contract test: complements the isolated MySQL integration scenarios. */
$source = file_get_contents(__DIR__ . '/../app/repositories/CouponRepository.php');
$schema = file_get_contents(__DIR__ . '/../database/schema.sql');
if (!is_string($source) || !is_string($schema)) {
    throw new RuntimeException('Cannot load repository and SQL schema.');
}
function phase7Assert(bool $condition, string $description): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $description);
}

$search = strstr($source, 'public function search(array $filters): array');
phase7Assert(is_string($search), 'search method exists');
$search = strstr($search, 'public function countPublicSearch', true);
phase7Assert(is_string($search), 'search method boundary');
phase7Assert(str_contains($search, "SELECT id, name FROM categories WHERE id IN ("), 'only page category IDs are fetched');
phase7Assert(str_contains($search, 'array_unique') && str_contains($search, 'execute($categoryIds)'),
    'category IDs are deduplicated and parameter bound');
phase7Assert(!str_contains($search, "query('SELECT id, name FROM categories')"), 'no full category-table scan for badges');
phase7Assert(str_contains($search, "?? 'Khác'"), 'category fallback preserved');
phase7Assert(str_contains($search, 'submitter_name'), 'public contributor name preserved');

$detail = strstr($source, 'public function findPublic(int $id): ?array');
phase7Assert(is_string($detail), 'public detail method exists');
$detail = strstr($detail, 'public function isActive', true);
phase7Assert(is_string($detail), 'public detail method boundary');
phase7Assert(!str_contains($detail, 'submitter_email') && !str_contains($detail, 'u.email'),
    'public detail does not query contributor email');
phase7Assert(str_contains($detail, 'submitter_name'), 'public detail retains contributor display name');
$admin = strstr($source, 'public function pendingForModeration(): array');
phase7Assert(is_string($admin) && str_contains(strstr($admin, 'public function submitOfficial', true), 'submitter_email'),
    'private admin moderation attribution remains intact');

// Static SQL parity sentinels. They do not establish runtime equivalence; the
// isolated DB integration test covers real result-set/count behavior.
$count = strstr($source, 'public function countPublicSearch(array $filters): int');
phase7Assert(is_string($count), 'count method exists');
$count = strstr($count, 'public function findPublic', true);
$procedure = strstr($schema, 'CREATE PROCEDURE `sp_search_coupons`');
phase7Assert(is_string($count) && is_string($procedure), 'count and stored procedure available');
$procedure = strstr($procedure, 'END$$', true);
phase7Assert(is_string($procedure), 'stored procedure boundary');
foreach ([
    'keyword' => ['c.title LIKE ?', 'c.title LIKE CONCAT'],
    'category' => ['c.category_id = ?', 'c.category_id=p_category'],
    'merchant' => ['c.merchant_id = ?', 'c.merchant_id=p_merchant'],
    'discount type' => ['c.discount_type = ?', 'c.discount_type=p_type'],
    'usage channel' => ["c.usage_channel = 'BOTH'", "c.usage_channel='BOTH'"],
    'ending soon' => ['INTERVAL 3 DAY', 'INTERVAL 3 DAY'],
] as $case => [$php, $sql]) {
    phase7Assert(str_contains($count, $php) && str_contains($procedure, $sql), 'predicate parity sentinel: ' . $case);
}
echo "PASS: Phase 7 repository scope, public data minimization, and SQL predicate sentinels (no database).\n";

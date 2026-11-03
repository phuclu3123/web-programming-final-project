# CouponHub — Phase 7: Database Query & Repository Maintainability

Baseline: Git commit `668e160` (PR #11). Scope: source refactor + regression tests, **no schema migration**, **no changed search filter policy**.

## Source findings

1. `CouponRepository::search()` used to load every `categories` row for each **non-empty** coupon search page, although cards need only the IDs in the current result set. It now deduplicates the current page's `category_id` values, uses a bound `WHERE id IN (...)` query, and retains the `Khác` fallback. The stored procedure `sp_search_coupons` remains unchanged. The number of category rows fetched is bounded by the number of distinct category IDs on the page, rather than the whole table (actual query latency depends on the DB and indexes).
2. `CouponRepository::findPublic()` queried `users.email AS submitter_email`. The current public coupon-detail view uses `submitter_name`, not the email. The public query no longer retrieves the email. Admin moderation's `pendingForModeration()` still retrieves email for its existing workflow.
3. `countPublicSearch()` and `sp_search_coupons` duplicate eligibility/filter criteria across SQL/PHP. This is a **maintenance risk**, not a proven count bug. Their current common predicates include active status, keyword, merchant, category, discount type, usage channel, and ending-soon. The code retains both existing queries and adds static predicate sentinels plus isolated-DB count-vs-result checks to detect drift.

## Verification

- `node --test tests/*.test.cjs`: **52/52 PASS**.
- `php tests/coupon-repository-phase7.php`: **PASS**, no database required.
- `php tests/public-header-notifications-phase6.php`: **PASS**.
- `php tests/coupon-interaction-errors-phase4.php`: **PASS**.
- `php tests/oauth-setup-access-phase5.php`: **PASS**.
- PHP syntax checks on the changed PHP files: **PASS**.
- `tests/mysqli-integration.php`: extended with filtered search/count checks and a public-email data-minimization check, **NOT executed against MariaDB** in this audit. It refuses to run without an explicitly isolated schema-only database `couponhub_merge_test` on port `13379`. A `SKIP` is not a PASS.

## MySQL/MariaDB integration and performance follow-up

Only use an **isolated** database created specifically for integration tests; `tests/mysqli-integration.php` expects `COUPONHUB_RUN_DB_TEST=1`, `COUPONHUB_DB=couponhub_merge_test`, `COUPONHUB_PORT=13379`, no `config/local.php`, and an empty schema. See `tests/sql-install.py` for how the project manages its isolated MariaDB instance. That installer *resets/drops the test database*; never aim it at any shared database or the normal XAMPP application DB.

For **read-only** `EXPLAIN` on a disposable or read-only copy of application data (replace numeric IDs with valid IDs from the copy):

```sql
EXPLAIN SELECT id, name FROM categories WHERE id IN (1, 2, 3);

EXPLAIN SELECT COUNT(*)
FROM v_coupon_status c
JOIN merchants m ON m.id = c.merchant_id
JOIN v_coupon_vote_stats vs ON vs.coupon_id = c.id
JOIN v_coupon_favorite_stats fs ON fs.coupon_id = c.id
WHERE c.effective_status COLLATE utf8mb4_vietnamese_ci =
      _utf8mb4'ACTIVE' COLLATE utf8mb4_vietnamese_ci;
```

Inspect `type`, `possible_keys`, `key`, `rows`, derived view costs, and performance across representative dataset sizes. Small fixture data can make a table scan optimal; do not add/drop indexes based on `EXPLAIN` alone. Measure before proposing a schema change.

## Merge checklist

1. Apply changes on an up-to-date `Quang` working tree based on `668e160`, review `git diff`.
2. Run the 52 JS tests and no-DB PHP tests.
3. Test with the isolated MariaDB fixture and compare search/count across filters and active/pending status.
4. Smoke-test coupon list, AJAX search, pagination, categories badges, public details and admin pending review on localhost.
5. Commit Phase 7 separately. Do not push or create PR until explicitly requested.

-- CouponHub v2: many-to-many User <-> Merchant and two Favorite metrics.
-- Compatibility target: MySQL 8+ / MariaDB 10.4+.
-- Schema-only staging migration: deploying this ALONE will NOT update the legacy
-- permissions or point-awarding routines. Deploy corresponding PHP + routine changes
-- before relying on qualified_favorites for rewards. Back up the DB first.
-- Do not import couponhub_schema.sql again.
-- All dates are stored in UTC.

USE `couponhub`;
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';

-- 1) Actual access, independently per User–Shop pair.
-- An approved shop can have multiple active managers.
-- A user can manage multiple approved shops.
CREATE TABLE IF NOT EXISTS merchant_memberships (
    user_id BIGINT UNSIGNED NOT NULL,
    merchant_id BIGINT UNSIGNED NOT NULL,
    status ENUM('ACTIVE', 'REVOKED') NOT NULL DEFAULT 'ACTIVE',
    granted_by BIGINT UNSIGNED NULL,
    granted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revoked_by BIGINT UNSIGNED NULL,
    revoked_at DATETIME NULL,
    revoke_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, merchant_id),
    KEY idx_mm_merchant_status (merchant_id, status),
    KEY idx_mm_user_status (user_id, status),
    CONSTRAINT fk_mm_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_mm_merchant FOREIGN KEY (merchant_id) REFERENCES merchants(id),
    CONSTRAINT fk_mm_grant_actor FOREIGN KEY (granted_by) REFERENCES users(id),
    CONSTRAINT fk_mm_revoke_actor FOREIGN KEY (revoked_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2) Separate application/review history; REJECTED does not imply REVOKED.
-- Reapplications produce NEW request rows; current rights stay in memberships.
CREATE TABLE IF NOT EXISTS merchant_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    merchant_id BIGINT UNSIGNED NOT NULL,
    request_type ENUM('CREATE', 'JOIN', 'CLAIM', 'LEGACY') NOT NULL,
    status ENUM('PENDING', 'APPROVED', 'REJECTED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    message TEXT NULL,
    evidence_reference VARCHAR(1000) NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_mr_user_status (user_id, status),
    KEY idx_mr_merchant_status (merchant_id, status),
    KEY idx_mr_queue (status, created_at),
    CONSTRAINT fk_mr_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_mr_merchant FOREIGN KEY (merchant_id) REFERENCES merchants(id),
    CONSTRAINT fk_mr_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3) Compatibility backfill: approved legacy owner -> ACTIVE membership.
-- Does not invent proof/requests or grant access to pending/rejected merchants.
INSERT INTO merchant_memberships (
    user_id, merchant_id, status, granted_by, granted_at
)
SELECT m.owner_user_id, m.id, 'ACTIVE', m.reviewed_by,
       COALESCE(m.reviewed_at, m.created_at)
FROM merchants AS m
JOIN users AS u ON u.id = m.owner_user_id
WHERE m.owner_user_id IS NOT NULL
  AND m.status = 'APPROVED'
  AND m.deleted_at IS NULL
  AND u.deleted_at IS NULL
ON DUPLICATE KEY UPDATE user_id = merchant_memberships.user_id;

-- 4) Freeze ineligibility for manager/self-owned favorites.
-- This field means "still potentially eligible for reward" (sticky 0).
-- On each future INSERT the service/procedure MUST set it to 0 when the actor
-- authored the coupon or actively manages its merchant.
-- On each new membership APPROVAL the service MUST set the user's existing
-- favorites of this shop to 0 in the SAME TRANSACTION.
-- REVOKE must NOT change 0 back to 1.
SET @has_eligibility_column = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'favorites'
    AND COLUMN_NAME = 'is_eligible_for_rewards'
);
SET @alter_sql = IF(
  @has_eligibility_column = 0,
  'ALTER TABLE `favorites` ADD COLUMN `is_eligible_for_rewards` TINYINT(1) NOT NULL DEFAULT 1',
  'SELECT ''favorites.is_eligible_for_rewards already exists'' AS note'
);
PREPARE stmt_eligibility FROM @alter_sql;
EXECUTE stmt_eligibility;
DEALLOCATE PREPARE stmt_eligibility;

-- Legacy favorites: exclude own contributions and currently active managers.
-- Existing historical membership at the time of each save is unavailable:
-- this is a best-effort conservative backfill, NOT recovered historical proof.
UPDATE favorites AS f
JOIN coupons AS c ON c.id = f.coupon_id
LEFT JOIN merchant_memberships AS mm
  ON mm.user_id = f.user_id
 AND mm.merchant_id = c.merchant_id
 AND mm.status = 'ACTIVE'
SET f.is_eligible_for_rewards = 0
WHERE f.is_eligible_for_rewards <> 0
  AND (f.user_id = c.submitted_by OR mm.user_id IS NOT NULL);

-- 5) Computed metrics: keep favorites normalized, avoid stale cached counters.
-- total_favorites: every currently saved favorite, including managers.
-- qualified_favorites: not self-saved, not ineligible, not ACTIVE manager now.
-- No amount of favorites directly changes WORKING/NOT_WORKING vote counts.
CREATE OR REPLACE SQL SECURITY INVOKER VIEW v_coupon_favorite_stats AS
SELECT
    c.id AS coupon_id,
    COUNT(f.user_id) AS total_favorites,
    COALESCE(SUM(
        CASE
            WHEN f.user_id IS NOT NULL
             AND f.user_id <> c.submitted_by
             AND f.is_eligible_for_rewards = 1
             AND mm.user_id IS NULL
            THEN 1 ELSE 0
        END
    ), 0) AS qualified_favorites
FROM coupons AS c
LEFT JOIN favorites AS f ON f.coupon_id = c.id
LEFT JOIN merchant_memberships AS mm
    ON mm.user_id = f.user_id
   AND mm.merchant_id = c.merchant_id
   AND mm.status = 'ACTIVE'
GROUP BY c.id, c.submitted_by, c.merchant_id;

-- Inspect the two metrics after migration:
-- SELECT c.id, c.title, s.total_favorites, s.qualified_favorites
-- FROM coupons c JOIN v_coupon_favorite_stats s ON s.coupon_id=c.id;

-- CouponHub v2: many-to-many User <-> Merchant and two Favorite metrics.
-- Compatibility target: MySQL 8+ / MariaDB 10.4+.
-- Integrated migration: includes table/view/procedure updates. Deploy together with
-- Phase5 PHP files; NEVER deploy alongside running old Phase4 code. Back up the DB first.
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

-- Legacy applications that are still awaiting Shop review are transferred into the request queue.
INSERT INTO merchant_requests(user_id,merchant_id,request_type,status,created_at)
SELECT m.owner_user_id,m.id,'LEGACY','PENDING',m.created_at
FROM merchants m
WHERE m.owner_user_id IS NOT NULL AND m.status='PENDING' AND m.deleted_at IS NULL
  AND NOT EXISTS(SELECT 1 FROM merchant_requests r WHERE r.user_id=m.owner_user_id AND r.merchant_id=m.id);

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


-- Historical votes which are from own coupons or current managers must not affect Trust Score.
SET @has_vote_flag=(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='votes' AND COLUMN_NAME='is_eligible_for_trust');
SET @sql_vote=IF(@has_vote_flag=0, 'ALTER TABLE votes ADD COLUMN is_eligible_for_trust TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE stmt_vote FROM @sql_vote; EXECUTE stmt_vote; DEALLOCATE PREPARE stmt_vote;
UPDATE votes v JOIN coupons c ON c.id=v.coupon_id LEFT JOIN merchant_memberships mm ON mm.user_id=v.user_id AND mm.merchant_id=c.merchant_id AND mm.status='ACTIVE'
SET v.is_eligible_for_trust=0 WHERE v.is_eligible_for_trust=1 AND (v.user_id=c.submitted_by OR mm.user_id IS NOT NULL);


-- Recreate status view with explicit unicode_ci for compatibility.
CREATE OR REPLACE SQL SECURITY INVOKER VIEW v_coupon_status AS
SELECT c.*,
 (CASE
   WHEN c.deleted_at IS NOT NULL OR c.publication_status = 'ARCHIVED' THEN 'ARCHIVED'
   WHEN c.publication_status = 'DISABLED' OR m.status = 'LOCKED' OR m.deleted_at IS NOT NULL OR cat.is_active = 0 THEN 'DISABLED'
   WHEN c.moderation_status = 'REJECTED' THEN 'REJECTED'
   WHEN c.moderation_status = 'PENDING' THEN 'PENDING'
   WHEN c.publication_status = 'EXPIRED' OR UTC_TIMESTAMP() >= c.ends_at THEN 'EXPIRED'
   WHEN UTC_TIMESTAMP() < c.starts_at THEN 'SCHEDULED'
   ELSE 'ACTIVE'
 END) COLLATE utf8mb4_unicode_ci AS effective_status,
 CASE WHEN c.source = 'OFFICIAL' AND m.status = 'APPROVED' AND m.deleted_at IS NULL THEN 1 ELSE 0 END AS is_official
FROM coupons c
JOIN merchants m ON m.id = c.merchant_id
JOIN categories cat ON cat.id = c.category_id;

-- Replace derived vote statistics; favorites view was created above.
CREATE OR REPLACE SQL SECURITY INVOKER VIEW v_coupon_vote_stats AS
SELECT c.id AS coupon_id,
 COALESCE(SUM(v.vote_type='WORKING'),0) AS working_count,
 COALESCE(SUM(v.vote_type='NOT_WORKING'),0) AS not_working_count,
 COALESCE(SUM(v.vote_type='CONDITION_CHANGED'),0) AS condition_changed_count,
 COUNT(v.user_id) AS total_votes,
 ROUND(100.0 * SUM(v.vote_type='WORKING') / NULLIF(SUM(v.vote_type IN ('WORKING','NOT_WORKING')),0),2) AS trust_score,
 MAX(v.updated_at) AS last_verification_at
FROM coupons c LEFT JOIN votes v ON v.coupon_id=c.id AND v.is_eligible_for_trust=1 AND v.user_id<>c.submitted_by
 AND NOT EXISTS (SELECT 1 FROM merchant_memberships mm WHERE mm.user_id=v.user_id AND mm.merchant_id=c.merchant_id AND mm.status='ACTIVE')
GROUP BY c.id;

-- Replace only affected procedures. Do NOT re-import the original schema or hotfix afterwards.
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_award_coupon_points$$
DROP PROCEDURE IF EXISTS sp_submit_coupon$$
DROP PROCEDURE IF EXISTS sp_set_vote$$
DROP PROCEDURE IF EXISTS sp_set_favorite$$
DROP PROCEDURE IF EXISTS sp_attach_campaign_coupon$$
DROP PROCEDURE IF EXISTS sp_search_coupons$$
DROP PROCEDURE IF EXISTS sp_merchant_stats$$
CREATE PROCEDURE sp_award_coupon_points(IN p_coupon_id BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
  DECLARE v_author BIGINT UNSIGNED;
  DECLARE v_source VARCHAR(20);
  DECLARE v_moderation VARCHAR(20);
  DECLARE v_working INT DEFAULT 0;
  DECLARE v_favorites INT DEFAULT 0;
  SELECT c.submitted_by,c.source,c.moderation_status
    INTO v_author,v_source,v_moderation
    FROM coupons c WHERE c.id=p_coupon_id;
  IF v_source='COMMUNITY' AND v_moderation='APPROVED' THEN
    SELECT working_count INTO v_working FROM v_coupon_vote_stats WHERE coupon_id=p_coupon_id;
    SELECT qualified_favorites INTO v_favorites FROM v_coupon_favorite_stats WHERE coupon_id=p_coupon_id;
    INSERT INTO point_transactions(user_id,coupon_id,rule_code,points,note)
      SELECT v_author,p_coupon_id,r.code,r.points,r.description FROM point_rules r
      WHERE r.is_active=1 AND (
        r.code='COUPON_APPROVED'
        OR (r.code='WORKING_10' AND v_working>=r.threshold_count)
        OR (r.code='FAVORITES_100' AND v_favorites>=r.threshold_count))
      AND NOT EXISTS (SELECT 1 FROM point_transactions p WHERE p.coupon_id=p_coupon_id AND p.rule_code=r.code);
  END IF;
END$$

CREATE PROCEDURE sp_submit_coupon(
  IN p_actor BIGINT UNSIGNED,IN p_merchant BIGINT UNSIGNED,IN p_category BIGINT UNSIGNED,
  IN p_official TINYINT,IN p_title VARCHAR(200),IN p_code VARCHAR(100),IN p_description TEXT,IN p_terms TEXT,
  IN p_type VARCHAR(30),IN p_value DECIMAL(15,2),IN p_cashback_unit VARCHAR(20),
  IN p_min_order DECIMAL(15,2),IN p_max_discount DECIMAL(15,2),IN p_channel VARCHAR(10),
  IN p_url VARCHAR(2048),IN p_qr_path VARCHAR(500),IN p_image_path VARCHAR(500),
  IN p_start DATETIME,IN p_end DATETIME)
SQL SECURITY INVOKER
BEGIN
  DECLARE v_owner BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_merchant_state VARCHAR(20) DEFAULT NULL;
  DECLARE v_deleted DATETIME DEFAULT NULL;
  DECLARE v_id BIGINT UNSIGNED;
  DECLARE v_duplicate INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required';
  END IF;
  SELECT owner_user_id,status,deleted_at INTO v_owner,v_merchant_state,v_deleted FROM merchants WHERE id=p_merchant FOR UPDATE;
  IF v_merchant_state IS NULL OR v_merchant_state IN ('LOCKED','REJECTED') OR v_deleted IS NOT NULL THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Merchant unavailable';
  END IF;
  IF p_official IS NULL OR p_official NOT IN (0,1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid official flag'; END IF;
  IF p_official=1 AND (v_merchant_state<>'APPROVED' OR
      NOT EXISTS(SELECT 1 FROM merchant_memberships mm WHERE mm.user_id=p_actor AND mm.merchant_id=p_merchant AND mm.status='ACTIVE')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only approved merchant owner can submit official coupon';
  END IF;
  IF NOT EXISTS(SELECT 1 FROM categories WHERE id=p_category AND is_active=1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active category required';
  END IF;
  IF p_title IS NULL OR CHAR_LENGTH(TRIM(p_title))=0 OR p_terms IS NULL OR CHAR_LENGTH(TRIM(p_terms))=0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Title and terms required';
  END IF;
  IF p_start IS NULL OR p_end IS NULL OR p_end<=p_start OR p_end<=UTC_TIMESTAMP() THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid validity period';
  END IF;
  IF NULLIF(TRIM(p_code),'') IS NOT NULL THEN
    SELECT COUNT(*) INTO v_duplicate FROM coupons WHERE merchant_id=p_merchant
      AND code_normalized=UPPER(TRIM(p_code)) AND deleted_at IS NULL
      AND moderation_status IN ('PENDING','APPROVED') AND publication_status='ENABLED'
      AND starts_at<p_end AND ends_at>p_start;
    IF v_duplicate>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate code with overlapping validity'; END IF;
  END IF;
  INSERT INTO coupons(merchant_id,category_id,submitted_by,source,title,code,description,terms,
    discount_type,discount_value,cashback_unit,min_order_amount,max_discount_amount,usage_channel,
    promotion_url,qr_image_path,image_path,starts_at,ends_at)
  VALUES(p_merchant,p_category,p_actor,IF(p_official=1,'OFFICIAL','COMMUNITY'),TRIM(p_title),NULLIF(TRIM(p_code),''),p_description,p_terms,
    p_type,p_value,p_cashback_unit,p_min_order,p_max_discount,p_channel,NULLIF(TRIM(p_url),''),NULLIF(TRIM(p_qr_path),''),p_image_path,p_start,p_end);
  SET v_id=LAST_INSERT_ID();
  INSERT INTO activity_logs(user_id,coupon_id,action) VALUES(p_actor,v_id,'COUPON_SUBMIT');
  COMMIT;
  SELECT v_id AS coupon_id;
END$$

CREATE PROCEDURE sp_set_vote(IN p_actor BIGINT UNSIGNED,IN p_coupon BIGINT UNSIGNED,IN p_vote VARCHAR(30))
SQL SECURITY INVOKER
BEGIN
  DECLARE v_author BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_changed INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  SELECT submitted_by INTO v_author FROM coupons WHERE id=p_coupon FOR UPDATE;
  IF v_author IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon not found'; END IF;
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required';
  END IF;
  IF p_actor=v_author OR EXISTS(SELECT 1 FROM merchant_memberships mm JOIN coupons c ON c.merchant_id=mm.merchant_id WHERE c.id=p_coupon AND mm.user_id=p_actor AND mm.status='ACTIVE') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Author and merchant owner cannot vote on own coupon';
  END IF;
  IF p_vote IS NULL OR p_vote NOT IN ('WORKING','NOT_WORKING','CONDITION_CHANGED') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid vote';
  END IF;
  IF NOT EXISTS(SELECT 1 FROM v_coupon_status WHERE id=p_coupon AND effective_status COLLATE utf8mb4_unicode_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon must be active';
  END IF;
  IF NOT EXISTS(SELECT 1 FROM votes WHERE user_id=p_actor AND coupon_id=p_coupon AND vote_type=p_vote AND is_eligible_for_trust=1) THEN
    INSERT INTO votes(user_id,coupon_id,vote_type,is_eligible_for_trust) VALUES(p_actor,p_coupon,p_vote,1)
      ON DUPLICATE KEY UPDATE vote_type=p_vote,is_eligible_for_trust=1,updated_at=UTC_TIMESTAMP();
    SET v_changed=1;
    INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(p_actor,p_coupon,'VOTE_SET',p_vote);
    INSERT INTO coupon_events(coupon_id,user_id,event_type,request_key) VALUES(p_coupon,p_actor,'VOTE',UUID());
  END IF;
  CALL sp_award_coupon_points(p_coupon);
  COMMIT;
  SELECT v_changed AS changed;
END$$

CREATE PROCEDURE sp_set_favorite(IN p_actor BIGINT UNSIGNED,IN p_coupon BIGINT UNSIGNED,IN p_saved TINYINT)
SQL SECURITY INVOKER
BEGIN
  DECLARE v_lock BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_valid TINYINT DEFAULT 1;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  SELECT id INTO v_lock FROM coupons WHERE id=p_coupon FOR UPDATE;
  IF v_lock IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon not found'; END IF;
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required';
  END IF;
  IF p_saved IS NULL OR p_saved NOT IN (0,1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid saved flag'; END IF;
  IF p_saved=1 THEN
    IF NOT EXISTS(SELECT 1 FROM v_coupon_status WHERE id=p_coupon AND effective_status COLLATE utf8mb4_unicode_ci IN (_utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci,_utf8mb4'SCHEDULED' COLLATE utf8mb4_unicode_ci)) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon cannot be saved';
    END IF;
    IF NOT EXISTS(SELECT 1 FROM favorites WHERE user_id=p_actor AND coupon_id=p_coupon) THEN
      IF EXISTS(SELECT 1 FROM coupons c WHERE c.id=p_coupon AND (c.submitted_by=p_actor OR EXISTS(SELECT 1 FROM merchant_memberships mm WHERE mm.user_id=p_actor AND mm.merchant_id=c.merchant_id AND mm.status='ACTIVE'))) THEN
        SET v_valid=0;
      END IF;
      INSERT INTO favorites(user_id,coupon_id,is_eligible_for_rewards) VALUES(p_actor,p_coupon,v_valid);
      INSERT INTO activity_logs(user_id,coupon_id,action) VALUES(p_actor,p_coupon,'FAVORITE_ADD');
      INSERT INTO coupon_events(coupon_id,user_id,event_type,request_key) VALUES(p_coupon,p_actor,'FAVORITE_ADD',UUID());
    END IF;
    CALL sp_award_coupon_points(p_coupon);
  ELSE
    DELETE FROM favorites WHERE user_id=p_actor AND coupon_id=p_coupon;
    IF ROW_COUNT()>0 THEN INSERT INTO activity_logs(user_id,coupon_id,action) VALUES(p_actor,p_coupon,'FAVORITE_REMOVE'); END IF;
  END IF;
  COMMIT;
END$$

CREATE PROCEDURE sp_attach_campaign_coupon(IN p_actor BIGINT UNSIGNED,IN p_campaign BIGINT UNSIGNED,IN p_coupon BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
  DECLARE v_campaign BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_owner_merchant BIGINT UNSIGNED;
  DECLARE v_merchant BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_role VARCHAR(20) DEFAULT NULL;
  DECLARE v_cs DATETIME; DECLARE v_ce DATETIME; DECLARE v_ps DATETIME; DECLARE v_pe DATETIME;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  SELECT role INTO v_role FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL;
  IF v_role IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required'; END IF;
  SELECT id,owner_merchant_id,starts_at,ends_at INTO v_campaign,v_owner_merchant,v_cs,v_ce FROM campaigns WHERE id=p_campaign FOR UPDATE;
  SELECT merchant_id,starts_at,ends_at INTO v_merchant,v_ps,v_pe FROM coupons WHERE id=p_coupon AND deleted_at IS NULL FOR UPDATE;
  IF v_campaign IS NULL OR v_merchant IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign or coupon not found'; END IF;
  IF v_owner_merchant IS NOT NULL AND v_owner_merchant<>v_merchant THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon does not belong to campaign merchant';
  END IF;
  IF v_role<>'ADMIN' AND (v_role<>'MERCHANT' OR v_owner_merchant IS NULL OR NOT EXISTS(
    SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id WHERE mm.merchant_id=v_owner_merchant AND mm.user_id=p_actor AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign owner or admin required';
  END IF;
  IF v_cs>=v_pe OR v_ps>=v_ce THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign and coupon periods must overlap'; END IF;
  IF NOT EXISTS(SELECT 1 FROM campaign_coupons WHERE campaign_id=p_campaign AND coupon_id=p_coupon) THEN
    INSERT INTO campaign_coupons(campaign_id,coupon_id,added_by) VALUES(p_campaign,p_coupon,p_actor);
  END IF;
  COMMIT;
END$$

CREATE PROCEDURE sp_search_coupons(IN p_keyword VARCHAR(200),IN p_category BIGINT UNSIGNED,IN p_merchant BIGINT UNSIGNED,
  IN p_type VARCHAR(30),IN p_channel VARCHAR(10),IN p_ending_soon TINYINT,IN p_sort VARCHAR(20),IN p_page INT,IN p_page_size INT)
SQL SECURITY INVOKER
BEGIN
  DECLARE v_offset INT DEFAULT 0;
  DECLARE v_size INT DEFAULT 20;
  SET v_size=LEAST(100,GREATEST(1,COALESCE(p_page_size,20)));
  SET v_offset=(LEAST(100000,GREATEST(1,COALESCE(p_page,1)))-1)*v_size;
  SELECT c.id,c.title,c.code,c.merchant_id,m.name AS merchant_name,c.category_id,c.discount_type,c.discount_value,
    c.min_order_amount,c.max_discount_amount,c.usage_channel,c.starts_at,c.ends_at,c.is_official,
    s.trust_score,s.total_votes,fs.total_favorites AS favorite_count,fs.qualified_favorites
  FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id JOIN v_coupon_vote_stats s ON s.coupon_id=c.id JOIN v_coupon_favorite_stats fs ON fs.coupon_id=c.id
  WHERE c.effective_status COLLATE utf8mb4_unicode_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci
    AND (p_keyword IS NULL OR p_keyword='' OR c.title LIKE CONCAT('%',p_keyword,'%') OR c.code LIKE CONCAT('%',p_keyword,'%')
      OR c.description LIKE CONCAT('%',p_keyword,'%') OR m.name LIKE CONCAT('%',p_keyword,'%'))
    AND (p_category IS NULL OR c.category_id=p_category) AND (p_merchant IS NULL OR c.merchant_id=p_merchant)
    AND (p_type IS NULL OR c.discount_type=p_type) AND (p_channel IS NULL OR c.usage_channel=p_channel OR c.usage_channel='BOTH')
    AND (COALESCE(p_ending_soon,0)=0 OR c.ends_at<=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 3 DAY))
  ORDER BY CASE WHEN p_sort='POPULAR' THEN fs.qualified_favorites END DESC,
    CASE WHEN p_sort='ENDING_SOON' THEN c.ends_at END ASC,c.created_at DESC,c.id DESC
  LIMIT v_size OFFSET v_offset;
END$$

CREATE PROCEDURE sp_merchant_stats(IN p_actor BIGINT UNSIGNED,IN p_merchant BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
  IF NOT EXISTS(SELECT 1 FROM users u WHERE u.id=p_actor AND u.status='ACTIVE' AND u.deleted_at IS NULL AND
     (u.role='ADMIN' OR (u.role='MERCHANT' AND EXISTS(SELECT 1 FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id WHERE m.id=p_merchant AND mm.user_id=u.id AND mm.status='ACTIVE' AND m.status='APPROVED' AND m.deleted_at IS NULL)))) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Merchant owner or admin required';
  END IF;
  SELECT COUNT(*) AS total_coupons,COALESCE(SUM(effective_status COLLATE utf8mb4_unicode_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci),0) AS active_coupons,
    COALESCE(SUM(effective_status COLLATE utf8mb4_unicode_ci=_utf8mb4'ACTIVE' COLLATE utf8mb4_unicode_ci AND ends_at<=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 3 DAY)),0) AS expiring_soon
    FROM v_coupon_status WHERE merchant_id=p_merchant AND deleted_at IS NULL;
  SELECT COALESCE(SUM(e.event_type='VIEW'),0) AS total_views,COALESCE(SUM(e.event_type='COPY'),0) AS total_copies,
    COALESCE(SUM(e.event_type='SHARE'),0) AS total_shares
    FROM coupon_events e JOIN coupons c ON c.id=e.coupon_id WHERE c.merchant_id=p_merchant;
  SELECT COALESCE(SUM(s.total_favorites),0) AS current_favorites,COALESCE(SUM(s.qualified_favorites),0) AS qualified_favorites FROM v_coupon_favorite_stats s JOIN coupons c ON c.id=s.coupon_id WHERE c.merchant_id=p_merchant;
END$$

DELIMITER ;

SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE() ORDER BY ROUTINE_NAME;

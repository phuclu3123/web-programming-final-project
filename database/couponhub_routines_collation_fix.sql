-- CouponHub routines collation fix
-- Purpose: restore the stored procedures required by the PHP repositories.
-- Safe for existing development data: this script does NOT drop/recreate tables and does NOT delete rows.
-- Run while the `couponhub` database is selected in phpMyAdmin.

-- IMPORTANT: keep database/routine string collations aligned with the tables.
-- Existing CouponHub tables use utf8mb4_unicode_ci.
ALTER DATABASE `couponhub`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET collation_connection = 'utf8mb4_unicode_ci';
SET time_zone = '+00:00';

DELIMITER $$
DROP PROCEDURE IF EXISTS sp_award_coupon_points$$
DROP PROCEDURE IF EXISTS sp_add_category$$
DROP PROCEDURE IF EXISTS sp_submit_coupon$$
DROP PROCEDURE IF EXISTS sp_moderate_coupon$$
DROP PROCEDURE IF EXISTS sp_set_vote$$
DROP PROCEDURE IF EXISTS sp_set_favorite$$
DROP PROCEDURE IF EXISTS sp_attach_campaign_coupon$$
DROP PROCEDURE IF EXISTS sp_resolve_report$$
DROP PROCEDURE IF EXISTS sp_record_coupon_event$$
DROP PROCEDURE IF EXISTS sp_search_coupons$$
DROP PROCEDURE IF EXISTS sp_merchant_stats$$

-- Internal helper: caller MUST hold coupons row lock in its transaction.
-- Do not expose an HTTP route for this procedure; points are server calculated.
CREATE PROCEDURE sp_award_coupon_points(IN p_coupon_id BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
  DECLARE v_author BIGINT UNSIGNED;
  DECLARE v_owner BIGINT UNSIGNED;
  DECLARE v_source VARCHAR(20);
  DECLARE v_moderation VARCHAR(20);
  DECLARE v_working INT DEFAULT 0;
  DECLARE v_favorites INT DEFAULT 0;
  SELECT c.submitted_by,m.owner_user_id,c.source,c.moderation_status
    INTO v_author,v_owner,v_source,v_moderation
    FROM coupons c JOIN merchants m ON m.id=c.merchant_id WHERE c.id=p_coupon_id;
  IF v_source='COMMUNITY' AND v_moderation='APPROVED' THEN
    SELECT COUNT(*) INTO v_working FROM votes
      WHERE coupon_id=p_coupon_id AND vote_type='WORKING'
        AND user_id<>v_author AND (v_owner IS NULL OR user_id<>v_owner);
    SELECT COUNT(*) INTO v_favorites FROM favorites
      WHERE coupon_id=p_coupon_id AND user_id<>v_author AND (v_owner IS NULL OR user_id<>v_owner);
    INSERT INTO point_transactions(user_id,coupon_id,rule_code,points,note)
      SELECT v_author,p_coupon_id,r.code,r.points,r.description FROM point_rules r
      WHERE r.is_active=1 AND (
        r.code='COUPON_APPROVED'
        OR (r.code='WORKING_10' AND v_working>=r.threshold_count)
        OR (r.code='FAVORITES_100' AND v_favorites>=r.threshold_count))
      AND NOT EXISTS (SELECT 1 FROM point_transactions p WHERE p.coupon_id=p_coupon_id AND p.rule_code=r.code);
  END IF;
END$$

CREATE PROCEDURE sp_add_category(IN p_actor BIGINT UNSIGNED,IN p_name VARCHAR(100),IN p_slug VARCHAR(191),IN p_description TEXT)
SQL SECURITY INVOKER
BEGIN
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Admin required';
  END IF;
  IF p_name IS NULL OR CHAR_LENGTH(TRIM(p_name))=0 OR p_slug IS NULL OR CHAR_LENGTH(TRIM(p_slug))=0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Name and slug required';
  END IF;
  INSERT INTO categories(name,slug,description) VALUES(TRIM(p_name),TRIM(p_slug),p_description);
  SELECT LAST_INSERT_ID() AS category_id;
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
  IF p_official=1 AND (v_owner IS NULL OR v_owner<>p_actor OR v_merchant_state<>'APPROVED' OR
      NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND role='MERCHANT')) THEN
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

CREATE PROCEDURE sp_moderate_coupon(IN p_actor BIGINT UNSIGNED,IN p_coupon BIGINT UNSIGNED,
  IN p_moderation VARCHAR(20),IN p_publication VARCHAR(20),IN p_note TEXT)
SQL SECURITY INVOKER
BEGIN
  DECLARE v_old_m VARCHAR(20) DEFAULT NULL;
  DECLARE v_old_p VARCHAR(20);
  DECLARE v_merchant BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_lock BIGINT UNSIGNED;
  DECLARE v_code VARCHAR(100);
  DECLARE v_start DATETIME;
  DECLARE v_end DATETIME;
  DECLARE v_deleted DATETIME;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Admin required';
  END IF;
  IF p_moderation IS NULL OR p_moderation NOT IN ('PENDING','APPROVED','REJECTED') OR
     p_publication IS NULL OR p_publication NOT IN ('ENABLED','DISABLED','EXPIRED','ARCHIVED') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid state';
  END IF;
  SELECT merchant_id INTO v_merchant FROM coupons WHERE id=p_coupon;
  IF v_merchant IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon not found'; END IF;
  -- Same merchant -> coupon lock order as submission/edit to prevent duplicate reactivation.
  SELECT id INTO v_lock FROM merchants WHERE id=v_merchant FOR UPDATE;
  SELECT moderation_status,publication_status,code_normalized,starts_at,ends_at,deleted_at
    INTO v_old_m,v_old_p,v_code,v_start,v_end,v_deleted FROM coupons WHERE id=p_coupon FOR UPDATE;
  IF v_deleted IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Deleted coupon cannot be moderated'; END IF;
  IF p_moderation IN ('PENDING','APPROVED') AND p_publication='ENABLED' AND v_code IS NOT NULL AND EXISTS(
    SELECT 1 FROM coupons WHERE id<>p_coupon AND merchant_id=v_merchant AND code_normalized=v_code
      AND deleted_at IS NULL AND moderation_status IN ('PENDING','APPROVED') AND publication_status='ENABLED'
      AND starts_at<v_end AND ends_at>v_start) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Duplicate code with overlapping validity';
  END IF;
  UPDATE coupons SET moderation_status=p_moderation,publication_status=p_publication,
    reviewed_by=p_actor,reviewed_at=UTC_TIMESTAMP(),review_note=p_note WHERE id=p_coupon;
  IF v_old_m<>p_moderation OR v_old_p<>p_publication THEN
    INSERT INTO coupon_status_history(coupon_id,actor_id,old_moderation,new_moderation,old_publication,new_publication,note)
    VALUES(p_coupon,p_actor,v_old_m,p_moderation,v_old_p,p_publication,p_note);
    INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(p_actor,p_coupon,'COUPON_MODERATE',CONCAT(p_moderation,'/',p_publication));
  END IF;
  IF p_moderation='APPROVED' AND p_publication='ENABLED' THEN CALL sp_award_coupon_points(p_coupon); END IF;
  COMMIT;
END$$

CREATE PROCEDURE sp_set_vote(IN p_actor BIGINT UNSIGNED,IN p_coupon BIGINT UNSIGNED,IN p_vote VARCHAR(30))
SQL SECURITY INVOKER
BEGIN
  DECLARE v_author BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_owner BIGINT UNSIGNED;
  DECLARE v_changed INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  SELECT submitted_by INTO v_author FROM coupons WHERE id=p_coupon FOR UPDATE;
  IF v_author IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon not found'; END IF;
  SELECT m.owner_user_id INTO v_owner FROM merchants m JOIN coupons c ON c.merchant_id=m.id WHERE c.id=p_coupon;
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Active account required';
  END IF;
  IF p_actor=v_author OR (v_owner IS NOT NULL AND p_actor=v_owner) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Author and merchant owner cannot vote on own coupon';
  END IF;
  IF p_vote IS NULL OR p_vote NOT IN ('WORKING','NOT_WORKING','CONDITION_CHANGED') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid vote';
  END IF;
  IF NOT EXISTS(SELECT 1 FROM v_coupon_status WHERE id=p_coupon AND effective_status='ACTIVE') THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon must be active';
  END IF;
  IF NOT EXISTS(SELECT 1 FROM votes WHERE user_id=p_actor AND coupon_id=p_coupon AND vote_type=p_vote) THEN
    INSERT INTO votes(user_id,coupon_id,vote_type) VALUES(p_actor,p_coupon,p_vote)
      ON DUPLICATE KEY UPDATE vote_type=p_vote,updated_at=UTC_TIMESTAMP();
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
    IF NOT EXISTS(SELECT 1 FROM v_coupon_status WHERE id=p_coupon AND effective_status IN ('ACTIVE','SCHEDULED')) THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon cannot be saved';
    END IF;
    IF NOT EXISTS(SELECT 1 FROM favorites WHERE user_id=p_actor AND coupon_id=p_coupon) THEN
      INSERT INTO favorites(user_id,coupon_id) VALUES(p_actor,p_coupon);
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
    SELECT 1 FROM merchants WHERE id=v_owner_merchant AND owner_user_id=p_actor AND status='APPROVED' AND deleted_at IS NULL)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign owner or admin required';
  END IF;
  IF v_cs>=v_pe OR v_ps>=v_ce THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Campaign and coupon periods must overlap'; END IF;
  IF NOT EXISTS(SELECT 1 FROM campaign_coupons WHERE campaign_id=p_campaign AND coupon_id=p_coupon) THEN
    INSERT INTO campaign_coupons(campaign_id,coupon_id,added_by) VALUES(p_campaign,p_coupon,p_actor);
  END IF;
  COMMIT;
END$$

CREATE PROCEDURE sp_resolve_report(IN p_actor BIGINT UNSIGNED,IN p_report BIGINT UNSIGNED,IN p_status VARCHAR(20),IN p_note TEXT)
SQL SECURITY INVOKER
BEGIN
  DECLARE v_coupon BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_status VARCHAR(20);
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  IF NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Admin required';
  END IF;
  IF p_status IS NULL OR p_status NOT IN ('RESOLVED','DISMISSED') OR p_note IS NULL OR CHAR_LENGTH(TRIM(p_note))=0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Resolution status and note required';
  END IF;
  SELECT coupon_id,status INTO v_coupon,v_status FROM reports WHERE id=p_report FOR UPDATE;
  IF v_coupon IS NULL OR v_status<>'OPEN' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Open report required'; END IF;
  UPDATE reports SET status=p_status,resolved_by=p_actor,resolved_at=UTC_TIMESTAMP(),resolution_note=p_note WHERE id=p_report;
  INSERT INTO activity_logs(user_id,coupon_id,action,description) VALUES(p_actor,v_coupon,'REPORT_RESOLVE',CONCAT('Report #',p_report,': ',p_status));
  COMMIT;
END$$

CREATE PROCEDURE sp_record_coupon_event(IN p_actor BIGINT UNSIGNED,IN p_coupon BIGINT UNSIGNED,IN p_event VARCHAR(20),IN p_key VARCHAR(64))
SQL SECURITY INVOKER
BEGIN
  IF p_event IS NULL OR p_event NOT IN ('VIEW','COPY','SHARE') OR p_key IS NULL OR CHAR_LENGTH(TRIM(p_key))=0 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid event or request key';
  END IF;
  IF p_actor IS NOT NULL AND NOT EXISTS(SELECT 1 FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid account';
  END IF;
  IF NOT EXISTS(SELECT 1 FROM v_coupon_status WHERE id=p_coupon AND effective_status IN ('ACTIVE','SCHEDULED')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon unavailable';
  END IF;
  IF p_event='COPY' AND NOT EXISTS(SELECT 1 FROM coupons WHERE id=p_coupon AND NULLIF(TRIM(code),'') IS NOT NULL) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Coupon has no copyable code';
  END IF;
  -- Duplicate key returns an error: service may treat only an exact matching replay as success.
  INSERT INTO coupon_events(coupon_id,user_id,event_type,request_key) VALUES(p_coupon,p_actor,p_event,p_key);
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
    s.trust_score,s.total_votes,(SELECT COUNT(*) FROM favorites f WHERE f.coupon_id=c.id) AS favorite_count
  FROM v_coupon_status c JOIN merchants m ON m.id=c.merchant_id JOIN v_coupon_vote_stats s ON s.coupon_id=c.id
  WHERE c.effective_status='ACTIVE'
    AND (p_keyword IS NULL OR p_keyword='' OR c.title LIKE CONCAT('%',p_keyword,'%') OR c.code LIKE CONCAT('%',p_keyword,'%')
      OR c.description LIKE CONCAT('%',p_keyword,'%') OR m.name LIKE CONCAT('%',p_keyword,'%'))
    AND (p_category IS NULL OR c.category_id=p_category) AND (p_merchant IS NULL OR c.merchant_id=p_merchant)
    AND (p_type IS NULL OR c.discount_type=p_type) AND (p_channel IS NULL OR c.usage_channel=p_channel OR c.usage_channel='BOTH')
    AND (COALESCE(p_ending_soon,0)=0 OR c.ends_at<=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 3 DAY))
  ORDER BY CASE WHEN p_sort='POPULAR' THEN favorite_count END DESC,
    CASE WHEN p_sort='ENDING_SOON' THEN c.ends_at END ASC,c.created_at DESC,c.id DESC
  LIMIT v_size OFFSET v_offset;
END$$

CREATE PROCEDURE sp_merchant_stats(IN p_actor BIGINT UNSIGNED,IN p_merchant BIGINT UNSIGNED)
SQL SECURITY INVOKER
BEGIN
  IF NOT EXISTS(SELECT 1 FROM users u WHERE u.id=p_actor AND u.status='ACTIVE' AND u.deleted_at IS NULL AND
     (u.role='ADMIN' OR (u.role='MERCHANT' AND EXISTS(SELECT 1 FROM merchants m WHERE m.id=p_merchant AND m.owner_user_id=u.id AND m.status='APPROVED' AND m.deleted_at IS NULL)))) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Merchant owner or admin required';
  END IF;
  SELECT COUNT(*) AS total_coupons,COALESCE(SUM(effective_status='ACTIVE'),0) AS active_coupons,
    COALESCE(SUM(effective_status='ACTIVE' AND ends_at<=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 3 DAY)),0) AS expiring_soon
    FROM v_coupon_status WHERE merchant_id=p_merchant AND deleted_at IS NULL;
  SELECT COALESCE(SUM(e.event_type='VIEW'),0) AS total_views,COALESCE(SUM(e.event_type='COPY'),0) AS total_copies,
    COALESCE(SUM(e.event_type='SHARE'),0) AS total_shares
    FROM coupon_events e JOIN coupons c ON c.id=e.coupon_id WHERE c.merchant_id=p_merchant;
  SELECT COUNT(*) AS current_favorites FROM favorites f JOIN coupons c ON c.id=f.coupon_id WHERE c.merchant_id=p_merchant;
END$$

DELIMITER ;

-- Verification
SHOW PROCEDURE STATUS WHERE Db = DATABASE();

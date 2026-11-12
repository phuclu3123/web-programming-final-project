-- Existing installations: backup first, select the intended database, then run.
-- Additive/repeatable; does not reseed or replace user/coupon/identity data.
-- Apply the phase-3 migration first when upgrading an older installation.
SET NAMES utf8mb4;
ALTER TABLE oauth_accounts MODIFY provider ENUM('github', 'google', 'facebook') NOT NULL;
DELIMITER $$

DROP PROCEDURE IF EXISTS `sp_attach_campaign_coupon`$$
CREATE PROCEDURE `sp_attach_campaign_coupon` (IN `p_actor` BIGINT UNSIGNED, IN `p_campaign` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED)  SQL SECURITY INVOKER BEGIN
  DECLARE v_campaign BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_owner_merchant BIGINT UNSIGNED;
  DECLARE v_merchant BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_role VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci DEFAULT NULL;
  DECLARE v_cs DATETIME; DECLARE v_ce DATETIME; DECLARE v_ps DATETIME; DECLARE v_pe DATETIME;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  SELECT role INTO v_role FROM users WHERE id=p_actor AND status='ACTIVE' AND deleted_at IS NULL LOCK IN SHARE MODE;
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

DROP PROCEDURE IF EXISTS `sp_moderate_coupon`$$
CREATE PROCEDURE `sp_moderate_coupon` (IN `p_actor` BIGINT UNSIGNED, IN `p_coupon` BIGINT UNSIGNED, IN `p_moderation` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_publication` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_note` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)  SQL SECURITY INVOKER BEGIN
  DECLARE v_old_m VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci DEFAULT NULL;
  DECLARE v_old_p VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;
  DECLARE v_merchant BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_lock BIGINT UNSIGNED;
  DECLARE v_code VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;
  DECLARE v_start DATETIME;
  DECLARE v_end DATETIME;
  DECLARE v_deleted DATETIME;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  SELECT id INTO v_lock FROM users WHERE id=p_actor AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL LOCK IN SHARE MODE;
  IF v_lock IS NULL THEN
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
  IF v_old_p='ARCHIVED' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Archived or revoked coupon cannot be moderated'; END IF;
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

DROP PROCEDURE IF EXISTS `sp_resolve_report`$$
CREATE PROCEDURE `sp_resolve_report` (IN `p_actor` BIGINT UNSIGNED, IN `p_report` BIGINT UNSIGNED, IN `p_status` VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci, IN `p_note` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci)  SQL SECURITY INVOKER BEGIN
  DECLARE v_coupon BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_actor BIGINT UNSIGNED DEFAULT NULL;
  DECLARE v_status VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
  SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
  START TRANSACTION;
  SELECT id INTO v_actor FROM users WHERE id=p_actor AND role='ADMIN' AND status='ACTIVE' AND deleted_at IS NULL LOCK IN SHARE MODE;
  IF v_actor IS NULL THEN
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

DELIMITER ;

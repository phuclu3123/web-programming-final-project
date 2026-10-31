-- Upgrade existing CouponHub installations. Back up first. No seed or table/data changes.
-- Prevent re-approval of terminal ARCHIVED/revoked coupons.
SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci;
CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `v_coupon_status`  AS SELECT (`c`.`source` = 'OFFICIAL') AS `is_official`, `c`.`id` AS `id`, `c`.`merchant_id` AS `merchant_id`, `c`.`category_id` AS `category_id`, `c`.`submitted_by` AS `submitted_by`, `c`.`source` AS `source`, `c`.`title` AS `title`, `c`.`code` AS `code`, `c`.`code_normalized` AS `code_normalized`, `c`.`description` AS `description`, `c`.`terms` AS `terms`, `c`.`discount_type` AS `discount_type`, `c`.`discount_value` AS `discount_value`, `c`.`cashback_unit` AS `cashback_unit`, `c`.`min_order_amount` AS `min_order_amount`, `c`.`max_discount_amount` AS `max_discount_amount`, `c`.`currency` AS `currency`, `c`.`usage_channel` AS `usage_channel`, `c`.`promotion_url` AS `promotion_url`, `c`.`qr_image_path` AS `qr_image_path`, `c`.`image_path` AS `image_path`, `c`.`starts_at` AS `starts_at`, `c`.`ends_at` AS `ends_at`, `c`.`moderation_status` AS `moderation_status`, `c`.`publication_status` AS `publication_status`, `c`.`reviewed_by` AS `reviewed_by`, `c`.`reviewed_at` AS `reviewed_at`, `c`.`review_note` AS `review_note`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`deleted_at` AS `deleted_at`, CASE WHEN `c`.`deleted_at` is not null OR `c`.`publication_status` = 'ARCHIVED' THEN 'ARCHIVED' WHEN `c`.`publication_status` = 'DISABLED' OR (`c`.`source` = 'OFFICIAL' AND `m`.`status` <> 'APPROVED') OR `m`.`status` = 'LOCKED' OR `m`.`deleted_at` is not null OR `cat`.`is_active` = 0 THEN 'DISABLED' WHEN `c`.`moderation_status` = 'REJECTED' THEN 'REJECTED' WHEN `c`.`moderation_status` = 'PENDING' THEN 'PENDING' WHEN `c`.`publication_status` = 'EXPIRED' OR utc_timestamp() >= `c`.`ends_at` THEN 'EXPIRED' WHEN utc_timestamp() < `c`.`starts_at` THEN 'SCHEDULED' ELSE 'ACTIVE' END AS effective_status FROM ((`coupons` `c` join `merchants` `m` on(`m`.`id` = `c`.`merchant_id`)) join `categories` `cat` on(`cat`.`id` = `c`.`category_id`)) ;
DELIMITER $$
DROP PROCEDURE IF EXISTS sp_moderate_coupon$$
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
DELIMITER ;

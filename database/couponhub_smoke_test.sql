-- ONLY run after schema import into a NEW disposable database named *_test.
-- This script leaves fake fixtures behind. Do not run on the application DB.
-- Placeholder password hashes below are intentionally NOT valid login hashes.
-- Script has not been executed by its author: see design document.
SET NAMES utf8mb4;
SET time_zone = '+00:00';
DELIMITER $$

CREATE PROCEDURE test_assert(IN p_ok BOOLEAN,IN p_message VARCHAR(128))
BEGIN
  IF p_ok IS NULL OR p_ok=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT=p_message; END IF;
END$$

CREATE PROCEDURE test_couponhub()
BEGIN
  DECLARE v_admin BIGINT UNSIGNED;
  DECLARE v_author BIGINT UNSIGNED;
  DECLARE v_owner BIGINT UNSIGNED;
  DECLARE v_reader BIGINT UNSIGNED;
  DECLARE v_first_reader BIGINT UNSIGNED;
  DECLARE v_merchant BIGINT UNSIGNED;
  DECLARE v_other_merchant BIGINT UNSIGNED;
  DECLARE v_category BIGINT UNSIGNED;
  DECLARE v_coupon BIGINT UNSIGNED;
  DECLARE v_campaign BIGINT UNSIGNED;
  DECLARE v_report BIGINT UNSIGNED;
  DECLARE v_n INT;
  DECLARE v_points INT;
  DECLARE v_rejected BOOLEAN DEFAULT FALSE;
  DECLARE v_i INT DEFAULT 1;
  DECLARE v_start DATETIME;
  DECLARE v_end DATETIME;
  DECLARE v_score DECIMAL(5,2);
  DECLARE v_state VARCHAR(20);

  CALL test_assert(RIGHT(DATABASE(),5)='_test','Safety gate: selected database name must end with _test');
  SELECT COUNT(*) INTO v_n FROM users;
  CALL test_assert(v_n=0,'Safety gate: use a fresh schema with zero users');

  INSERT INTO users(email,password_hash,full_name,role) VALUES('admin@example.invalid','!NO_LOGIN','Test Admin','ADMIN');
  SET v_admin=LAST_INSERT_ID();
  INSERT INTO users(email,password_hash,full_name) VALUES('author@example.invalid','!NO_LOGIN','Test Author');
  SET v_author=LAST_INSERT_ID();
  INSERT INTO users(email,password_hash,full_name,role) VALUES('owner@example.invalid','!NO_LOGIN','Test Merchant','MERCHANT');
  SET v_owner=LAST_INSERT_ID();
  INSERT INTO merchants(owner_user_id,name,slug,status) VALUES(v_owner,'Test Shop','test-shop','APPROVED');
  SET v_merchant=LAST_INSERT_ID();
  INSERT INTO merchants(name,slug) VALUES('Other Shop','other-shop');
  SET v_other_merchant=LAST_INSERT_ID();
  SELECT MIN(id) INTO v_category FROM categories;
  SET v_start=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY);
  SET v_end=DATE_ADD(UTC_TIMESTAMP(),INTERVAL 7 DAY);

  CALL sp_submit_coupon(v_author,v_merchant,v_category,0,'Test 15 percent',' SMOKE15 ',NULL,'Test only',
    'PERCENTAGE',15,NULL,100000,50000,'ONLINE','https://example.invalid/deal',NULL,NULL,v_start,v_end);
  SELECT id INTO v_coupon FROM coupons WHERE merchant_id=v_merchant AND code_normalized='SMOKE15';
  SELECT effective_status INTO v_state FROM v_coupon_status WHERE id=v_coupon;
  CALL test_assert(v_state='PENDING','New coupon must be pending');
  SELECT trust_score INTO v_score FROM v_coupon_vote_stats WHERE coupon_id=v_coupon;
  CALL test_assert(v_score IS NULL,'Zero votes must produce NULL trust score');

  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLSTATE '45000' SET v_rejected=TRUE;
    CALL sp_submit_coupon(v_author,v_merchant,v_category,0,'Duplicate','smoke15',NULL,'Test',
      'PERCENTAGE',15,NULL,0,NULL,'ONLINE',NULL,NULL,NULL,v_start,v_end);
  END;
  CALL test_assert(v_rejected,'Overlapping normalized duplicate must be rejected');

  SET v_rejected=FALSE;
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLSTATE '45000' SET v_rejected=TRUE;
    CALL sp_moderate_coupon(v_author,v_coupon,'APPROVED','ENABLED','Unauthorized');
  END;
  CALL test_assert(v_rejected,'Regular user must not approve coupon');

  CALL sp_moderate_coupon(v_admin,v_coupon,'APPROVED','ENABLED','Test approval');
  CALL sp_moderate_coupon(v_admin,v_coupon,'APPROVED','ENABLED','Replay approval');
  SELECT earned_points INTO v_points FROM v_user_points WHERE user_id=v_author;
  CALL test_assert(v_points=5,'Repeated approval must award only five points');

  SET v_rejected=FALSE;
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLSTATE '45000' SET v_rejected=TRUE;
    CALL sp_set_vote(v_author,v_coupon,'WORKING');
  END;
  CALL test_assert(v_rejected,'Self vote must be rejected');

  WHILE v_i<=100 DO
    INSERT INTO users(email,password_hash,full_name)
      VALUES(CONCAT('reader',v_i,'@example.invalid'),'!NO_LOGIN',CONCAT('Test Reader ',v_i));
    SET v_reader=LAST_INSERT_ID();
    IF v_i=1 THEN SET v_first_reader=v_reader; END IF;
    CALL sp_set_favorite(v_reader,v_coupon,1);
    IF v_i<=10 THEN CALL sp_set_vote(v_reader,v_coupon,'WORKING'); END IF;
    SET v_i=v_i+1;
  END WHILE;
  SELECT earned_points INTO v_points FROM v_user_points WHERE user_id=v_author;
  CALL test_assert(v_points=35,'Approval + ten votes + hundred saves must award 35');
  SELECT COUNT(*) INTO v_n FROM point_transactions WHERE coupon_id=v_coupon;
  CALL test_assert(v_n=3,'Exactly three award ledger entries expected');
  SELECT trust_score INTO v_score FROM v_coupon_vote_stats WHERE coupon_id=v_coupon;
  CALL test_assert(v_score=100,'Ten working votes must produce 100 score');

  CALL sp_set_favorite(v_first_reader,v_coupon,0);
  CALL sp_set_favorite(v_first_reader,v_coupon,1);
  CALL sp_set_favorite(v_first_reader,v_coupon,1);
  CALL sp_set_vote(v_first_reader,v_coupon,'NOT_WORKING');
  SELECT trust_score INTO v_score FROM v_coupon_vote_stats WHERE coupon_id=v_coupon;
  CALL test_assert(v_score=90,'Changing vote must replace the current vote');
  CALL sp_set_vote(v_first_reader,v_coupon,'WORKING');
  CALL sp_set_vote(v_first_reader,v_coupon,'WORKING');
  SELECT COUNT(*) INTO v_n FROM votes WHERE coupon_id=v_coupon;
  CALL test_assert(v_n=10,'Repeated vote must not add duplicate row');
  SELECT COUNT(*) INTO v_n FROM favorites WHERE coupon_id=v_coupon;
  CALL test_assert(v_n=100,'Repeated save must not add duplicate row');
  SELECT earned_points INTO v_points FROM v_user_points WHERE user_id=v_author;
  CALL test_assert(v_points=35,'Threshold recrossing must not award points again');

  SET v_rejected=FALSE;
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_rejected=TRUE;
    UPDATE coupons SET discount_value=101 WHERE id=v_coupon;
  END;
  CALL test_assert(v_rejected,'CHECK must reject percentage above 100');
  SET v_rejected=FALSE;
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET v_rejected=TRUE;
    UPDATE coupons SET ends_at=starts_at WHERE id=v_coupon;
  END;
  CALL test_assert(v_rejected,'CHECK must reject empty validity period');
  SET v_rejected=FALSE;
  BEGIN
    DECLARE CONTINUE HANDLER FOR 1452 SET v_rejected=TRUE;
    INSERT INTO favorites(user_id,coupon_id) VALUES(v_first_reader,18446744073709551600);
  END;
  CALL test_assert(v_rejected,'FK must reject unknown coupon');

  CALL sp_record_coupon_event(NULL,v_coupon,'VIEW','smoke-request-1');
  SET v_rejected=FALSE;
  BEGIN
    DECLARE CONTINUE HANDLER FOR 1062 SET v_rejected=TRUE;
    CALL sp_record_coupon_event(NULL,v_coupon,'VIEW','smoke-request-1');
  END;
  CALL test_assert(v_rejected,'Duplicate event key must be rejected');

  INSERT INTO campaigns(created_by,name,slug,starts_at,ends_at,status)
    VALUES(v_admin,'Platform Test','platform-test',v_start,v_end,'PUBLISHED');
  SET v_campaign=LAST_INSERT_ID();
  CALL sp_attach_campaign_coupon(v_admin,v_campaign,v_coupon);
  CALL sp_attach_campaign_coupon(v_admin,v_campaign,v_coupon);
  SELECT COUNT(*) INTO v_n FROM campaign_coupons WHERE campaign_id=v_campaign;
  CALL test_assert(v_n=1,'Campaign association must be unique');

  INSERT INTO campaigns(owner_merchant_id,created_by,name,slug,starts_at,ends_at)
    VALUES(v_other_merchant,v_admin,'Other Shop Campaign','other-campaign',v_start,v_end);
  SET v_campaign=LAST_INSERT_ID();
  SET v_rejected=FALSE;
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLSTATE '45000' SET v_rejected=TRUE;
    CALL sp_attach_campaign_coupon(v_admin,v_campaign,v_coupon);
  END;
  CALL test_assert(v_rejected,'Shop campaign must reject other merchant coupon');

  INSERT INTO reports(coupon_id,reported_by,reason,details) VALUES(v_coupon,v_first_reader,'OTHER','Smoke report');
  SET v_report=LAST_INSERT_ID();
  CALL sp_resolve_report(v_admin,v_report,'DISMISSED','Test resolved');
  SELECT COUNT(*) INTO v_n FROM reports WHERE id=v_report AND status='DISMISSED' AND resolved_by=v_admin AND resolved_at IS NOT NULL;
  CALL test_assert(v_n=1,'Report resolution must keep resolver and time');

  -- Boundary policy: same code in a nonoverlapping period is allowed.
  CALL sp_submit_coupon(v_author,v_merchant,v_category,0,'Next round','SMOKE15',NULL,'Future round',
    'FIXED_AMOUNT',10000,NULL,0,NULL,'ONLINE',NULL,NULL,NULL,v_end,DATE_ADD(v_end,INTERVAL 1 DAY));
  SELECT COUNT(*) INTO v_n FROM coupons WHERE merchant_id=v_merchant AND code_normalized='SMOKE15';
  CALL test_assert(v_n=2,'Nonoverlapping reuse of code must be allowed');

  -- Direct update is intentional here solely to simulate time passing in test DB.
  UPDATE coupons SET starts_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 2 DAY),ends_at=DATE_SUB(UTC_TIMESTAMP(),INTERVAL 1 DAY) WHERE id=v_coupon;
  SELECT effective_status INTO v_state FROM v_coupon_status WHERE id=v_coupon;
  CALL test_assert(v_state='EXPIRED','Expiry must be derived without scheduler');
  SET v_rejected=FALSE;
  BEGIN
    DECLARE CONTINUE HANDLER FOR SQLSTATE '45000' SET v_rejected=TRUE;
    CALL sp_set_vote(v_first_reader,v_coupon,'WORKING');
  END;
  CALL test_assert(v_rejected,'Expired coupon must reject new vote');

  SELECT 'PASS: sequential smoke assertions completed; concurrency tests still required' AS test_result;
END$$
DELIMITER ;
CALL test_couponhub();
DROP PROCEDURE test_couponhub;
DROP PROCEDURE test_assert;

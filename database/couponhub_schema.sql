-- CouponHub - database design v1, 2026-09-29.
-- Target: MySQL >= 8.0.16 / MariaDB >= 10.4; InnoDB; utf8mb4.
-- Import ONCE into a NEW, EMPTY database selected in phpMyAdmin.
-- No DROP DATABASE, no sample accounts, no hard-coded DEFINER.
-- All DATETIME values are UTC. Application connections must also use UTC.
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET collation_connection = 'utf8mb4_unicode_ci';
SET time_zone = '+00:00';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  avatar_path VARCHAR(500) NULL,
  role ENUM('USER','MERCHANT','ADMIN') NOT NULL DEFAULT 'USER',
  status ENUM('ACTIVE','LOCKED') NOT NULL DEFAULT 'ACTIVE',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  KEY idx_users_role_status (role,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE merchants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_user_id BIGINT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  description TEXT NULL,
  website_url VARCHAR(2048) NULL,
  logo_path VARCHAR(500) NULL,
  status ENUM('UNCLAIMED','PENDING','APPROVED','REJECTED','LOCKED') NOT NULL DEFAULT 'UNCLAIMED',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_merchants_owner FOREIGN KEY (owner_user_id) REFERENCES users(id),
  CONSTRAINT fk_merchants_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id),
  CONSTRAINT ck_merchant_owner CHECK (status <> 'APPROVED' OR owner_user_id IS NOT NULL),
  KEY idx_merchants_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(191) NOT NULL UNIQUE,
  description TEXT NULL,
  is_active TINYINT NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT ck_category_active CHECK (is_active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupons (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  merchant_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  submitted_by BIGINT UNSIGNED NOT NULL,
  source ENUM('COMMUNITY','OFFICIAL') NOT NULL DEFAULT 'COMMUNITY',
  title VARCHAR(200) NOT NULL,
  code VARCHAR(100) NULL,
  code_normalized VARCHAR(100) GENERATED ALWAYS AS (NULLIF(UPPER(TRIM(code)),'')) STORED,
  description TEXT NULL,
  terms TEXT NOT NULL,
  discount_type ENUM('PERCENTAGE','FIXED_AMOUNT','FREE_SHIPPING','BUY_X_GET_Y','GIFT','CASHBACK') NOT NULL,
  discount_value DECIMAL(15,2) NULL,
  cashback_unit ENUM('PERCENTAGE','FIXED_AMOUNT') NULL,
  min_order_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  max_discount_amount DECIMAL(15,2) NULL,
  currency CHAR(3) NOT NULL DEFAULT 'VND',
  usage_channel ENUM('ONLINE','OFFLINE','BOTH') NOT NULL,
  promotion_url VARCHAR(2048) NULL,
  qr_image_path VARCHAR(500) NULL,
  image_path VARCHAR(500) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  moderation_status ENUM('PENDING','APPROVED','REJECTED') NOT NULL DEFAULT 'PENDING',
  publication_status ENUM('ENABLED','DISABLED','EXPIRED','ARCHIVED') NOT NULL DEFAULT 'ENABLED',
  reviewed_by BIGINT UNSIGNED NULL,
  reviewed_at DATETIME NULL,
  review_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_coupon_merchant FOREIGN KEY (merchant_id) REFERENCES merchants(id),
  CONSTRAINT fk_coupon_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT fk_coupon_author FOREIGN KEY (submitted_by) REFERENCES users(id),
  CONSTRAINT fk_coupon_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id),
  CONSTRAINT ck_coupon_dates CHECK (ends_at > starts_at),
  CONSTRAINT ck_coupon_access CHECK (NULLIF(TRIM(code),'') IS NOT NULL OR NULLIF(TRIM(promotion_url),'') IS NOT NULL OR NULLIF(TRIM(qr_image_path),'') IS NOT NULL),
  CONSTRAINT ck_coupon_currency CHECK (currency = 'VND'),
  CONSTRAINT ck_coupon_money CHECK (min_order_amount >= 0 AND (max_discount_amount IS NULL OR max_discount_amount > 0)),
  CONSTRAINT ck_coupon_discount CHECK (
    (discount_type = 'PERCENTAGE' AND discount_value IS NOT NULL AND discount_value > 0 AND discount_value <= 100 AND cashback_unit IS NULL)
    OR (discount_type = 'FIXED_AMOUNT' AND discount_value IS NOT NULL AND discount_value > 0 AND cashback_unit IS NULL)
    OR (discount_type = 'CASHBACK' AND cashback_unit IS NOT NULL AND discount_value IS NOT NULL AND discount_value > 0 AND (cashback_unit = 'FIXED_AMOUNT' OR discount_value <= 100))
    OR (discount_type IN ('FREE_SHIPPING','BUY_X_GET_Y','GIFT') AND discount_value IS NULL AND cashback_unit IS NULL)
  ),
  KEY idx_coupon_duplicate (merchant_id,code_normalized,starts_at,ends_at),
  KEY idx_coupon_public (moderation_status,publication_status,ends_at),
  KEY idx_coupon_category_date (category_id,ends_at),
  KEY idx_coupon_author_date (submitted_by,created_at),
  KEY idx_coupon_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campaigns (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_merchant_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  description TEXT NULL,
  banner_path VARCHAR(500) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NOT NULL,
  status ENUM('DRAFT','PUBLISHED','DISABLED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_campaign_owner FOREIGN KEY (owner_merchant_id) REFERENCES merchants(id),
  CONSTRAINT fk_campaign_creator FOREIGN KEY (created_by) REFERENCES users(id),
  CONSTRAINT ck_campaign_dates CHECK (ends_at > starts_at),
  KEY idx_campaign_status_dates (status,starts_at,ends_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campaign_coupons (
  campaign_id BIGINT UNSIGNED NOT NULL,
  coupon_id BIGINT UNSIGNED NOT NULL,
  added_by BIGINT UNSIGNED NOT NULL,
  added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (campaign_id,coupon_id),
  CONSTRAINT fk_cc_campaign FOREIGN KEY (campaign_id) REFERENCES campaigns(id),
  CONSTRAINT fk_cc_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  CONSTRAINT fk_cc_actor FOREIGN KEY (added_by) REFERENCES users(id),
  KEY idx_cc_coupon (coupon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favorites (
  user_id BIGINT UNSIGNED NOT NULL,
  coupon_id BIGINT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id,coupon_id),
  CONSTRAINT fk_favorite_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_favorite_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  KEY idx_favorite_coupon (coupon_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE votes (
  user_id BIGINT UNSIGNED NOT NULL,
  coupon_id BIGINT UNSIGNED NOT NULL,
  vote_type ENUM('WORKING','NOT_WORKING','CONDITION_CHANGED') NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id,coupon_id),
  CONSTRAINT fk_vote_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_vote_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  KEY idx_vote_coupon_type (coupon_id,vote_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  body TEXT NOT NULL,
  status ENUM('VISIBLE','HIDDEN','DELETED') NOT NULL DEFAULT 'VISIBLE',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_comment_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT ck_comment_body CHECK (CHAR_LENGTH(TRIM(body)) BETWEEN 1 AND 3000),
  KEY idx_comment_coupon_date (coupon_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_id BIGINT UNSIGNED NOT NULL,
  reported_by BIGINT UNSIGNED NOT NULL,
  reason ENUM('EXPIRED','NOT_WORKING','WRONG_TERMS','DUPLICATE','SPAM','FAKE','INAPPROPRIATE','OTHER') NOT NULL,
  details TEXT NULL,
  status ENUM('OPEN','RESOLVED','DISMISSED') NOT NULL DEFAULT 'OPEN',
  resolved_by BIGINT UNSIGNED NULL,
  resolved_at DATETIME NULL,
  resolution_note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_report_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  CONSTRAINT fk_report_author FOREIGN KEY (reported_by) REFERENCES users(id),
  CONSTRAINT fk_report_resolver FOREIGN KEY (resolved_by) REFERENCES users(id),
  CONSTRAINT ck_report_resolution CHECK (
    (status = 'OPEN' AND resolved_by IS NULL AND resolved_at IS NULL)
    OR (status IN ('RESOLVED','DISMISSED') AND resolved_by IS NOT NULL AND resolved_at IS NOT NULL)
  ),
  KEY idx_report_queue (status,created_at),
  KEY idx_report_author_coupon (reported_by,coupon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE levels (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  min_points INT UNSIGNED NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE point_rules (
  code VARCHAR(40) PRIMARY KEY,
  description VARCHAR(200) NOT NULL,
  points INT NOT NULL,
  threshold_count INT UNSIGNED NOT NULL,
  is_active TINYINT NOT NULL DEFAULT 1,
  CONSTRAINT ck_rule_points CHECK (points > 0),
  CONSTRAINT ck_rule_active CHECK (is_active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE point_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  coupon_id BIGINT UNSIGNED NOT NULL,
  rule_code VARCHAR(40) NOT NULL,
  points INT NOT NULL,
  note VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_points_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_points_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  CONSTRAINT fk_points_rule FOREIGN KEY (rule_code) REFERENCES point_rules(code),
  CONSTRAINT ck_transaction_points CHECK (points > 0),
  UNIQUE KEY uq_coupon_award (coupon_id,rule_code),
  KEY idx_points_user_date (user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupon_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  event_type ENUM('VIEW','COPY','SHARE','FAVORITE_ADD','VOTE') NOT NULL,
  request_key VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_event_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  CONSTRAINT fk_event_user FOREIGN KEY (user_id) REFERENCES users(id),
  UNIQUE KEY uq_event_request (request_key),
  KEY idx_events_coupon_time (coupon_id,occurred_at,event_type),
  KEY idx_events_time (occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupon_status_history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_id BIGINT UNSIGNED NOT NULL,
  actor_id BIGINT UNSIGNED NOT NULL,
  old_moderation VARCHAR(20) NOT NULL,
  new_moderation VARCHAR(20) NOT NULL,
  old_publication VARCHAR(20) NOT NULL,
  new_publication VARCHAR(20) NOT NULL,
  note TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  CONSTRAINT fk_history_actor FOREIGN KEY (actor_id) REFERENCES users(id),
  KEY idx_history_coupon_date (coupon_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE articles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  author_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NULL,
  title VARCHAR(200) NOT NULL,
  slug VARCHAR(191) NOT NULL UNIQUE,
  summary TEXT NULL,
  body LONGTEXT NOT NULL,
  cover_path VARCHAR(500) NULL,
  status ENUM('DRAFT','PUBLISHED','ARCHIVED') NOT NULL DEFAULT 'DRAFT',
  published_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_article_author FOREIGN KEY (author_id) REFERENCES users(id),
  CONSTRAINT fk_article_category FOREIGN KEY (category_id) REFERENCES categories(id),
  CONSTRAINT ck_article_publication CHECK (status <> 'PUBLISHED' OR published_at IS NOT NULL),
  KEY idx_article_publication (status,published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  coupon_id BIGINT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  description VARCHAR(500) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_activity_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
  KEY idx_activity_user_date (user_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO levels (name,min_points) VALUES ('Newbie',0),('Deal Hunter',100),('Deal Expert',500),('Coupon Master',1500);
INSERT INTO point_rules (code,description,points,threshold_count) VALUES
 ('COUPON_APPROVED','Coupon cộng đồng được duyệt',5,1),
 ('WORKING_10','Coupon đạt 10 xác nhận dùng được',10,10),
 ('FAVORITES_100','Coupon đạt 100 người lưu',20,100);
INSERT INTO categories (name,slug,sort_order) VALUES
 ('Mua sắm','mua-sam',1),('Ăn uống','an-uong',2),('Du lịch','du-lich',3),('Công nghệ','cong-nghe',4),
 ('Giải trí','giai-tri',5),('Làm đẹp','lam-dep',6),('Dịch vụ','dich-vu',7),('Thời trang','thoi-trang',8);

-- Dynamic states: no cron/Event Scheduler needed to expire coupons.
CREATE SQL SECURITY INVOKER VIEW v_coupon_status AS
SELECT c.*,
 CASE
   WHEN c.deleted_at IS NOT NULL OR c.publication_status = 'ARCHIVED' THEN 'ARCHIVED'
   WHEN c.publication_status = 'DISABLED' OR m.status = 'LOCKED' OR m.deleted_at IS NOT NULL OR cat.is_active = 0 THEN 'DISABLED'
   WHEN c.moderation_status = 'REJECTED' THEN 'REJECTED'
   WHEN c.moderation_status = 'PENDING' THEN 'PENDING'
   WHEN c.publication_status = 'EXPIRED' OR UTC_TIMESTAMP() >= c.ends_at THEN 'EXPIRED'
   WHEN UTC_TIMESTAMP() < c.starts_at THEN 'SCHEDULED'
   ELSE 'ACTIVE'
 END AS effective_status,
 CASE WHEN c.source = 'OFFICIAL' AND m.status = 'APPROVED' AND m.deleted_at IS NULL THEN 1 ELSE 0 END AS is_official
FROM coupons c JOIN merchants m ON m.id=c.merchant_id JOIN categories cat ON cat.id=c.category_id;

CREATE SQL SECURITY INVOKER VIEW v_coupon_vote_stats AS
SELECT c.id AS coupon_id,
 COALESCE(SUM(v.vote_type='WORKING'),0) AS working_count,
 COALESCE(SUM(v.vote_type='NOT_WORKING'),0) AS not_working_count,
 COALESCE(SUM(v.vote_type='CONDITION_CHANGED'),0) AS condition_changed_count,
 COUNT(v.user_id) AS total_votes,
 ROUND(100.0 * SUM(v.vote_type='WORKING') / NULLIF(SUM(v.vote_type IN ('WORKING','NOT_WORKING')),0),2) AS trust_score,
 MAX(v.updated_at) AS last_verification_at
FROM coupons c LEFT JOIN votes v ON v.coupon_id=c.id
GROUP BY c.id;

CREATE SQL SECURITY INVOKER VIEW v_user_points AS
SELECT u.id AS user_id,COALESCE(SUM(p.points),0) AS earned_points
FROM users u LEFT JOIN point_transactions p ON p.user_id=u.id GROUP BY u.id;

CREATE SQL SECURITY INVOKER VIEW v_user_level AS
SELECT p.user_id,p.earned_points,l.id AS level_id,l.name AS level_name
FROM v_user_points p JOIN levels l ON l.min_points=(SELECT MAX(l2.min_points) FROM levels l2 WHERE l2.min_points<=p.earned_points);

CREATE SQL SECURITY INVOKER VIEW v_contributor_stats AS
SELECT u.id AS user_id,COUNT(c.id) AS submitted_count,
 COALESCE(SUM(c.moderation_status='APPROVED'),0) AS approved_count,
 COALESCE(SUM(c.moderation_status='REJECTED'),0) AS rejected_count
FROM users u LEFT JOIN coupons c ON c.submitted_by=u.id AND c.source='COMMUNITY'
GROUP BY u.id;

DELIMITER $$

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

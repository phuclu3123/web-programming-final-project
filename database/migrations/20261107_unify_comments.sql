-- CouponHub 2026-11-07: unify coupon/article comments in comments (22 -> 21 tables).
-- Applies to the 20-table consolidated core, or the 22-table baseline with article_comments.
-- REQUIREMENTS: full backup; stop all CouponHub writers; select the intended database.
-- Do not import schema.sql or demo.sql over an existing database.
-- Existing coupon comment IDs are unchanged. Article comment IDs are reassigned and
-- ARTICLE_COMMENT_* activity log references are translated before dropping the old table.
-- MySQL/MariaDB DDL commits independently; if interrupted, keep the backup and rerun
-- THIS script after checking database status. Do not manually DROP article_comments.
SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci;

DELIMITER $$
DROP PROCEDURE IF EXISTS couponhub_unify_comments_20261107$$
CREATE PROCEDURE couponhub_unify_comments_20261107()
MODIFIES SQL DATA
SQL SECURITY INVOKER
BEGIN
  DECLARE v_legacy_exists INT DEFAULT 0;
  DECLARE v_current_has_article INT DEFAULT 0;
  DECLARE v_current_has_request INT DEFAULT 0;
  DECLARE v_legacy_count BIGINT DEFAULT 0;
  DECLARE v_valid_copy BIGINT DEFAULT 0;
  DECLARE v_invalid_audit BIGINT DEFAULT 0;
  DECLARE v_pending_audit BIGINT DEFAULT 0;

  IF (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND TABLE_TYPE='BASE TABLE') <> 1
    OR (SELECT COUNT(*) FROM information_schema.TABLES
      WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='articles' AND TABLE_TYPE='BASE TABLE') <> 1
  THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Missing comments/articles: stop, select consolidated CouponHub DB.';
  END IF;

  SELECT COUNT(*) INTO v_current_has_article FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND COLUMN_NAME='article_id';
  SELECT COUNT(*) INTO v_current_has_request FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND COLUMN_NAME='request_id';
  SELECT COUNT(*) INTO v_legacy_exists FROM information_schema.TABLES
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='article_comments' AND TABLE_TYPE='BASE TABLE';

  IF v_current_has_article <> v_current_has_request THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Partial comments schema; inspect manually before migration.';
  END IF;

  IF v_legacy_exists = 1 THEN
    SELECT COUNT(*) INTO v_invalid_audit FROM activity_logs
     WHERE action LIKE 'ARTICLE_COMMENT_%'
       AND (description IS NULL OR JSON_VALID(description) = 0);
    IF v_invalid_audit > 0 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invalid article-comment audit JSON; refusing migration.';
    END IF;
  END IF;

  IF v_current_has_article = 0 THEN
    -- Drop/recreate just the coupon FK while relaxing coupon_id to NULL.
    -- Coupon rows remain unchanged; article rows must have coupon_id NULL.
    -- MariaDB 10.4 cannot DROP and re-ADD the same FK name in one ALTER.
    -- If a previous attempt stopped after dropping this FK, a retry can proceed.
    IF EXISTS (
      SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
      WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'comments'
        AND CONSTRAINT_NAME = 'fk_comment_coupon'
        AND CONSTRAINT_TYPE = 'FOREIGN KEY'
    ) THEN
      ALTER TABLE comments DROP FOREIGN KEY fk_comment_coupon;
    END IF;

    ALTER TABLE comments
      MODIFY COLUMN coupon_id BIGINT UNSIGNED NULL,
      ADD COLUMN article_id BIGINT UNSIGNED NULL AFTER coupon_id,
      ADD COLUMN request_id VARBINARY(32) NULL AFTER status,
      ADD UNIQUE KEY uq_comment_article_request (user_id,request_id),
      ADD KEY idx_comment_article_page (article_id,status,id),
      ADD KEY idx_comment_article_moderation (status,id),
      ADD CONSTRAINT fk_comment_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id),
      ADD CONSTRAINT fk_comment_article FOREIGN KEY (article_id) REFERENCES articles(id),
      ADD CONSTRAINT ck_comment_target CHECK (
        (coupon_id IS NOT NULL AND article_id IS NULL AND request_id IS NULL)
        OR (coupon_id IS NULL AND article_id IS NOT NULL AND request_id IS NOT NULL)
      );
  END IF;

  IF v_legacy_exists = 1 THEN
    -- All application writers MUST be stopped until this routine completes.
    -- DML is transactional. The UNIQUE(user_id,request_id) key makes retries safe.
    START TRANSACTION;
    INSERT INTO comments (article_id,user_id,body,status,request_id,created_at,updated_at)
    SELECT a.article_id,a.user_id,a.body,a.status,a.request_id,a.created_at,a.updated_at
      FROM article_comments a
      WHERE NOT EXISTS (
        SELECT 1 FROM comments c WHERE c.user_id=a.user_id AND c.request_id=a.request_id
      )
      ORDER BY a.id;

    SELECT COUNT(*) INTO v_legacy_count FROM article_comments;
    SELECT COUNT(*) INTO v_valid_copy FROM article_comments a
      JOIN comments c ON c.user_id=a.user_id AND c.request_id=a.request_id
       AND c.article_id=a.article_id AND c.coupon_id IS NULL
       AND BINARY c.body=BINARY a.body AND c.status=a.status
       AND c.created_at=a.created_at AND c.updated_at=a.updated_at;
    IF v_legacy_count <> v_valid_copy THEN
      ROLLBACK;
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Article comment parity failed; old table preserved.';
    END IF;

    -- Preserve audit links even if article_comment.id collided with coupon comment.id.
    -- A marker prevents double remapping if this migration needs to be rerun.
    UPDATE activity_logs l
      JOIN article_comments a
        ON l.action LIKE 'ARTICLE_COMMENT_%'
       AND JSON_VALID(l.description) = 1
       AND JSON_EXTRACT(l.description,'$.comment_migrated_21') IS NULL
       AND JSON_UNQUOTE(JSON_EXTRACT(l.description,'$.article_comment_id')) = CAST(a.id AS CHAR)
      JOIN comments c ON c.user_id=a.user_id AND c.request_id=a.request_id
    SET l.description=JSON_SET(l.description,
      '$.article_comment_id',c.id,'$.comment_migrated_21',1);

    -- Any unmapped article-comment audit points at a possibly stale ID.
    -- Refuse to remove the original table until all such links are resolved.
    SELECT COUNT(*) INTO v_pending_audit FROM activity_logs l
     WHERE l.action LIKE 'ARTICLE_COMMENT_%'
       AND JSON_EXTRACT(l.description,'$.comment_migrated_21') IS NULL;
    IF v_pending_audit > 0 THEN
      ROLLBACK;
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Unmapped article audit IDs; old table preserved.';
    END IF;

    COMMIT;

    -- Verify again after committing and BEFORE the only destructive operation.
    SELECT COUNT(*) INTO v_valid_copy FROM article_comments a
      JOIN comments c ON c.user_id=a.user_id AND c.request_id=a.request_id
       AND c.article_id=a.article_id AND c.coupon_id IS NULL
       AND BINARY c.body=BINARY a.body AND c.status=a.status
       AND c.created_at=a.created_at AND c.updated_at=a.updated_at;
    IF v_legacy_count <> v_valid_copy THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Post-commit parity failed; old table preserved.';
    END IF;
    DROP TABLE article_comments;
  END IF;
END$$
CALL couponhub_unify_comments_20261107()$$
DROP PROCEDURE couponhub_unify_comments_20261107$$
DELIMITER ;

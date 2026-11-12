-- Additive: back up first. Comment events and read receipts persist by account.
-- Rows with kind NULL are read receipts for notifications derived from coupon/shop workflows.
CREATE TABLE IF NOT EXISTS notifications (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 event_key VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 actor_id BIGINT UNSIGNED DEFAULT NULL,
 comment_id BIGINT UNSIGNED DEFAULT NULL,
 subject_comment_id BIGINT UNSIGNED DEFAULT NULL,
 kind ENUM('ARTICLE_REPLY','COMMENT_LIKE') DEFAULT NULL,
 active TINYINT UNSIGNED NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 read_at DATETIME DEFAULT NULL,
 UNIQUE KEY uq_notification_user_event(user_id,event_key),
 KEY ix_notification_feed(user_id,active,created_at),
 CONSTRAINT fk_notification_user FOREIGN KEY(user_id) REFERENCES users(id),
 CONSTRAINT fk_notification_actor FOREIGN KEY(actor_id) REFERENCES users(id),
 CONSTRAINT fk_notification_comment FOREIGN KEY(comment_id) REFERENCES comments(id),
 CONSTRAINT fk_notification_subject FOREIGN KEY(subject_comment_id) REFERENCES comments(id),
 CONSTRAINT ck_notification_active CHECK(active IN (0,1)),
 CONSTRAINT ck_notification_event CHECK(kind IS NULL OR (actor_id IS NOT NULL AND comment_id IS NOT NULL AND subject_comment_id IS NOT NULL AND actor_id<>user_id))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- Backfill existing interactions once; keep read state and original timestamps on rerun.
INSERT INTO notifications(user_id,event_key,actor_id,comment_id,subject_comment_id,kind,created_at)
SELECT parent.user_id,CONCAT('article-reply-',reply.id),reply.user_id,reply.id,parent.id,'ARTICLE_REPLY',reply.created_at
FROM comments reply JOIN comments parent ON parent.id=reply.parent_comment_id
WHERE reply.article_id IS NOT NULL AND reply.status='VISIBLE' AND parent.status='VISIBLE' AND parent.user_id<>reply.user_id
ON DUPLICATE KEY UPDATE event_key=VALUES(event_key);
INSERT INTO notifications(user_id,event_key,actor_id,comment_id,subject_comment_id,kind,created_at)
SELECT c.user_id,CONCAT('comment-like-',c.id,'-',r.user_id),r.user_id,c.id,c.id,'COMMENT_LIKE',r.created_at
FROM comment_reactions r JOIN comments c ON c.id=r.comment_id
WHERE c.article_id IS NOT NULL AND c.status='VISIBLE' AND c.user_id<>r.user_id
ON DUPLICATE KEY UPDATE event_key=VALUES(event_key);

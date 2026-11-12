-- HISTORICAL ONLY for old 22-table installs. Do NOT run on 21-table baseline!
-- Current migration: 20261107_unify_comments.sql
-- Additive migration: select the existing CouponHub database; never re-import schema/demo.
SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci;
CREATE TABLE IF NOT EXISTS article_comments (
 id bigint unsigned NOT NULL AUTO_INCREMENT,
 article_id bigint unsigned NOT NULL,
 user_id bigint unsigned NOT NULL,
 body text NOT NULL,
 status enum('VISIBLE','HIDDEN','DELETED') NOT NULL DEFAULT 'VISIBLE',
 request_id varbinary(32) NOT NULL,
 created_at datetime NOT NULL DEFAULT current_timestamp(),
 updated_at datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
 PRIMARY KEY (id),
 UNIQUE KEY uq_article_comment_request (user_id,request_id),
 KEY idx_article_comment_page (article_id,status,id),
 KEY idx_article_comment_status (status,id),
 CONSTRAINT fk_article_comment_article FOREIGN KEY (article_id) REFERENCES articles(id),
 CONSTRAINT fk_article_comment_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- Back up first. Additive upgrade for the unified comments schema; no existing rows are rewritten.
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND COLUMN_NAME='parent_comment_id')=0,
 'ALTER TABLE comments ADD COLUMN parent_comment_id BIGINT UNSIGNED DEFAULT NULL', 'SELECT 1');
PREPARE comment_upgrade FROM @ddl;
EXECUTE comment_upgrade;
DEALLOCATE PREPARE comment_upgrade;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND COLUMN_NAME='root_comment_id')=0,
 'ALTER TABLE comments ADD COLUMN root_comment_id BIGINT UNSIGNED DEFAULT NULL', 'SELECT 1');
PREPARE comment_upgrade FROM @ddl;
EXECUTE comment_upgrade;
DEALLOCATE PREPARE comment_upgrade;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND INDEX_NAME='ix_comment_thread')=0,
 'ALTER TABLE comments ADD KEY ix_comment_thread(article_id,root_comment_id,status,id)', 'SELECT 1');
PREPARE comment_upgrade FROM @ddl;
EXECUTE comment_upgrade;
DEALLOCATE PREPARE comment_upgrade;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND CONSTRAINT_NAME='fk_comment_parent')=0,
 'ALTER TABLE comments ADD CONSTRAINT fk_comment_parent FOREIGN KEY(parent_comment_id) REFERENCES comments(id)', 'SELECT 1');
PREPARE comment_upgrade FROM @ddl;
EXECUTE comment_upgrade;
DEALLOCATE PREPARE comment_upgrade;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND CONSTRAINT_NAME='fk_comment_root')=0,
 'ALTER TABLE comments ADD CONSTRAINT fk_comment_root FOREIGN KEY(root_comment_id) REFERENCES comments(id)', 'SELECT 1');
PREPARE comment_upgrade FROM @ddl;
EXECUTE comment_upgrade;
DEALLOCATE PREPARE comment_upgrade;
SET @ddl = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='comments' AND CONSTRAINT_NAME='ck_comment_thread')=0,
 'ALTER TABLE comments ADD CONSTRAINT ck_comment_thread CHECK ((parent_comment_id IS NULL AND root_comment_id IS NULL) OR (article_id IS NOT NULL AND parent_comment_id IS NOT NULL AND root_comment_id IS NOT NULL))', 'SELECT 1');
PREPARE comment_upgrade FROM @ddl;
EXECUTE comment_upgrade;
DEALLOCATE PREPARE comment_upgrade;

CREATE TABLE IF NOT EXISTS comment_reactions (
 comment_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(comment_id,user_id),
 KEY ix_comment_reaction_user(user_id),
 CONSTRAINT fk_comment_reaction_comment FOREIGN KEY(comment_id) REFERENCES comments(id),
 CONSTRAINT fk_comment_reaction_user FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- Additive migration: back up the current database before applying.
CREATE TABLE IF NOT EXISTS external_campaigns (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 provider VARCHAR(32) NOT NULL DEFAULT 'accesstrade',
 provider_id VARCHAR(191) NOT NULL,
 payload LONGTEXT NOT NULL,
 fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 approved_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
 state ENUM('PENDING','APPROVED','DISMISSED') NOT NULL DEFAULT 'PENDING',
 source_running TINYINT UNSIGNED NOT NULL DEFAULT 0,
 starts_at DATETIME DEFAULT NULL,
 ends_at DATETIME DEFAULT NULL,
 fetched_at DATETIME NOT NULL,
 reviewed_by BIGINT UNSIGNED DEFAULT NULL,
 reviewed_at DATETIME DEFAULT NULL,
 review_note VARCHAR(1000) DEFAULT NULL,
 UNIQUE KEY uq_external_campaign(provider,provider_id),
 KEY ix_external_campaign_queue(state,fetched_at),
 CONSTRAINT fk_external_campaign_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id),
 CONSTRAINT ck_external_campaign_running CHECK(source_running IN (0,1)),
 CONSTRAINT ck_external_campaign_dates CHECK(ends_at IS NULL OR starts_at IS NULL OR ends_at>starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

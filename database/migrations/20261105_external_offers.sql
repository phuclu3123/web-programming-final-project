-- Additive only. Back up an existing database first; never re-import schema/demo.
CREATE TABLE IF NOT EXISTS external_offers (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 provider VARCHAR(32) NOT NULL,
 external_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 provider_id VARCHAR(191) NOT NULL,
 code VARCHAR(100) DEFAULT NULL,
 payload LONGTEXT NOT NULL,
 fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 imported_fingerprint CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
 state ENUM('PENDING','IMPORTED','DISMISSED') NOT NULL DEFAULT 'PENDING',
 coupon_id BIGINT UNSIGNED DEFAULT NULL,
 fetched_at DATETIME NOT NULL,
 imported_at DATETIME DEFAULT NULL,
 UNIQUE KEY uq_external_offer(provider,external_key),
 KEY ix_external_state(state,fetched_at),
 CONSTRAINT fk_external_coupon FOREIGN KEY(coupon_id) REFERENCES coupons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

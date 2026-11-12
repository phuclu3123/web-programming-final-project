-- Apply ONLY to the 20-table migration snapshot after making a backup.
-- Preserve Facebook OAuth support already present in main.
-- This is NOT required for fresh installs from database/schema.sql.
SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci;
ALTER TABLE oauth_accounts
  MODIFY provider ENUM('github','google','facebook') NOT NULL;

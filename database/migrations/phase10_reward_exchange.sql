-- CouponHub Phase 10: Reward Exchange. Install once after schema.sql (and after Phase 8 if applicable).
-- Never subtract from point_transactions: earned_points remain the basis of Level.
-- Pending and fulfilled requests reserve/spend wallet points; cancelled requests release them.
SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci;

CREATE TABLE IF NOT EXISTS reward_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL,
  title VARCHAR(160) NOT NULL,
  description VARCHAR(500) NOT NULL,
  cost_points INT UNSIGNED NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reward_code (code),
  KEY idx_reward_active_cost (is_active,cost_points)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

CREATE TABLE IF NOT EXISTS reward_redemptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  reward_id BIGINT UNSIGNED NOT NULL,
  points_spent INT UNSIGNED NOT NULL,
  idempotency_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  status ENUM('PENDING','FULFILLED','CANCELLED') NOT NULL DEFAULT 'PENDING',
  admin_note VARCHAR(500) DEFAULT NULL,
  processed_by BIGINT UNSIGNED DEFAULT NULL,
  processed_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_reward_redeem_once (user_id,idempotency_key),
  KEY idx_reward_redemptions_wallet (user_id,status),
  KEY idx_reward_redemptions_queue (status,created_at,id),
  KEY idx_reward_redemptions_reward (reward_id),
  CONSTRAINT fk_reward_redemption_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_reward_redemption_item FOREIGN KEY (reward_id) REFERENCES reward_items(id),
  CONSTRAINT fk_reward_redemption_admin FOREIGN KEY (processed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

-- Two example rewards from brief.md. These are REQUESTS for manual fulfilment, not live voucher codes.
-- Admin must verify available inventory/partner commitments before marking a request fulfilled.
INSERT IGNORE INTO reward_items(code,title,description,cost_points,is_active) VALUES
('VOUCHER_50K','Yêu cầu voucher 50.000đ','Đổi 500 điểm để gửi yêu cầu voucher 50.000đ. Chờ Admin xác minh và giao voucher thủ công; không phát mã tự động.',500,1),
('EXCLUSIVE_DEAL','Yêu cầu Exclusive Deal','Đổi 1.000 điểm để yêu cầu ưu đãi độc quyền. Chờ Admin xác minh khả năng cung cấp và phản hồi.',1000,1);

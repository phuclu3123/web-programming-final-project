-- CouponHub Phase 8 / Deal Request. Import ONCE into a database with schema.sql already installed.
-- No DROP, no existing data modifications, no additional CouponPoint policy.
SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci;
CREATE TABLE IF NOT EXISTS deal_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(160) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  closed_at DATETIME NULL,
  KEY idx_deal_requests_status_created (status, created_at, id),
  KEY idx_deal_requests_user_status (user_id, status),
  CONSTRAINT fk_deal_requests_author FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

CREATE TABLE IF NOT EXISTS deal_request_replies (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  request_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  coupon_id BIGINT UNSIGNED NOT NULL,
  note VARCHAR(500) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_deal_request_coupon (request_id, coupon_id),
  KEY idx_deal_request_replies_actor (user_id, created_at),
  KEY idx_deal_request_replies_coupon (coupon_id),
  CONSTRAINT fk_deal_reply_request FOREIGN KEY (request_id) REFERENCES deal_requests(id),
  CONSTRAINT fk_deal_reply_author FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_deal_reply_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci;

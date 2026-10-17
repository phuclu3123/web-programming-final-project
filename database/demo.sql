-- COUPONHUB: DỮ LIỆU DEMO TÙY CHỌN
-- Chọn database mới, import schema.sql trước, rồi import file này MỘT LẦN.
-- 6 tài khoản / 12 shop / 32 coupon / 2 chiến dịch và tương tác mẫu.
-- Username, mật khẩu và quyền shop: docs/DEMO_DATA.md.
-- Chỉ dữ liệu giả từ fixture; không phải chương trình của các thương hiệu thật.
-- Không chứa OAuth/reset token. Mật khẩu được lưu bằng bcrypt.
-- Ngày hiệu lực tương đối theo UTC tại thời điểm import, không cố định theo ngày xuất.
-- Từ chối nạp nếu có dữ liệu trong bất kỳ bảng nào; không xóa/ghi đè dữ liệu.
SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci;
SET time_zone = '+00:00';
SET @couponhub_demo_previous_sql_mode = @@SESSION.sql_mode;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION,NO_BACKSLASH_ESCAPES';

DELIMITER $$
CREATE PROCEDURE couponhub_install_demo()
MODIFIES SQL DATA
SQL SECURITY INVOKER
BEGIN
  DECLARE demo_anchor DATETIME;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
  BEGIN
    ROLLBACK;
    RESIGNAL;
  END;
  START TRANSACTION;
  IF
    EXISTS (SELECT 1 FROM `activity_logs` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `articles` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `campaigns` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `campaign_coupons` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `categories` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `comments` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `coupons` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `coupon_events` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `coupon_status_history` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `favorites` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `levels` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `merchants` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `merchant_memberships` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `merchant_requests` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `oauth_accounts` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `password_reset_tokens` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `point_rules` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `point_transactions` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `reports` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `users` LIMIT 1)
    OR EXISTS (SELECT 1 FROM `votes` LIMIT 1)
  THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Demo import requires an empty CouponHub schema; existing data was kept.';
  END IF;
  SET demo_anchor = UTC_TIMESTAMP();

  -- categories: 8
  INSERT INTO `categories` (`id`,`name`,`slug`,`description`,`is_active`,`sort_order`,`created_at`) VALUES
    (1, 'Mua sắm', 'mua-sam', 'Danh mục ưu đãi mua sắm', 1, 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (2, 'Ăn uống', 'an-uong', 'Danh mục ưu đãi ăn uống', 1, 2, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (3, 'Du lịch', 'du-lich', 'Danh mục ưu đãi du lịch', 1, 3, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (4, 'Công nghệ', 'cong-nghe', 'Danh mục ưu đãi công nghệ', 1, 4, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (5, 'Giải trí', 'giai-tri', 'Danh mục ưu đãi giải trí', 1, 5, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (6, 'Làm đẹp', 'lam-dep', 'Danh mục ưu đãi làm đẹp', 1, 6, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (7, 'Dịch vụ', 'dich-vu', 'Danh mục ưu đãi dịch vụ', 1, 7, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (8, 'Thời trang', 'thoi-trang', 'Danh mục ưu đãi thời trang', 1, 8, DATE_ADD(demo_anchor, INTERVAL -438 SECOND));

  -- levels: 4
  INSERT INTO `levels` (`id`,`name`,`min_points`) VALUES
    (1, 'Thành viên mới', 0),
    (2, 'Tích cực', 100),
    (3, 'Cộng tác viên', 300),
    (4, 'Chuyên gia', 1000);

  -- point_rules: 3
  INSERT INTO `point_rules` (`code`,`description`,`points`,`threshold_count`,`is_active`) VALUES
    ('COUPON_APPROVED', 'Coupon cộng đồng được duyệt', 10, 1, 1),
    ('FAVORITES_100', 'Coupon đạt một trăm lượt lưu hợp lệ', 20, 100, 1),
    ('WORKING_10', 'Coupon đạt mười xác nhận dùng được', 5, 10, 1);

  -- users: 6
  INSERT INTO `users` (`id`,`email`,`username`,`phone`,`password_hash`,`full_name`,`avatar_path`,`role`,`status`,`created_at`,`updated_at`,`deleted_at`,`auth_version`) VALUES
    (1, 'admin.demo@couponhub.test', 'admin_demo', '0900000001', '$2y$10$SSURyRwFWll2B2oZCN1iC.Rs.YedxDfRJf3B0ZaibHLygFh8RZuHW', 'Quản trị CouponHub [DEMO]', NULL, 'ADMIN', 'ACTIVE', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, 0),
    (2, 'merchant.shops@couponhub.test', 'merchant_shops', '0900000002', '$2y$10$vQuMDsoZ.SMpLqcQDQ6q8e971oqn.0KKEZUkq8U7w.jRiLviVBW0.', 'Quản lý sàn thương mại [DEMO]', NULL, 'MERCHANT', 'ACTIVE', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, 0),
    (3, 'merchant.services@couponhub.test', 'merchant_services', '0900000003', '$2y$10$h.foGjx8dZU6rpvHJlGvruk6SaQ4P7Q2uTgO2tE8SnF.8NJGwzAQW', 'Quản lý di chuyển và ăn uống [DEMO]', NULL, 'MERCHANT', 'ACTIVE', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, 0),
    (4, 'merchant.lifestyle@couponhub.test', 'merchant_lifestyle', '0900000004', '$2y$10$WZRqyCoB0JkbrjAdUCE98.v/Go.H8XEama5wxmybWpttmTAxrxK4u', 'Quản lý du lịch và phong cách [DEMO]', NULL, 'MERCHANT', 'ACTIVE', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, 0),
    (5, 'user.demo@couponhub.test', 'user_demo', '0900000005', '$2y$10$epLn3b3nrWTKiGH3.mXh4OtVhBtwKcpkhnkl5v0UOyZpo7OerA8FO', 'Người dùng trải nghiệm [DEMO]', NULL, 'USER', 'ACTIVE', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, 0),
    (6, 'user.community@couponhub.test', 'user_community', '0900000006', '$2y$10$xpaw8StZFXz3DTDdfaJ5ouN25U37x2QoEbeE9yO4nB4XSQfV4tX8e', 'Thành viên cộng đồng [DEMO]', NULL, 'USER', 'ACTIVE', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, 0);

  -- merchants: 12
  INSERT INTO `merchants` (`id`,`owner_user_id`,`name`,`slug`,`description`,`website_url`,`logo_path`,`status`,`reviewed_by`,`reviewed_at`,`review_note`,`created_at`,`updated_at`,`deleted_at`) VALUES
    (1, 2, 'Shopee', 'demo-shopee', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu Shopee.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (2, 2, 'Lazada', 'demo-lazada', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu Lazada.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (3, 3, 'Grab', 'demo-grab', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu Grab.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (4, 3, 'be', 'demo-be', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu be.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (5, 2, 'Tiki', 'demo-tiki', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu Tiki.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (6, 3, 'ShopeeFood', 'demo-shopeefood', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu ShopeeFood.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (7, 4, 'Traveloka', 'demo-traveloka', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu Traveloka.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (8, 4, 'CGV', 'demo-cgv', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu CGV.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (9, 4, 'Hasaki', 'demo-hasaki', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu Hasaki.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (10, 4, 'FPT Shop', 'demo-fpt-shop', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu FPT Shop.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (11, 2, 'Coolmate', 'demo-coolmate', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu Coolmate.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (12, 3, 'Highlands Coffee', 'demo-highlands', '[DEMO] Shop mô phỏng để kiểm thử CouponHub; không đại diện thương hiệu Highlands Coffee.', NULL, NULL, 'APPROVED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL);

  -- merchant_memberships: 12
  INSERT INTO `merchant_memberships` (`user_id`,`merchant_id`,`status`,`granted_by`,`granted_at`,`revoked_by`,`revoked_at`,`revoke_note`,`created_at`,`updated_at`) VALUES
    (2, 1, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (2, 2, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (2, 5, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (2, 11, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (3, 3, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (3, 4, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (3, 6, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (3, 12, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (4, 7, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (4, 8, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (4, 9, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (4, 10, 'ACTIVE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND));

  -- merchant_requests: 12
  INSERT INTO `merchant_requests` (`id`,`user_id`,`merchant_id`,`request_type`,`status`,`message`,`evidence_reference`,`reviewed_by`,`reviewed_at`,`review_note`,`created_at`,`updated_at`) VALUES
    (1, 2, 1, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-SHOPEE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (2, 2, 2, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-LAZADA', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (3, 3, 3, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-GRAB', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (4, 3, 4, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-BE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (5, 2, 5, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-TIKI', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (6, 3, 6, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-SHOPEEFOOD', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (7, 4, 7, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-TRAVELOKA', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (8, 4, 8, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-CGV', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (9, 4, 9, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-HASAKI', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (10, 4, 10, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-FPT-SHOP', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (11, 2, 11, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-COOLMATE', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (12, 3, 12, 'CREATE', 'APPROVED', NULL, 'DEMO-LOCAL-HIGHLANDS', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Xác minh tài khoản quản lý shop trong bộ thử nghiệm local.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND));

  -- campaigns: 2
  INSERT INTO `campaigns` (`id`,`owner_merchant_id`,`created_by`,`name`,`slug`,`description`,`banner_path`,`starts_at`,`ends_at`,`status`,`created_at`,`updated_at`) VALUES
    (1, NULL, 1, 'Tuần ưu đãi đa nền tảng [DEMO]', 'demo-couponhub-week', '[DEMO] Chiến dịch mẫu, các ưu đãi không áp dụng ngoài hệ thống thử nghiệm.', NULL, DATE_ADD(demo_anchor, INTERVAL -86838 SECOND), DATE_ADD(demo_anchor, INTERVAL 2591562 SECOND), 'PUBLISHED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (2, 1, 2, 'Ưu đãi Shopee chính thức [DEMO]', 'demo-shopee-official', '[DEMO] Chiến dịch mẫu, các ưu đãi không áp dụng ngoài hệ thống thử nghiệm.', NULL, DATE_ADD(demo_anchor, INTERVAL -86838 SECOND), DATE_ADD(demo_anchor, INTERVAL 2591562 SECOND), 'PUBLISHED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND));

  -- coupons: 32
  INSERT INTO `coupons` (`id`,`merchant_id`,`category_id`,`submitted_by`,`source`,`title`,`code`,`description`,`terms`,`discount_type`,`discount_value`,`cashback_unit`,`min_order_amount`,`max_discount_amount`,`currency`,`usage_channel`,`promotion_url`,`qr_image_path`,`image_path`,`starts_at`,`ends_at`,`moderation_status`,`publication_status`,`reviewed_by`,`reviewed_at`,`review_note`,`created_at`,`updated_at`,`deleted_at`) VALUES
    (1, 1, 1, 2, 'OFFICIAL', '[DEMO] Shopee giảm 20%, tối đa 60.000đ', 'DEMO_SHOPEE_20', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Shopee. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 200.000đ. Ưu đãi tối đa 60.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 20.00, NULL, 200000.00, 60000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 302540 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (2, 1, 1, 2, 'OFFICIAL', '[DEMO] Shopee giảm 30.000đ cho đơn từ 150.000đ', 'DEMO_SHOPEE_30K', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Shopee. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 150.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FIXED_AMOUNT', 30000.00, NULL, 150000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (3, 2, 1, 2, 'OFFICIAL', '[DEMO] Lazada giảm 50.000đ cho đơn từ 300.000đ', 'DEMO_LAZADA_50K', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Lazada. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 300.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FIXED_AMOUNT', 50000.00, NULL, 300000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -438 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND), DATE_ADD(demo_anchor, INTERVAL -438 SECOND), NULL),
    (4, 2, 1, 2, 'OFFICIAL', '[DEMO] Lazada miễn phí vận chuyển từ 99.000đ', 'DEMO_LAZADA_SHIP', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Lazada. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 99.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FREE_SHIPPING', NULL, NULL, 99000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (5, 3, 7, 3, 'OFFICIAL', '[DEMO] Grab giảm 25%, tối đa 30.000đ', 'DEMO_GRAB_25', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Grab. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 50.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 25.00, NULL, 50000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 475340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (6, 3, 7, 3, 'OFFICIAL', '[DEMO] Grab giảm 20.000đ cho chuyến từ 80.000đ', 'DEMO_GRAB_20K', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Grab. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 80.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FIXED_AMOUNT', 20000.00, NULL, 80000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (7, 4, 7, 3, 'OFFICIAL', '[DEMO] be giảm 15.000đ cho chuyến từ 50.000đ', 'DEMO_BE_15K', 'Dữ liệu thử nghiệm CouponHub theo nền tảng be. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 50.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FIXED_AMOUNT', 15000.00, NULL, 50000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (8, 4, 7, 3, 'OFFICIAL', '[DEMO] be giảm 20%, tối đa 25.000đ', 'DEMO_BE_20', 'Dữ liệu thử nghiệm CouponHub theo nền tảng be. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 60.000đ. Ưu đãi tối đa 25.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 20.00, NULL, 60000.00, 25000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (9, 5, 1, 2, 'OFFICIAL', '[DEMO] Tiki giảm 40.000đ cho đơn từ 250.000đ', 'DEMO_TIKI_40K', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Tiki. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 250.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FIXED_AMOUNT', 40000.00, NULL, 250000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (10, 5, 1, 2, 'OFFICIAL', '[DEMO] Tiki giảm 15%, tối đa 100.000đ', 'DEMO_TIKI_15', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Tiki. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 500.000đ. Ưu đãi tối đa 100.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 15.00, NULL, 500000.00, 100000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (11, 6, 2, 3, 'OFFICIAL', '[DEMO] ShopeeFood giảm 30%, tối đa 40.000đ', 'DEMO_FOOD_30', 'Dữ liệu thử nghiệm CouponHub theo nền tảng ShopeeFood. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 40.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 30.00, NULL, 100000.00, 40000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (12, 6, 2, 3, 'OFFICIAL', '[DEMO] ShopeeFood miễn phí vận chuyển từ 70.000đ', 'DEMO_FOOD_SHIP', 'Dữ liệu thử nghiệm CouponHub theo nền tảng ShopeeFood. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 70.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FREE_SHIPPING', NULL, NULL, 70000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (13, 7, 3, 4, 'OFFICIAL', '[DEMO] Traveloka giảm 10%, tối đa 200.000đ', 'DEMO_TRAVEL_10', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Traveloka. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 1.000.000đ. Ưu đãi tối đa 200.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 1000000.00, 200000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (14, 7, 3, 4, 'OFFICIAL', '[DEMO] Traveloka giảm 150.000đ cho đặt phòng từ 1,5 triệu', 'DEMO_TRAVEL_150K', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Traveloka. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 1.500.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FIXED_AMOUNT', 150000.00, NULL, 1500000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (15, 8, 5, 4, 'OFFICIAL', '[DEMO] CGV giảm 25.000đ cho giao dịch từ 100.000đ', 'DEMO_CGV_25K', 'Dữ liệu thử nghiệm CouponHub theo nền tảng CGV. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FIXED_AMOUNT', 25000.00, NULL, 100000.00, NULL, 'VND', 'BOTH', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (16, 8, 5, 4, 'OFFICIAL', '[DEMO] CGV mua hai vé, tặng một phần bắp mẫu', 'DEMO_CGV_COMBO', 'Dữ liệu thử nghiệm CouponHub theo nền tảng CGV. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 120.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'BUY_X_GET_Y', NULL, NULL, 120000.00, NULL, 'VND', 'OFFLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (17, 9, 6, 4, 'OFFICIAL', '[DEMO] Hasaki giảm 15%, tối đa 80.000đ', 'DEMO_HASAKI_15', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Hasaki. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 300.000đ. Ưu đãi tối đa 80.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 15.00, NULL, 300000.00, 80000.00, 'VND', 'BOTH', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (18, 9, 6, 4, 'OFFICIAL', '[DEMO] Hasaki tặng bộ quà mẫu cho đơn từ 500.000đ', 'DEMO_HASAKI_GIFT', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Hasaki. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 500.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'GIFT', NULL, NULL, 500000.00, NULL, 'VND', 'BOTH', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (19, 10, 4, 4, 'OFFICIAL', '[DEMO] FPT Shop giảm 5%, tối đa 300.000đ', 'DEMO_FPT_5', 'Dữ liệu thử nghiệm CouponHub theo nền tảng FPT Shop. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 3.000.000đ. Ưu đãi tối đa 300.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 5.00, NULL, 3000000.00, 300000.00, 'VND', 'BOTH', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (20, 10, 4, 4, 'OFFICIAL', '[DEMO] FPT Shop hoàn 3%, tối đa 150.000đ', 'DEMO_FPT_CASHBACK', 'Dữ liệu thử nghiệm CouponHub theo nền tảng FPT Shop. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 1.000.000đ. Ưu đãi tối đa 150.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'CASHBACK', 3.00, 'PERCENTAGE', 1000000.00, 150000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (21, 11, 8, 2, 'OFFICIAL', '[DEMO] Coolmate giảm 20%, tối đa 100.000đ', 'DEMO_COOLMATE_20', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Coolmate. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 300.000đ. Ưu đãi tối đa 100.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 20.00, NULL, 300000.00, 100000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (22, 11, 8, 2, 'OFFICIAL', '[DEMO] Coolmate giảm 40.000đ cho đơn từ 250.000đ', 'DEMO_COOLMATE_40K', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Coolmate. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 250.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'FIXED_AMOUNT', 40000.00, NULL, 250000.00, NULL, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (23, 12, 2, 3, 'OFFICIAL', '[DEMO] Highlands giảm 15%, tối đa 20.000đ', 'DEMO_HIGHLANDS_15', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Highlands Coffee. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 80.000đ. Ưu đãi tối đa 20.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 15.00, NULL, 80000.00, 20000.00, 'VND', 'OFFLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (24, 12, 2, 3, 'OFFICIAL', '[DEMO] Highlands mua hai, tặng một đồ uống mẫu', 'DEMO_HIGHLANDS_2GET1', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Highlands Coffee. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'BUY_X_GET_Y', NULL, NULL, 100000.00, NULL, 'VND', 'BOTH', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (25, 1, 1, 5, 'COMMUNITY', '[DEMO] Cộng đồng chia sẻ mã Shopee giảm 10%', 'DEMO_COMMUNITY_SHOPEE', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Shopee. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 100000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (26, 2, 1, 6, 'COMMUNITY', '[DEMO] Cộng đồng chia sẻ mã Lazada giảm 10%', 'DEMO_COMMUNITY_LAZADA', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Lazada. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 100000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (27, 1, 1, 2, 'OFFICIAL', '[DEMO] Shopee giảm 10% đang chờ Admin duyệt', 'DEMO_SHOPEE_PENDING', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Shopee. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 100000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'PENDING', 'ENABLED', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (28, 3, 7, 3, 'OFFICIAL', '[DEMO] Grab giảm 10% đang chờ Admin duyệt', 'DEMO_GRAB_PENDING', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Grab. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 100000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'PENDING', 'ENABLED', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (29, 4, 7, 3, 'OFFICIAL', '[DEMO] be giảm 10% đã bị từ chối để kiểm thử', 'DEMO_BE_REJECTED', 'Dữ liệu thử nghiệm CouponHub theo nền tảng be. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 100000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'REJECTED', 'DISABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Điều kiện chưa rõ ràng, cần bổ sung trước khi duyệt.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (30, 7, 3, 4, 'OFFICIAL', '[DEMO] Traveloka giảm 10% có hiệu lực sau bảy ngày', 'DEMO_TRAVEL_SCHEDULED', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Traveloka. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 100000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL 604340 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (31, 8, 5, 4, 'OFFICIAL', '[DEMO] CGV giảm 10% đã hết hạn để kiểm thử', 'DEMO_CGV_EXPIRED', 'Dữ liệu thử nghiệm CouponHub theo nền tảng CGV. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 100000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -2592438 SECOND), DATE_ADD(demo_anchor, INTERVAL -259638 SECOND), 'APPROVED', 'ENABLED', 1, DATE_ADD(demo_anchor, INTERVAL -2938038 SECOND), '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -3024438 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL),
    (32, 9, 6, 4, 'OFFICIAL', '[DEMO] Hasaki giảm 10% đang tạm tắt', 'DEMO_HASAKI_DISABLED', 'Dữ liệu thử nghiệm CouponHub theo nền tảng Hasaki. Mã minh họa, không dùng để thanh toán thật.', '[DEMO] Chỉ dùng trong bản chạy thử CouponHub, không phải chương trình của thương hiệu. Đơn tối thiểu 100.000đ. Ưu đãi tối đa 30.000đ. Không quy đổi thành tiền mặt; điều kiện minh họa theo loại ưu đãi và kênh sử dụng đã chọn.', 'PERCENTAGE', 10.00, NULL, 100000.00, 30000.00, 'VND', 'ONLINE', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -86860 SECOND), DATE_ADD(demo_anchor, INTERVAL 7819340 SECOND), 'APPROVED', 'DISABLED', 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), '[DEMO] Shop tạm ngưng phát hành mã.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), NULL);

  -- coupon_events: 12
  INSERT INTO `coupon_events` (`id`,`coupon_id`,`user_id`,`event_type`,`request_key`,`occurred_at`) VALUES
    (1, 1, 5, 'FAVORITE_ADD', X'31663634653530362d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (2, 1, 5, 'VOTE', X'31663636313663352d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (3, 3, 5, 'FAVORITE_ADD', X'31663636663364392d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (4, 3, 5, 'VOTE', X'31663638383763642d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (5, 5, 5, 'FAVORITE_ADD', X'31663639366633322d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (6, 5, 5, 'VOTE', X'31663661346338642d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (7, 1, 6, 'FAVORITE_ADD', X'31663665353836322d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (8, 1, 6, 'VOTE', X'31663666356338612d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (9, 3, 6, 'FAVORITE_ADD', X'31663730356363662d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (10, 3, 6, 'VOTE', X'31663731626264662d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (11, 5, 6, 'FAVORITE_ADD', X'31663732613164612d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (12, 5, 6, 'VOTE', X'31663733383761642d633965362d313166312d393763362d653866623163346566616161', DATE_ADD(demo_anchor, INTERVAL -437 SECOND));

  -- coupon_status_history: 31
  INSERT INTO `coupon_status_history` (`id`,`coupon_id`,`actor_id`,`old_moderation`,`new_moderation`,`old_publication`,`new_publication`,`note`,`created_at`) VALUES
    (1, 1, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (2, 2, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (3, 3, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (4, 4, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (5, 5, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (6, 6, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (7, 7, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (8, 8, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (9, 9, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (10, 10, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (11, 11, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (12, 12, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (13, 13, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (14, 14, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (15, 15, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (16, 16, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (17, 17, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (18, 18, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (19, 19, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (20, 20, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (21, 21, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (22, 22, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (23, 23, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (24, 24, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (25, 25, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (26, 26, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (27, 29, 1, 'PENDING', 'REJECTED', 'ENABLED', 'DISABLED', '[DEMO] Điều kiện chưa rõ ràng, cần bổ sung trước khi duyệt.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (28, 30, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (29, 31, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -2938038 SECOND)),
    (30, 32, 1, 'PENDING', 'APPROVED', 'ENABLED', 'ENABLED', '[DEMO] Đã kiểm tra thông tin trong bộ thử nghiệm.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (31, 32, 1, 'APPROVED', 'APPROVED', 'ENABLED', 'DISABLED', '[DEMO] Shop tạm ngưng phát hành mã.', DATE_ADD(demo_anchor, INTERVAL -437 SECOND));

  -- favorites: 6
  INSERT INTO `favorites` (`user_id`,`coupon_id`,`created_at`,`is_eligible_for_rewards`) VALUES
    (5, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (5, 3, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (5, 5, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (6, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (6, 3, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (6, 5, DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1);

  -- point_transactions: 2
  INSERT INTO `point_transactions` (`id`,`user_id`,`coupon_id`,`rule_code`,`points`,`note`,`created_at`) VALUES
    (1, 5, 25, 'COUPON_APPROVED', 10, 'Coupon cộng đồng được duyệt', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (2, 6, 26, 'COUPON_APPROVED', 10, 'Coupon cộng đồng được duyệt', DATE_ADD(demo_anchor, INTERVAL -437 SECOND));

  -- reports: 2
  INSERT INTO `reports` (`id`,`coupon_id`,`reported_by`,`reason`,`details`,`status`,`resolved_by`,`resolved_at`,`resolution_note`,`created_at`) VALUES
    (1, 7, 5, 'WRONG_TERMS', '[DEMO] Báo cáo mẫu để Admin kiểm tra quy trình phản hồi điều kiện coupon.', 'OPEN', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (2, 11, 6, 'WRONG_TERMS', '[DEMO] Báo cáo mẫu để Admin kiểm tra quy trình phản hồi điều kiện coupon.', 'OPEN', NULL, NULL, NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND));

  -- votes: 6
  INSERT INTO `votes` (`user_id`,`coupon_id`,`vote_type`,`created_at`,`updated_at`,`is_eligible_for_trust`) VALUES
    (5, 1, 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (5, 3, 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (5, 5, 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (6, 1, 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (6, 3, 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1),
    (6, 5, 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND), 1);

  -- activity_logs: 81
  INSERT INTO `activity_logs` (`id`,`user_id`,`coupon_id`,`action`,`description`,`created_at`) VALUES
    (1, 2, 1, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (2, 1, 1, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (3, 2, 2, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (4, 1, 2, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (5, 2, 3, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (6, 1, 3, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -438 SECOND)),
    (7, 2, 4, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (8, 1, 4, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (9, 3, 5, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (10, 1, 5, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (11, 3, 6, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (12, 1, 6, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (13, 3, 7, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (14, 1, 7, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (15, 3, 8, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (16, 1, 8, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (17, 2, 9, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (18, 1, 9, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (19, 2, 10, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (20, 1, 10, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (21, 3, 11, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (22, 1, 11, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (23, 3, 12, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (24, 1, 12, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (25, 4, 13, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (26, 1, 13, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (27, 4, 14, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (28, 1, 14, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (29, 4, 15, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (30, 1, 15, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (31, 4, 16, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (32, 1, 16, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (33, 4, 17, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (34, 1, 17, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (35, 4, 18, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (36, 1, 18, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (37, 4, 19, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (38, 1, 19, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (39, 4, 20, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (40, 1, 20, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (41, 2, 21, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (42, 1, 21, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (43, 2, 22, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (44, 1, 22, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (45, 3, 23, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (46, 1, 23, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (47, 3, 24, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (48, 1, 24, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (49, 5, 25, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (50, 1, 25, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (51, 6, 26, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (52, 1, 26, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (53, 2, 27, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (54, 3, 28, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (55, 3, 29, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (56, 1, 29, 'COUPON_MODERATE', 'REJECTED/DISABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (57, 4, 30, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (58, 1, 30, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (59, 4, 31, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -3024438 SECOND)),
    (60, 1, 31, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -2938038 SECOND)),
    (61, 4, 32, 'COUPON_SUBMIT', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (62, 1, 32, 'COUPON_MODERATE', 'APPROVED/ENABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (63, 1, 32, 'COUPON_MODERATE', 'APPROVED/DISABLED', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (64, 5, 1, 'FAVORITE_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (65, 5, 1, 'VOTE_SET', 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (66, 5, 3, 'FAVORITE_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (67, 5, 3, 'VOTE_SET', 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (68, 5, 5, 'FAVORITE_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (69, 5, 5, 'VOTE_SET', 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (70, 5, 1, 'COMMENT_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (71, 5, 3, 'COMMENT_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (72, 5, 7, 'REPORT_CREATE', 'WRONG_TERMS', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (73, 6, 1, 'FAVORITE_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (74, 6, 1, 'VOTE_SET', 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (75, 6, 3, 'FAVORITE_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (76, 6, 3, 'VOTE_SET', 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (77, 6, 5, 'FAVORITE_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (78, 6, 5, 'VOTE_SET', 'WORKING', DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (79, 6, 1, 'COMMENT_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (80, 6, 3, 'COMMENT_ADD', NULL, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (81, 6, 11, 'REPORT_CREATE', 'WRONG_TERMS', DATE_ADD(demo_anchor, INTERVAL -437 SECOND));

  -- campaign_coupons: 10
  INSERT INTO `campaign_coupons` (`campaign_id`,`coupon_id`,`added_by`,`added_at`) VALUES
    (1, 1, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (1, 2, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (1, 3, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (1, 4, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (1, 5, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (1, 6, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (1, 7, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (1, 8, 1, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (2, 1, 2, DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (2, 2, 2, DATE_ADD(demo_anchor, INTERVAL -437 SECOND));

  -- comments: 4
  INSERT INTO `comments` (`id`,`coupon_id`,`user_id`,`body`,`status`,`created_at`,`updated_at`) VALUES
    (1, 1, 5, '[DEMO] Bình luận mẫu: đã kiểm tra đơn tối thiểu và mức giảm tối đa trong bản chạy thử.', 'VISIBLE', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (2, 3, 5, '[DEMO] Bình luận mẫu: đã kiểm tra đơn tối thiểu và mức giảm tối đa trong bản chạy thử.', 'VISIBLE', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (3, 1, 6, '[DEMO] Bình luận mẫu: đã kiểm tra đơn tối thiểu và mức giảm tối đa trong bản chạy thử.', 'VISIBLE', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND)),
    (4, 3, 6, '[DEMO] Bình luận mẫu: đã kiểm tra đơn tối thiểu và mức giảm tối đa trong bản chạy thử.', 'VISIBLE', DATE_ADD(demo_anchor, INTERVAL -437 SECOND), DATE_ADD(demo_anchor, INTERVAL -437 SECOND));

  COMMIT;
END$$
CALL couponhub_install_demo()$$
DROP PROCEDURE couponhub_install_demo$$
DELIMITER ;

SET SESSION sql_mode = @couponhub_demo_previous_sql_mode;

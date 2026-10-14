-- CouponHub Phase 6: 7 Community Coupons gia lap do Tran Thi B dang.
-- Chi chay tren CSDL PHAT TRIEN / KIEM THU (khong phai ma giam gia that).
-- Import vao database couponhub tren phpMyAdmin. KHONG DROP / TRUNCATE bang nao.
-- Idempotent: nhap lai khong tao trung ma PH6B_*.
-- Tra cuu User theo email, Merchant theo ten, Category theo slug (khong hardcode id).
-- Dung quy trinh PENDING -> Admin APPROVED de giu dung logic CouponPoint.
-- Sau khi Admin duyet, dung 1 tai khoan de LUU it nhat 10 coupon moi co trang 2 (/saved-coupons, 9/trang).
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
SET time_zone = '+00:00';

-- Kiem tra du lieu can thiet truoc khi seed:
SELECT id, email, full_name FROM users WHERE email='tranthib@couponhub.com';
SELECT id, name, status FROM merchants WHERE name IN ('Shopee','Lazada','KFC','Highlands Coffee','Phúc Long','CGV') ORDER BY name;
SELECT id, name, slug, is_active FROM categories WHERE slug IN ('mua-sam','an-uong','giai-tri');

START TRANSACTION;
-- Coupon 1: Shopee, PH6B_SHP15
INSERT INTO coupons (
 merchant_id, category_id, submitted_by, source, title, code,
 description, terms, discount_type, discount_value, cashback_unit,
 min_order_amount, max_discount_amount, usage_channel, promotion_url,
 starts_at, ends_at, moderation_status, publication_status
)
SELECT m.id, cat.id, u.id, 'COMMUNITY', '[TEST P6] Giảm 15% đơn Shopee từ 200.000đ', 'PH6B_SHP15',
 'Coupon giả lập giảm 15% dành cho kiểm thử hệ thống CouponHub Phase 6.', 'Áp dụng cho đơn từ 200.000đ. Giảm tối đa 45.000đ. Chỉ dùng trong môi trường kiểm thử; không phải mã khuyến mãi thật.', 'PERCENTAGE', 15, NULL,
 200000, 45000, 'ONLINE', NULL,
 '2026-10-13 01:00:00', '2026-11-15 16:59:00', 'PENDING', 'ENABLED'
FROM users u
CROSS JOIN merchants m
CROSS JOIN categories cat
WHERE u.email = 'tranthib@couponhub.com' AND u.status = 'ACTIVE' AND u.deleted_at IS NULL
  AND m.name = 'Shopee' AND m.deleted_at IS NULL
  AND m.status NOT IN ('LOCKED', 'REJECTED')
  AND cat.slug = 'mua-sam' AND cat.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM coupons c WHERE c.code_normalized = 'PH6B_SHP15' AND c.deleted_at IS NULL);

-- Coupon 2: Lazada, PH6B_LZD40
INSERT INTO coupons (
 merchant_id, category_id, submitted_by, source, title, code,
 description, terms, discount_type, discount_value, cashback_unit,
 min_order_amount, max_discount_amount, usage_channel, promotion_url,
 starts_at, ends_at, moderation_status, publication_status
)
SELECT m.id, cat.id, u.id, 'COMMUNITY', '[TEST P6] Giảm 40.000đ đơn Lazada từ 250.000đ', 'PH6B_LZD40',
 'Coupon giả lập giảm trực tiếp 40.000đ dành cho kiểm thử tìm kiếm và lưu coupon.', 'Đơn hàng tối thiểu 250.000đ. Giảm cố định 40.000đ. Dữ liệu nội bộ dùng để kiểm thử, không áp dụng khi mua hàng thật.', 'FIXED_AMOUNT', 40000, NULL,
 250000, NULL, 'ONLINE', NULL,
 '2026-10-13 01:00:00', '2026-11-22 16:59:00', 'PENDING', 'ENABLED'
FROM users u
CROSS JOIN merchants m
CROSS JOIN categories cat
WHERE u.email = 'tranthib@couponhub.com' AND u.status = 'ACTIVE' AND u.deleted_at IS NULL
  AND m.name = 'Lazada' AND m.deleted_at IS NULL
  AND m.status NOT IN ('LOCKED', 'REJECTED')
  AND cat.slug = 'mua-sam' AND cat.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM coupons c WHERE c.code_normalized = 'PH6B_LZD40' AND c.deleted_at IS NULL);

-- Coupon 3: KFC, PH6B_KFC25
INSERT INTO coupons (
 merchant_id, category_id, submitted_by, source, title, code,
 description, terms, discount_type, discount_value, cashback_unit,
 min_order_amount, max_discount_amount, usage_channel, promotion_url,
 starts_at, ends_at, moderation_status, publication_status
)
SELECT m.id, cat.id, u.id, 'COMMUNITY', '[TEST P6] Giảm 25.000đ KFC từ 150.000đ', 'PH6B_KFC25',
 'Ưu đãi giả lập cho đơn ăn uống KFC; dùng kiểm thử bộ lọc danh mục và Merchant.', 'Hóa đơn từ 150.000đ. Giảm cố định 25.000đ. Có thể dùng thử cả online và tại cửa hàng trong hệ thống, mã không có giá trị thanh toán thực.', 'FIXED_AMOUNT', 25000, NULL,
 150000, NULL, 'BOTH', NULL,
 '2026-10-13 01:00:00', '2026-10-30 16:59:00', 'PENDING', 'ENABLED'
FROM users u
CROSS JOIN merchants m
CROSS JOIN categories cat
WHERE u.email = 'tranthib@couponhub.com' AND u.status = 'ACTIVE' AND u.deleted_at IS NULL
  AND m.name = 'KFC' AND m.deleted_at IS NULL
  AND m.status NOT IN ('LOCKED', 'REJECTED')
  AND cat.slug = 'an-uong' AND cat.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM coupons c WHERE c.code_normalized = 'PH6B_KFC25' AND c.deleted_at IS NULL);

-- Coupon 4: Highlands Coffee, PH6B_HLC10
INSERT INTO coupons (
 merchant_id, category_id, submitted_by, source, title, code,
 description, terms, discount_type, discount_value, cashback_unit,
 min_order_amount, max_discount_amount, usage_channel, promotion_url,
 starts_at, ends_at, moderation_status, publication_status
)
SELECT m.id, cat.id, u.id, 'COMMUNITY', '[TEST P6] Giảm 10% Highlands Coffee từ 90.000đ', 'PH6B_HLC10',
 'Coupon giả lập có hạn dùng ngắn để thử tính năng sắp hết hạn.', 'Hóa đơn từ 90.000đ. Giảm 10%, tối đa 20.000đ. Chỉ sử dụng để kiểm thử tính năng lọc thời hạn.', 'PERCENTAGE', 10, NULL,
 90000, 20000, 'OFFLINE', NULL,
 '2026-10-13 01:00:00', '2026-10-16 16:59:00', 'PENDING', 'ENABLED'
FROM users u
CROSS JOIN merchants m
CROSS JOIN categories cat
WHERE u.email = 'tranthib@couponhub.com' AND u.status = 'ACTIVE' AND u.deleted_at IS NULL
  AND m.name = 'Highlands Coffee' AND m.deleted_at IS NULL
  AND m.status NOT IN ('LOCKED', 'REJECTED')
  AND cat.slug = 'an-uong' AND cat.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM coupons c WHERE c.code_normalized = 'PH6B_HLC10' AND c.deleted_at IS NULL);

-- Coupon 5: Phúc Long, PH6B_PLGIFT
INSERT INTO coupons (
 merchant_id, category_id, submitted_by, source, title, code,
 description, terms, discount_type, discount_value, cashback_unit,
 min_order_amount, max_discount_amount, usage_channel, promotion_url,
 starts_at, ends_at, moderation_status, publication_status
)
SELECT m.id, cat.id, u.id, 'COMMUNITY', '[TEST P6] Tặng topping cho đơn Phúc Long từ 120.000đ', 'PH6B_PLGIFT',
 'Coupon giả lập nhận quà tặng, dùng kiểm thử loại giảm GIFT.', 'Hóa đơn đồ uống từ 120.000đ được tặng 1 topping thử nghiệm. Không có chương trình thật tại cửa hàng.', 'GIFT', NULL, NULL,
 120000, NULL, 'OFFLINE', NULL,
 '2026-10-13 01:00:00', '2026-11-09 16:59:00', 'PENDING', 'ENABLED'
FROM users u
CROSS JOIN merchants m
CROSS JOIN categories cat
WHERE u.email = 'tranthib@couponhub.com' AND u.status = 'ACTIVE' AND u.deleted_at IS NULL
  AND m.name = 'Phúc Long' AND m.deleted_at IS NULL
  AND m.status NOT IN ('LOCKED', 'REJECTED')
  AND cat.slug = 'an-uong' AND cat.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM coupons c WHERE c.code_normalized = 'PH6B_PLGIFT' AND c.deleted_at IS NULL);

-- Coupon 6: CGV, PH6B_CGV20
INSERT INTO coupons (
 merchant_id, category_id, submitted_by, source, title, code,
 description, terms, discount_type, discount_value, cashback_unit,
 min_order_amount, max_discount_amount, usage_channel, promotion_url,
 starts_at, ends_at, moderation_status, publication_status
)
SELECT m.id, cat.id, u.id, 'COMMUNITY', '[TEST P6] Giảm 20% vé CGV từ 120.000đ', 'PH6B_CGV20',
 'Coupon giả lập cho danh mục Giải trí, kiểm thử tìm kiếm và bộ lọc phần trăm.', 'Đơn mua vé từ 120.000đ. Giảm 20%, tối đa 40.000đ. Không dùng thanh toán tại rạp.', 'PERCENTAGE', 20, NULL,
 120000, 40000, 'ONLINE', NULL,
 '2026-10-13 01:00:00', '2026-11-10 16:59:00', 'PENDING', 'ENABLED'
FROM users u
CROSS JOIN merchants m
CROSS JOIN categories cat
WHERE u.email = 'tranthib@couponhub.com' AND u.status = 'ACTIVE' AND u.deleted_at IS NULL
  AND m.name = 'CGV' AND m.deleted_at IS NULL
  AND m.status NOT IN ('LOCKED', 'REJECTED')
  AND cat.slug = 'giai-tri' AND cat.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM coupons c WHERE c.code_normalized = 'PH6B_CGV20' AND c.deleted_at IS NULL);

-- Coupon 7: Shopee, PH6B_SHPFS
INSERT INTO coupons (
 merchant_id, category_id, submitted_by, source, title, code,
 description, terms, discount_type, discount_value, cashback_unit,
 min_order_amount, max_discount_amount, usage_channel, promotion_url,
 starts_at, ends_at, moderation_status, publication_status
)
SELECT m.id, cat.id, u.id, 'COMMUNITY', '[TEST P6] Miễn phí vận chuyển Shopee từ 99.000đ', 'PH6B_SHPFS',
 'Coupon giả lập miễn phí vận chuyển, kiểm thử loại FREE_SHIPPING.', 'Giá trị đơn hàng từ 99.000đ. Miễn phí vận chuyển chỉ là dữ liệu thử nghiệm, không áp dụng trên Shopee.', 'FREE_SHIPPING', NULL, NULL,
 99000, NULL, 'ONLINE', NULL,
 '2026-10-13 01:00:00', '2026-11-12 16:59:00', 'PENDING', 'ENABLED'
FROM users u
CROSS JOIN merchants m
CROSS JOIN categories cat
WHERE u.email = 'tranthib@couponhub.com' AND u.status = 'ACTIVE' AND u.deleted_at IS NULL
  AND m.name = 'Shopee' AND m.deleted_at IS NULL
  AND m.status NOT IN ('LOCKED', 'REJECTED')
  AND cat.slug = 'mua-sam' AND cat.is_active = 1
  AND NOT EXISTS (SELECT 1 FROM coupons c WHERE c.code_normalized = 'PH6B_SHPFS' AND c.deleted_at IS NULL);

-- Ghi nhan log mot lan cho tung coupon test da tao.
INSERT INTO activity_logs(user_id,coupon_id,action,description)
SELECT c.submitted_by, c.id, 'COUPON_SUBMIT', 'Phase 6 test fixture: user-submitted Community Coupon'
FROM coupons c JOIN users u ON u.id=c.submitted_by
WHERE u.email='tranthib@couponhub.com'
  AND c.code IN ('PH6B_SHP15','PH6B_LZD40','PH6B_KFC25','PH6B_HLC10','PH6B_PLGIFT','PH6B_CGV20','PH6B_SHPFS')
  AND NOT EXISTS (SELECT 1 FROM activity_logs al WHERE al.coupon_id=c.id AND al.action='COUPON_SUBMIT');
COMMIT;

-- Ket qua: phai co 7 coupon [TEST P6] do Tran Thi B dang.
SELECT c.id, c.title, c.code, m.name AS merchant_name, c.moderation_status, c.publication_status
FROM coupons c JOIN users u ON u.id=c.submitted_by JOIN merchants m ON m.id=c.merchant_id
WHERE u.email='tranthib@couponhub.com' AND c.code IN ('PH6B_SHP15','PH6B_LZD40','PH6B_KFC25','PH6B_HLC10','PH6B_PLGIFT','PH6B_CGV20','PH6B_SHPFS') ORDER BY c.id;

-- Sau khi Admin duyet, vao giao dien cong khai de luu coupon tu tai khoan kiem thu.
-- Khong chen truc tiep vao favorites: hay dung nut Luu de thu luong xu ly va quy tac qualified_favorites.

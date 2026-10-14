-- CouponHub Phase 5 - CouponPoint audit (READ ONLY).
-- Chon database `couponhub` TRUOC KHI chay. Khong INSERT/UPDATE/DELETE/ALTER.
-- Chay tung phan trong phpMyAdmin; khong can quyen information_schema.

-- 1. Tham so thuong hien tai.
SELECT code, description, points, threshold_count, is_active
FROM point_rules ORDER BY code;

-- 2. Lich su thuong + nguoi nhan.
SELECT pt.id, pt.user_id, u.full_name, pt.coupon_id, pt.rule_code,
       pt.points, pt.created_at
FROM point_transactions pt
JOIN users u ON u.id = pt.user_id
ORDER BY pt.id DESC;

-- 3. Tim giao dich thuong trung (neu khong co, tra ve 0 dong).
-- UNIQUE thuc su duoc thiet ke tren (coupon_id, rule_code).
SELECT coupon_id, rule_code, COUNT(*) AS award_count
FROM point_transactions
GROUP BY coupon_id, rule_code
HAVING COUNT(*) > 1;

-- 4. Kiem tra ranh gioi bao ve phat thuong mot lan.
SHOW INDEX FROM point_transactions;

-- 5. So tuong tac hop le cua tung coupon.
SELECT c.id AS coupon_id, c.title, c.submitted_by,
       c.source, c.moderation_status,
       fs.total_favorites, fs.qualified_favorites,
       vs.working_count AS qualified_working_votes,
       vs.total_votes AS qualified_total_votes
FROM coupons c
JOIN v_coupon_favorite_stats fs ON fs.coupon_id = c.id
JOIN v_coupon_vote_stats vs ON vs.coupon_id = c.id
ORDER BY c.id;

-- 6. Tinh trang cac QUY TAC NGUONG: du dieu kien hay chua, va da thuong hay chua.
-- Chi xet Community Coupon duoc phe duyet. Cac quy tac thuong 1 lan.
SELECT c.id AS coupon_id, c.title, r.code AS rule_code,
       r.threshold_count,
       CASE
         WHEN r.code = 'FAVORITES_100' THEN fs.qualified_favorites
         WHEN r.code = 'WORKING_10' THEN vs.working_count
       END AS current_qualified_count,
       CASE WHEN pt.id IS NULL THEN 'NOT_AWARDED' ELSE 'AWARDED' END AS award_status
FROM coupons c
JOIN point_rules r ON r.code IN ('FAVORITES_100', 'WORKING_10') AND r.is_active = 1
JOIN v_coupon_favorite_stats fs ON fs.coupon_id = c.id
JOIN v_coupon_vote_stats vs ON vs.coupon_id = c.id
LEFT JOIN point_transactions pt ON pt.coupon_id = c.id AND pt.rule_code = r.code
WHERE c.source = 'COMMUNITY' AND c.moderation_status = 'APPROVED'
ORDER BY c.id, r.code;

-- 7. Kiem tra Stored Procedure THUC TE tren database dang chay.
-- So sanh logic voi file source: v_coupon_favorite_stats.qualified_favorites
-- va v_coupon_vote_stats.working_count. Phan nay chi doc.
SHOW CREATE PROCEDURE sp_award_coupon_points;
SHOW CREATE PROCEDURE sp_set_favorite;
SHOW CREATE PROCEDURE sp_set_vote;

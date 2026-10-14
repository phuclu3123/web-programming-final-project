-- CouponHub Phase 6 / Saved Coupons QA (READ-ONLY)
-- Run on selected database couponhub in phpMyAdmin > SQL.
-- These SELECT queries do not alter data, views, routines or permissions.
-- 1. Each account's actual number of saved coupons.
SELECT f.user_id, u.full_name, COUNT(*) AS total_saved
FROM favorites f JOIN users u ON u.id=f.user_id
GROUP BY f.user_id,u.full_name ORDER BY f.user_id;

-- 2. Check every coupon's view totals against live favorites + eligibility rules.
SELECT c.id AS coupon_id,c.title,fs.total_favorites,fs.qualified_favorites,
 (SELECT COUNT(*) FROM favorites f WHERE f.coupon_id=c.id) AS independently_counted_total,
 (SELECT COUNT(*) FROM favorites f
   WHERE f.coupon_id=c.id AND f.is_eligible_for_rewards=1 AND f.user_id<>c.submitted_by
   AND NOT EXISTS (
     SELECT 1 FROM merchant_memberships mm
     WHERE mm.user_id=f.user_id AND mm.merchant_id=c.merchant_id AND mm.status='ACTIVE'
   )) AS independently_counted_qualified
FROM coupons c
JOIN v_coupon_favorite_stats fs ON fs.coupon_id=c.id
ORDER BY c.id;

-- 3. Show saved coupons with their current effective status and per-save eligibility.
-- This query is for a local test DB. It exposes names of users with saved coupons.
SELECT f.user_id,u.full_name,f.coupon_id,c.title,s.effective_status,
 f.is_eligible_for_rewards,f.created_at AS saved_at
FROM favorites f JOIN users u ON u.id=f.user_id
JOIN coupons c ON c.id=f.coupon_id
JOIN v_coupon_status s ON s.id=c.id
ORDER BY f.user_id,f.created_at DESC,f.coupon_id DESC;

-- 4. No duplicate CouponPoint awards per coupon and rule are allowed.
-- Expected: zero rows, independently protected by uq_coupon_award.
SELECT coupon_id,rule_code,COUNT(*) AS duplicate_count
FROM point_transactions
GROUP BY coupon_id,rule_code HAVING COUNT(*)>1;

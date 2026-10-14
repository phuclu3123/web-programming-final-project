-- CouponHub Phase 5 read-only verification after migration; select database couponhub.
SELECT DATABASE() AS db, VERSION() AS version, @@collation_connection AS connection_collation;
SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('merchant_memberships','merchant_requests');
SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='favorites' AND COLUMN_NAME='is_eligible_for_rewards';
SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='votes' AND COLUMN_NAME='is_eligible_for_trust';
SELECT TABLE_NAME FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('v_coupon_favorite_stats','v_coupon_vote_stats');
SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE() AND ROUTINE_NAME IN ('sp_set_favorite','sp_set_vote','sp_submit_coupon','sp_award_coupon_points','sp_search_coupons','sp_attach_campaign_coupon','sp_merchant_stats');
SELECT c.id, c.title, fs.total_favorites,fs.qualified_favorites,s.working_count,s.total_votes,s.trust_score
FROM coupons c JOIN v_coupon_favorite_stats fs ON fs.coupon_id=c.id JOIN v_coupon_vote_stats s ON s.coupon_id=c.id
ORDER BY c.id DESC LIMIT 20;
SELECT mm.user_id,mm.merchant_id,mm.status,m.name FROM merchant_memberships mm JOIN merchants m ON m.id=mm.merchant_id ORDER BY mm.user_id,mm.merchant_id LIMIT 40;

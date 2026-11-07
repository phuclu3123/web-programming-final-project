"""No-database structural checks for the 2026-11-07 consolidated schema.

This does NOT prove that SQL imports or that the PHP app works on MariaDB.
"""
from pathlib import Path
import re
root=Path(__file__).resolve().parents[1]
read=lambda p:(root / p).read_text(encoding='utf-8')
schema=read('database/schema.sql')
seed=read('database/demo.sql')
repo=read('app/repositories/CouponRepository.php')
admin=read('app/repositories/AdminCouponRepository.php')
notification=read('app/repositories/NotificationRepository.php')
tables=re.findall(r'^CREATE TABLE(?: IF NOT EXISTS)? `?([a-z][a-z_]+)`?',schema,re.M)
assert len(tables)==len(set(tables))==21,(len(tables),tables)
assert {'comments','external_offers','oauth_accounts','activity_logs'} <= set(tables)
assert 'coupon_status_history' not in tables
assert 'article_comments' not in tables
assert 'article_id' in schema and 'request_id' in schema
assert len(re.findall(r'^CREATE PROCEDURE `',schema,re.M))==21
assert len(re.findall(r'CREATE\s+VIEW `v_',schema))==6
assert 'DEFINER=`root`@`localhost`' not in schema
assert 'utf8mb4_general_ci' not in schema
assert 'COUPON_STATUS_CHANGE' in schema
assert "enum('github','google','facebook')" in schema
for token in ['coupon_status_history','`currency`','`qr_image_path`','`image_path`']:
    assert token not in seed, token
for token in ['coupon_status_history','qr_image_path','image_path']:
    assert token not in repo and token not in admin and token not in notification,token
assert repo.count('CALL sp_submit_coupon(')==2
assert all(x.count('?')==17 for x in re.findall(r'CALL sp_submit_coupon\([^)]+\)',repo))
assert seed.count("'COUPON_STATUS_CHANGE')")==31
assert len(re.findall(r'^\s+\(\d+, \d+, \d+, \d+, \'(?:OFFICIAL|COMMUNITY)\'',seed,re.M))==32
assert 'FROM activity_logs h JOIN coupons c' in notification
assert 'WHERE c.submitted_by=?' in notification
print('PASS: 21/6/21 objects; 32 coupons; 31 history events; 17 procedure parameters; no legacy table calls (static only).')

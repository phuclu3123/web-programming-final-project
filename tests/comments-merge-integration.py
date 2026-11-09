"""Destructive test of comments migration on an explicitly isolated DB only.

Creates/drops `couponhub_comments_merge_test` on localhost:13379. Never use a
production MariaDB instance. Requires COUPONHUB_RUN_DB_TEST=1.
Usage: COUPONHUB_RUN_DB_TEST=1 python tests/comments-merge-integration.py --mysql C:/xampp/mysql/bin/mysql.exe
"""
import argparse
import os
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--mysql', default='C:/xampp/mysql/bin/mysql.exe')
args = parser.parse_args()
if os.environ.get('COUPONHUB_RUN_DB_TEST') != '1':
    raise SystemExit('SKIP: requires COUPONHUB_RUN_DB_TEST=1 on isolated server port 13379')
mysql = [args.mysql, '--no-defaults', '--host=127.0.0.1', '--port=13379', '--user=root',
         '--batch', '--raw', '--skip-column-names', '--default-character-set=utf8mb4']
name = 'couponhub_comments_merge_test'

def query(sql, selected=True):
    return subprocess.check_output(mysql + ([name] if selected else []) + ['-e', sql]).decode().strip()

def load(relative_path):
    filename = ROOT / relative_path
    proc = subprocess.run(mysql + [name], input=filename.read_bytes(), capture_output=True)
    if proc.returncode:
        raise AssertionError(f'{relative_path}: {proc.stderr.decode(errors="replace")}')

assert query('SELECT @@port', selected=False) == '13379', 'Wrong MariaDB instance; abort'
assert not (ROOT / 'config/local.php').exists(), 'Local DB override; abort'
query(f'DROP DATABASE IF EXISTS {name}; CREATE DATABASE {name} CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci', selected=False)
load('database/schema.sql')
load('database/demo.sql')
assert query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'") == '21'
original_coupon_count = query('SELECT COUNT(*) FROM comments WHERE coupon_id IS NOT NULL')
original_coupon_ids = query('SELECT GROUP_CONCAT(id ORDER BY id) FROM comments WHERE coupon_id IS NOT NULL')

# Recreate the legacy feature table on test DB only, then cause ID collision.
load('database/migrations/20261106_article_comments.sql')
assert query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'") == '22'
query("INSERT INTO articles(author_id,title,slug,body,status,published_at) VALUES(1,'Article for merge fixture','merge-article-test','body','PUBLISHED',UTC_TIMESTAMP())")
article = query("SELECT id FROM articles WHERE slug='merge-article-test'")
key_a = 'a' * 32
key_b = 'b' * 32
query(f"INSERT INTO article_comments(article_id,user_id,body,status,request_id) VALUES({article},5,'safe text','VISIBLE','{key_a}')")
query(f"INSERT INTO article_comments(article_id,user_id,body,status,request_id) VALUES({article},6,'hidden text','HIDDEN','{key_b}')")
query("INSERT INTO activity_logs(user_id,action,description) VALUES(5,'ARTICLE_COMMENT_CREATE','{\"article_comment_id\":1}')")
query("INSERT INTO activity_logs(user_id,action,description) VALUES(1,'ARTICLE_COMMENT_HIDDEN','{\"article_comment_id\":2}')")
load('database/migrations/20261107_unify_comments.sql')
assert query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'") == '21'
assert query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='article_comments'") == '0'
assert query('SELECT COUNT(*) FROM comments WHERE coupon_id IS NOT NULL') == original_coupon_count
assert query('SELECT GROUP_CONCAT(id ORDER BY id) FROM comments WHERE coupon_id IS NOT NULL') == original_coupon_ids
assert query('SELECT COUNT(*) FROM comments WHERE article_id IS NOT NULL') == '2'
new_a = query(f"SELECT id FROM comments WHERE request_id='{key_a}'")
new_b = query(f"SELECT id FROM comments WHERE request_id='{key_b}'")
assert int(new_a) > 4 and int(new_b) > 4, 'Article IDs must not collide with coupon comment IDs'
assert query(f"SELECT status FROM comments WHERE id={new_b}") == 'HIDDEN'
assert query("SELECT JSON_UNQUOTE(JSON_EXTRACT(description,'$.article_comment_id')) FROM activity_logs WHERE action='ARTICLE_COMMENT_CREATE' ORDER BY id DESC LIMIT 1") == new_a
assert query("SELECT JSON_UNQUOTE(JSON_EXTRACT(description,'$.article_comment_id')) FROM activity_logs WHERE action='ARTICLE_COMMENT_HIDDEN' ORDER BY id DESC LIMIT 1") == new_b
assert query("SELECT COUNT(*) FROM activity_logs WHERE action LIKE 'ARTICLE_COMMENT_%' AND JSON_EXTRACT(description,'$.comment_migrated_21') IS NULL") == '0'

# Verify repeatable migration after successful completion (no extra comments).
load('database/migrations/20261107_unify_comments.sql')
assert query('SELECT COUNT(*) FROM comments WHERE article_id IS NOT NULL') == '2'

# Existing destinations during retry must match bytes, not just a CI collation.
for index, (original, different) in enumerate([
    ('Original Case', 'original case'),
    ('Cafe', 'Café'),
    ('Same text', 'Same text '),
]):
    load('database/migrations/20261106_article_comments.sql')
    key = str(index + 1) * 32
    stamp = '2026-11-01 00:00:00'
    quote = lambda value: "CONVERT(0x" + value.encode('utf-8').hex() + " USING utf8mb4)"
    query(f"INSERT INTO article_comments(article_id,user_id,body,status,request_id,created_at,updated_at) VALUES({article},5,{quote(original)},'VISIBLE','{key}','{stamp}','{stamp}')")
    query(f"INSERT INTO comments(article_id,user_id,body,status,request_id,created_at,updated_at) VALUES({article},5,{quote(different)},'VISIBLE','{key}','{stamp}','{stamp}')")
    failed = subprocess.run(mysql + [name], input=(ROOT / 'database/migrations/20261107_unify_comments.sql').read_bytes(), capture_output=True)
    assert failed.returncode != 0 and b'parity failed' in failed.stderr, failed.stderr
    assert query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='article_comments'") == '1'
    assert query('SELECT HEX(body) FROM article_comments LIMIT 1').lower() == original.encode().hex()
    query(f"DELETE FROM comments WHERE user_id=5 AND request_id='{key}'")
    load('database/migrations/20261107_unify_comments.sql')
    assert query(f"SELECT HEX(body) FROM comments WHERE user_id=5 AND request_id='{key}'").lower() == original.encode().hex()

print('PASS isolated MariaDB comments merge: IDs/audit/idempotence; case/accent/trailing-space conflicts preserve the legacy source.', flush=True)

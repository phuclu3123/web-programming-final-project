"""Upgrade the old 23-table schema on an owned, disposable MariaDB port only.

COUPONHUB_RUN_DB_TEST=1 python tests/legacy-core-upgrade.py
Requires full git history containing 27fa6a0; never connects to port 3306.
"""
from pathlib import Path
import hashlib
import os
import subprocess

ROOT = Path(__file__).resolve().parents[1]
DB = 'couponhub_legacy_core_upgrade_test'
MYSQL = ['C:/xampp/mysql/bin/mysql.exe', '--no-defaults', '--host=127.0.0.1',
         '--port=13379', '--user=root', '--batch', '--raw', '--skip-column-names',
         '--default-character-set=utf8mb4']

def query(sql, selected=True, check=True):
    result = subprocess.run(MYSQL + ([DB] if selected else []), input=sql.encode(), capture_output=True)
    if check and result.returncode:
        raise AssertionError(result.stderr.decode(errors='replace'))
    return result

def value(sql):
    return query(sql).stdout.decode().strip()

def load(path):
    result = query((ROOT / path).read_text(encoding='utf-8'), check=False)
    assert not result.returncode, result.stderr.decode(errors='replace')

def reset():
    query(f'DROP DATABASE IF EXISTS {DB}; CREATE DATABASE {DB} CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci', selected=False)
    for name in ['schema.sql', 'demo.sql']:
        data = subprocess.check_output(['git', '-c', 'safe.directory=' + str(ROOT), 'show', '27fa6a0:database/' + name], cwd=ROOT)
        result = subprocess.run(MYSQL + [DB], input=data, capture_output=True)
        assert not result.returncode, result.stderr

def tables():
    return value("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME").splitlines()

def ordering(table):
    columns = value(f"SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}' AND CONSTRAINT_NAME='PRIMARY' ORDER BY ORDINAL_POSITION").splitlines()
    assert columns, 'Missing primary key: ' + table
    return ','.join('`' + col + '`' for col in columns)

def digest(table, columns, where=''):
    fields = ','.join("IFNULL(HEX(`" + col + "`),'NULL')" for col in columns)
    return hashlib.sha256(query(f"SELECT CONCAT_WS(':',{fields}) FROM `{table}` {where} ORDER BY {ordering(table)}").stdout).hexdigest()

if __name__ == '__main__':
    assert os.environ.get('COUPONHUB_RUN_DB_TEST') == '1', 'Explicit isolated DB opt-in required'
    assert b'13379' == query('SELECT @@port', selected=False).stdout.strip()
    assert not (ROOT / 'config/local.php').exists()
    reset()
    assert len(tables()) == 23
    query("INSERT INTO articles(author_id,title,slug,body,status,published_at) VALUES(1,'Upgrade fixture','legacy-upgrade-article','body','PUBLISHED',UTC_TIMESTAMP())")
    article = value("SELECT id FROM articles WHERE slug='legacy-upgrade-article'")
    key = 'c' * 32
    query(f"INSERT INTO article_comments(article_id,user_id,body,status,request_id) VALUES({article},5,'Nội dung giữ nguyên: Tiếng Việt ✓','VISIBLE','{key}')")
    query("INSERT INTO activity_logs(user_id,action,description) VALUES(5,'ARTICLE_COMMENT_CREATE','{\"article_comment_id\":1}')")
    old_body = value('SELECT HEX(body) FROM article_comments WHERE id=1')
    old_ids = value('SELECT GROUP_CONCAT(id ORDER BY id) FROM comments')
    old_history = query('SELECT id,actor_id,coupon_id,HEX(old_moderation),HEX(new_moderation),HEX(old_publication),HEX(new_publication),HEX(note),created_at FROM coupon_status_history ORDER BY id').stdout
    removed = {'category_id', 'cover_path'}
    snapshots = {}
    for table in tables():
        if table in ['activity_logs', 'article_comments', 'coupon_status_history']:
            continue
        columns = value(f"SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}' ORDER BY ORDINAL_POSITION").splitlines()
        if table == 'articles': columns = [c for c in columns if c not in removed]
        if table == 'coupons': columns = [c for c in columns if c not in {'currency', 'image_path', 'qr_image_path'}]
        snapshots[table] = (columns, digest(table, columns))
    load('database/migrations/20261109_legacy_core_compat.sql')
    assert len(tables()) == 22
    assert value("SELECT COUNT(*) FROM information_schema.PARAMETERS WHERE SPECIFIC_SCHEMA=DATABASE() AND SPECIFIC_NAME='sp_submit_coupon'") == '17'
    for table, (columns, before) in snapshots.items():
        assert digest(table, columns) == before, 'Core upgrade changed existing ' + table
    new_history = query("SELECT legacy_history_id,user_id,coupon_id,HEX(old_moderation),HEX(new_moderation),HEX(old_publication),HEX(new_publication),HEX(note),created_at FROM activity_logs WHERE legacy_history_id IS NOT NULL ORDER BY legacy_history_id").stdout
    assert old_history == new_history, 'History fields/timestamps were not preserved byte-for-byte'
    load('database/migrations/20261107_unify_comments.sql')
    assert len(tables()) == 21
    assert value('SELECT GROUP_CONCAT(id ORDER BY id) FROM comments WHERE coupon_id IS NOT NULL') == old_ids
    assert value(f"SELECT HEX(body) FROM comments WHERE request_id='{key}'") == old_body
    new_id = value(f"SELECT id FROM comments WHERE request_id='{key}'")
    assert int(new_id) > 4
    assert value("SELECT JSON_UNQUOTE(JSON_EXTRACT(description,'$.article_comment_id')) FROM activity_logs WHERE action='ARTICLE_COMMENT_CREATE'") == new_id
    for table, (columns, before) in snapshots.items():
        where = 'WHERE coupon_id IS NOT NULL' if table == 'comments' else ''
        assert digest(table, columns, where) == before, 'Comment upgrade changed existing ' + table
    print('PASS 23 -> 22 -> 21: existing tables/accounts/passwords, coupon IDs, history fields and article comment/audit preserved.', flush=True)
    before = {t: query(f'SELECT * FROM `{t}` ORDER BY {ordering(t)}').stdout for t in tables()}
    load('database/migrations/20261109_legacy_core_compat.sql')
    load('database/migrations/20261107_unify_comments.sql')
    assert all(query(f'SELECT * FROM `{t}` ORDER BY {ordering(t)}').stdout == rows for t, rows in before.items())
    assert value('SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE()') == '21'
    print('PASS repeat upgrade does not duplicate history/comments or change application data.', flush=True)
    reset()
    query("UPDATE coupons SET image_path='assets/customer-image.png' WHERE id=1")
    failed = query((ROOT/'database/migrations/20261109_legacy_core_compat.sql').read_text(encoding='utf-8'), check=False)
    assert failed.returncode and b'contain data' in failed.stderr
    assert value('SELECT COUNT(*) FROM coupon_status_history') == '31'
    assert value('SELECT image_path FROM coupons WHERE id=1') == 'assets/customer-image.png'
    print('PASS populated legacy content is refused before removal; original history/content preserved.', flush=True)

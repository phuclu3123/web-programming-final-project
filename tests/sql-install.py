"""Validate the SQL handoff on the explicitly isolated MariaDB instance only.

This resets couponhub_merge_test on 127.0.0.1:13379. Never uses app config.
Run: python tests/sql-install.py [--mysql path/to/mysql] [--php path/to/php]
"""
import argparse
import os
from pathlib import Path
import subprocess

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--mysql', default='C:/xampp/mysql/bin/mysql.exe')
parser.add_argument('--php', default='C:/xampp/php/php.exe')
args = parser.parse_args()
if os.environ.get('COUPONHUB_RUN_DB_TEST') != '1':
    raise SystemExit('SKIP: set COUPONHUB_RUN_DB_TEST=1 to reset the isolated test DB.')
mysql = [args.mysql, '--no-defaults', '--host=127.0.0.1', '--port=13379',
         '--user=root', '--default-character-set=utf8mb4', '--batch', '--raw',
         '--skip-column-names']
database = 'couponhub_merge_test'


def query(sql, selected=True):
    return subprocess.check_output(mysql + ([database] if selected else []) + ['-e', sql]).decode('utf-8').strip()


def load(name, expect_success=True):
    result = subprocess.run(mysql + [database], input=(ROOT / 'database' / name).read_bytes(), capture_output=True)
    if expect_success and result.returncode:
        raise AssertionError(result.stderr.decode('utf-8', errors='replace'))
    if not expect_success:
        assert result.returncode != 0, 'Unsafe import unexpectedly succeeded'
    return result


def reset():
    assert query('SELECT @@port', selected=False) == '13379'
    query('DROP DATABASE IF EXISTS couponhub_merge_test; '
          'CREATE DATABASE couponhub_merge_test CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci', selected=False)
    load('schema.sql')


def counts():
    tables = query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() "
                   "AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME").splitlines()
    return {table: int(query(f'SELECT COUNT(*) FROM `{table}`')) for table in tables}


def objects():
    return tuple(int(query(sql)) for sql in [
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'",
        'SELECT COUNT(*) FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE()',
        'SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE()',
    ])


reset()
assert objects() == (21, 6, 21), 'Incomplete schema'
assert sum(counts().values()) == 0, 'Schema must not seed demo data'
load('demo.sql')
assert objects() == (21, 6, 21), 'Temporary installer must be removed after success'
env = os.environ | {'COUPONHUB_HOST': '127.0.0.1', 'COUPONHUB_PORT': '13379',
                    'COUPONHUB_DB': database, 'COUPONHUB_USER': 'root', 'COUPONHUB_PASSWORD': ''}
# Local overrides could redirect PHP to a different DB: fail before invoking it.
assert not (ROOT / 'config/local.php').exists(), 'Temporarily move local config before this isolated test'
subprocess.run([args.php, 'tests/demo-seed.php'], cwd=ROOT, env=env, check=True)
before = counts()
hash_before = query('SELECT GROUP_CONCAT(password_hash ORDER BY id) FROM users')
duplicate = load('demo.sql', expect_success=False)
assert b'empty CouponHub schema' in duplicate.stderr, 'Expected explicit nonempty-data guard'
assert counts() == before, 'Repeated import changed data'
assert query('SELECT GROUP_CONCAT(password_hash ORDER BY id) FROM users') == hash_before
# A CLI stops on SIGNAL before reaching cleanup; remove only our installer.
query('DROP PROCEDURE IF EXISTS couponhub_install_demo')
assert objects() == (21, 6, 21)
print('PASS: separate schema/demo import, fixture login/roles, nonempty guard, no duplicate data.', flush=True)

# Prove a late seed failure rolls back earlier account/config/shop inserts.
reset()
query("ALTER TABLE coupons ADD CONSTRAINT test_reject_demo CHECK (title NOT LIKE '[DEMO]%')")
failed = load('demo.sql', expect_success=False)
assert b'test_reject_demo' in failed.stderr, 'Expected intentional late constraint failure'
assert sum(counts().values()) == 0, 'Demo import must roll back all fixture writes'
print('PASS: seed constraint failure rolls back all demo data; FK checks remain enabled.', flush=True)
assert query('SELECT @@foreign_key_checks') == '1'

# Leave a clean, successfully imported demo DB for subsequent integration tests.
reset()
load('demo.sql')
assert objects() == (21, 6, 21)

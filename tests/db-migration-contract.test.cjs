'use strict';

/**
 * CouponHub — static migration contract checks (Node.js >= 18)
 * Usage (from project root): node --test tests/db-migration-contract.test.cjs
 * This file only checks source contracts. It does NOT connect to MySQL,
 * import SQL, or prove that the PHP application works at runtime.
 */
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const read = relativePath => fs.readFileSync(path.join(root, relativePath), 'utf8');
const schema = read('database/schema.sql');
const seed = read('database/demo.sql');
const repo = read('app/repositories/CouponRepository.php');
const adminRepo = read('app/repositories/AdminCouponRepository.php');
const notificationRepo = read('app/repositories/NotificationRepository.php');

function tableDefinition(tableName) {
  const match = schema.match(new RegExp('^CREATE TABLE(?: IF NOT EXISTS)?\\s+`?'+tableName+'`?\\s+\\([\\s\\S]*?\\n\\) ENGINE=', 'm'));
  assert.ok(match, `Missing CREATE TABLE ${tableName}`);
  return match[0];
}

test('schema defines 21 tables, 6 views, 21 stored procedures (no duplicate tables)', () => {
  const tables = [...schema.matchAll(/^CREATE TABLE(?: IF NOT EXISTS)?\s+`?([a-z_]+)`?\s*\(/gm)].map(m => m[1]);
  assert.equal(tables.length, 21);
  assert.equal(new Set(tables).size, 21);
  const views = [...schema.matchAll(/CREATE\s+VIEW\s+`(v_[^`]+)`/g)].map(m => m[1]);
  assert.equal(views.length, 6);
  assert.equal(new Set(views).size, 6);
  const procedures = [...schema.matchAll(/^CREATE PROCEDURE\s+`([^`]+)`/gm)].map(m => m[1]);
  assert.equal(procedures.length, 21);
  assert.equal(new Set(procedures).size, 21);
});

test('schema includes new main features and removes legacy history table', () => {
  for (const name of ['comments', 'external_offers', 'oauth_accounts', 'activity_logs']) {
    tableDefinition(name);
  }
  assert.doesNotMatch(schema, /^CREATE TABLE `coupon_status_history`/m);
  assert.doesNotMatch(schema, /^CREATE TABLE(?: IF NOT EXISTS)? article_comments/m);
  assert.match(tableDefinition('activity_logs'), /`legacy_history_id`/);
  assert.match(tableDefinition('activity_logs'), /`new_moderation`/);
  assert.match(tableDefinition('activity_logs'), /`old_publication`/);
});

test('five deprecated columns have been removed from canonical schema', () => {
  const article = tableDefinition('articles');
  const coupon = tableDefinition('coupons');
  for (const name of ['category_id', 'cover_path']) {
    assert.doesNotMatch(article, new RegExp('^\\s*`'+name+'`\\s', 'm'));
  }
  for (const name of ['currency', 'image_path', 'qr_image_path']) {
    assert.doesNotMatch(coupon, new RegExp('^\\s*`'+name+'`\\s', 'm'));
  }
  assert.match(coupon, /^\s*`category_id`\s/gm);
});

test('schema is locally portable and OAuth provider includes Facebook', () => {
  assert.doesNotMatch(schema, /DEFINER=`root`@`localhost`/);
  assert.doesNotMatch(schema, /utf8mb4_general_ci/);
  assert.match(tableDefinition('oauth_accounts'), /enum\('github','google','facebook'\)/);
});

test('CouponRepository calls sp_submit_coupon with exactly 17 placeholders', () => {
  const calls = [...repo.matchAll(/CALL sp_submit_coupon\(([^)]*)\)/g)];
  assert.equal(calls.length, 2);
  for (const call of calls) {
    assert.equal((call[1].match(/\?/g) || []).length, 17);
  }
});

test('PHP repositories no longer write or read coupon_status_history', () => {
  for (const [label, source] of [
    ['CouponRepository', repo],
    ['AdminCouponRepository', adminRepo],
    ['NotificationRepository', notificationRepo],
  ]) {
    assert.doesNotMatch(source, /coupon_status_history/, `${label} still refers to removed table`);
  }
  assert.match(notificationRepo, /FROM activity_logs h JOIN coupons c/);
  assert.match(notificationRepo, /WHERE c\.submitted_by=\?/);
});

test('demo data and incremental migrations match new schema', () => {
  assert.doesNotMatch(seed, /coupon_status_history/);
  for (const name of ['currency', 'image_path', 'qr_image_path']) {
    assert.doesNotMatch(seed, new RegExp('`'+name+'`'));
  }
  assert.equal((seed.match(/'COUPON_STATUS_CHANGE'\)/g) || []).length, 31);
  for (const file of [
    'database/migrations/20261107_unify_comments.sql',
    'database/migrations/20261105_external_offers.sql',
    'database/migrations/20261106_article_comments.sql',
    'database/migrations/20261107_facebook_provider_compat.sql',
  ]) {
    assert.ok(fs.existsSync(path.join(root, file)), `${file} is missing`);
  }
});

const articleRepo = read('app/repositories/ArticleCommentRepository.php');
const interactions = read('app/repositories/InteractionRepository.php');
const mergeSql = read('database/migrations/20261107_unify_comments.sql');
test('unified comments enforces exactly one target, and retains article dedup', () => {
  const c = tableDefinition('comments');
  for (const name of ['coupon_id','article_id','request_id','user_id','status']) {
    assert.match(c, new RegExp('`'+name+'`'));
  }
  assert.match(c, /ck_comment_target/);
  assert.match(c, /uq_comment_article_request/);
  assert.match(c, /fk_comment_article/);
  assert.match(c, /fk_comment_coupon/);
  assert.match(articleRepo, /FROM comments c/);
  assert.match(articleRepo, /AND article_id IS NOT NULL FOR UPDATE/);
  assert.match(articleRepo, /WHERE c\.article_id IS NOT NULL/);
  assert.doesNotMatch(articleRepo, /\barticle_comments\b/);
  assert.match(interactions, /INSERT INTO comments\(coupon_id, user_id, body\)/);
});
test('incremental migration preserves coupon IDs and verifies article parity', () => {
  assert.match(mergeSql, /MODIFY COLUMN coupon_id BIGINT UNSIGNED NULL/);
  assert.match(mergeSql, /NOT EXISTS \(/);
  assert.match(mergeSql, /v_legacy_count <> v_valid_copy/);
  assert.match(mergeSql, /JSON_SET\(/);
  assert.match(mergeSql, /comment_migrated_21/);
  assert.match(mergeSql, /DROP TABLE article_comments/);
  assert.match(mergeSql, /DROP PROCEDURE couponhub_unify_comments_20261107/);
});

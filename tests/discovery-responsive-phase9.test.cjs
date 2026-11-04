'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const home = fs.readFileSync(path.join(root, 'app/views/home.php'), 'utf8');
const community = fs.readFileSync(path.join(root, 'app/views/coupons/index.php'), 'utf8');
const css = fs.readFileSync(path.join(root, 'assets/css/integration.css'), 'utf8');
const phase9 = css.split('/* Phase 9A v2 —')[1] || '';

test('home search keeps its PHP form behavior, visible label and accessible icon-only mode', () => {
  assert.match(home, /id="home-filter-form"/);
  assert.match(home, /id="q"[^>]*name="q"/);
  assert.match(home, /type="submit" class="btn btn-lime search-submit couponhub-search-action" aria-label="Khám phá ưu đãi"/);
  assert.match(home, /<span class="search-submit-label">Khám phá<\/span>/);
  assert.match(home, /class="fa-solid fa-magnifying-glass"/);
});

test('home and community search share pill dimensions and control rhythm', () => {
  assert.match(community, /community-search-submit-label">Tìm kiếm<\/span>/);
  assert.match(css, /\.community-search-input > \.community-search-submit\s*\{[^}]*min-height:\s*48px/);
  assert.match(phase9, /\.hero-search\.couponhub-search-form\s*\{[^}]*border-radius:\s*999px/);
  assert.match(phase9, /\.hero-search \.search-submit\s*\{[^}]*min-width:\s*132px/);
  assert.match(phase9, /\.hero-search \.search-submit\s*\{[^}]*border-radius:\s*999px/);
});

test('mobile search stays in one row with accessible icon-only control', () => {
  assert.match(phase9, /\.hero-search\.couponhub-search-form\s*\{[^}]*flex-wrap:\s*nowrap/);
  const mobile = phase9.split('@media (max-width: 600px) {')[1]?.split('@media (max-width: 359.98px)')[0];
  assert.ok(mobile);
  assert.match(mobile, /\.search-submit\s*\{[^}]*width:\s*48px/);
  assert.match(mobile, /\.search-submit-label\s*\{[^}]*position:\s*absolute/);
});

test('category chips wrap and filter hint remains visible', () => {
  assert.match(home, /class="category-strip"/);
  assert.match(phase9, /\.category-strip\s*\{[^}]*flex-wrap:\s*wrap/);
  assert.match(phase9, /\.category-strip\s*\{[^}]*overflow:\s*visible/);
  assert.match(phase9, /\.home-refine > summary > span\s*\{[^}]*display:\s*block/);
});

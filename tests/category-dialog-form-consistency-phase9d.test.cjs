'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const read = name => fs.readFileSync(path.join(__dirname, '..', name), 'utf8');
const css = read('assets/css/category-navigation.css');
const release = read('assets/css/release.css');
const partial = read('app/views/partials/category-picker-dialog.php');
const community = read('app/views/coupons/index.php');

// The community dialog is a descendant of the GET form. release.css has
// broad !important input/div resets which would otherwise leak into the dialog.
test('category dialog search input keeps its own styling inside Community form', () => {
  assert.match(community, /class="community-search couponhub-search-form"/);
  assert.match(community, /require .*category-picker-dialog\.php/);
  assert.match(release, /\.couponhub-search-form input\[type="search"\]/);
  const isolation = css.slice(css.indexOf('/* Phase 9D follow-up'));
  assert.ok(isolation.length > 100, 'expected scoped dialog isolation');
  assert.match(isolation, /\.couponhub-category-dialog \.couponhub-category-dialog-search > input\[type="search"\]\s*\{/);
  for (const expected of [
    /padding: 0 15px 0 43px !important/,
    /border-radius: 13px !important/,
    /background: #f7f9fd !important/,
    /color: #15284b !important/,
    /font-size: 14px !important/,
    /flex: 0 1 auto !important/,
  ]) assert.match(isolation, expected);
  assert.match(isolation, /\.couponhub-category-dialog \.couponhub-category-dialog-search > input\[type="search"\]:focus\s*\{/);
  assert.match(isolation, /box-shadow: 0 0 0 3px rgba\(49, 94, 203, \.16\) !important/);
});

test('category dialog footer and Font Awesome markup remain consistent on three pages', () => {
  const isolation = css.slice(css.indexOf('/* Phase 9D follow-up'));
  assert.match(isolation, /\.couponhub-category-dialog \.couponhub-category-dialog-footer\s*\{/);
  assert.match(isolation, /border-top: 1px solid #e6edf5 !important/);
  assert.match(partial, /fa-solid fa-magnifying-glass/);
  assert.match(partial, /fa-solid fa-xmark/);
  assert.doesNotMatch(partial, /<svg\b/i);
  for (const name of ['home.php', 'coupons/index.php', 'categories/index.php']) {
    const dir = name === 'home.php' ? 'app/views/home.php' : `app/views/${name}`;
    assert.match(read(dir), /category-picker-dialog\.php/);
  }
});

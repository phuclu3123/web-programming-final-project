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

// Phase 9G-B: no more universal search-input selector leaking into nested
// category dialog, even when it lives inside the Community form.
test('category dialog owns search-field styling inside Community form', () => {
  assert.match(community, /class="community-search couponhub-search-form"/);
  assert.match(community, /require .*category-picker-dialog\.php/);
  assert.doesNotMatch(release, /\.community-catalog \.community-search-input > input\[type="search"\]/);
  assert.match(release, /\.editorial-home \.hero-search > input\[name="q"\]/);
  assert.doesNotMatch(release, /\.couponhub-search-form input\[type="search"\]/);
  assert.doesNotMatch(release, /\.couponhub-search-form input\s*\{/);
  const base = css.match(/\.couponhub-category-dialog-search > input\s*\{([^}]+)\}/)?.[1];
  assert.ok(base, 'dialog search owns its one canonical input style');
  for (const expected of [/padding: 0 15px 0 43px/, /border-radius: 13px/, /background: #f7f9fd/, /color: #15284b/, /font-size: 14px/]) {
    assert.match(base, expected);
  }
  assert.doesNotMatch(base, /!important/);
  assert.match(css, /\.couponhub-category-dialog \.couponhub-category-dialog-search > input:focus\s*\{[^}]*box-shadow: 0 0 0 3px rgba\(49, 94, 203, \.16\)/);
});

test('category dialog footer and Font Awesome markup remain consistent on three pages', () => {
  // The existing descendant-div reset still affects the Community dialog
  // footer. Keep its local border until the broader div cleanup is audited.
  assert.match(css, /\.couponhub-category-dialog \.couponhub-category-dialog-footer\s*\{[^}]*border-top: 1px solid #e6edf5 !important/);
  assert.match(partial, /fa-solid fa-magnifying-glass/);
  assert.match(partial, /fa-solid fa-xmark/);
  assert.doesNotMatch(partial, /<svg\b/i);
  for (const name of ['home.php', 'coupons/index.php', 'categories/index.php']) {
    const dir = name === 'home.php' ? 'app/views/home.php' : `app/views/${name}`;
    assert.match(read(dir), /category-picker-dialog\.php/);
  }
});

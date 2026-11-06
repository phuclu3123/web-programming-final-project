'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const css = read('assets/css/release.css');
const categories = read('assets/css/category-navigation.css');
const views = [read('app/views/home.php'), read('app/views/coupons/index.php')];

test('legacy search field rules style Explore only and leave Community to its wrapper', () => {
  assert.doesNotMatch(css, /\.community-catalog \.community-search-input > input\[type="search"\]/);
  assert.match(css, /\.editorial-home \.hero-search > input\[name="q"\]/);
  assert.doesNotMatch(css, /\.couponhub-search-form input\s*\{/);
  assert.doesNotMatch(css, /\.couponhub-search-form input\[type="search"\]/);
  assert.match(views[0], /name="q"/);
  assert.match(views[1], /class="community-search-input"/);
});

test('retired search icon is scoped to submit action, not all form buttons', () => {
  assert.match(css, /\.couponhub-search-form \.couponhub-search-action::after\s*\{[^}]*content: none !important/);
  assert.doesNotMatch(css, /\.couponhub-search-form button::after/);
});

test('discovery region does not wipe every pseudo-element including FA glyphs', () => {
  for (const selector of ['.discovery-section', '.discovery-toolbar']) {
    for (const pseudo of ['before', 'after']) {
      const escaped = selector.replaceAll('.', String.raw`\.`);
      assert.ok(!new RegExp(escaped + String.raw`\s+\*::${pseudo}`).test(css), `broad ${selector} pseudo reset still exists`);
    }
  }
  assert.match(categories, /\.couponhub-category-dialog \.fa-solid::before/);
  assert.match(read('app/views/partials/category-picker-dialog.php'), /fa-solid fa-magnifying-glass/);
});

test('dialog search has only one canonical input rule without important shadow copies', () => {
  assert.equal((categories.match(/\.couponhub-category-dialog-search > input\s*\{/g) || []).length, 1);
  const inputRule = categories.match(/\.couponhub-category-dialog-search > input\s*\{([^}]+)\}/)?.[1];
  assert.ok(inputRule);
  assert.doesNotMatch(inputRule, /!important/);
  assert.doesNotMatch(categories, /\.couponhub-category-dialog \.couponhub-category-dialog-search > input\[type="search"\]\s*\{/);
});

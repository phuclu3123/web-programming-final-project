'use strict';

const { test } = require('node:test');
const assert = require('node:assert/strict');
const { readFileSync } = require('node:fs');
const { join } = require('node:path');

const root = join(__dirname, '..');
const read = file => readFileSync(join(root, file), 'utf8');
const release = read('assets/css/release.css');

test('search CSS uses a stable component class instead of placeholder text', () => {
  assert.doesNotMatch(release, /form:has\(input\[placeholder\*=/);
  assert.doesNotMatch(release, /\.community-catalog \.community-search-input > input\[type="search"\]/);
  assert.match(release, /\.couponhub-search-form \.couponhub-search-action::after/);
  for (const [file, id] of [
    ['app/views/home.php', 'home-filter-form'],
    ['app/views/coupons/index.php', 'coupon-search-form'],
  ]) {
    const html = read(file);
    assert.match(html, new RegExp('<form[^>]+id="' + id + '"[^>]+class="[^"]*couponhub-search-form'));
  }
});

test('generic form reset rules do not alter auth or admin forms', () => {
  const css = release.replace(/\/\*[\s\S]*?\*\//g, '');
  assert.doesNotMatch(css, /(?:^|\n)\s*form\s+(?:div|hr|\*::(?:before|after))\s*[,\{]/m);
  assert.match(css, /\.couponhub-search-form div\s*,/);
  assert.match(css, /\.couponhub-search-form hr\s*\{/);
});

test('authentication forms are not opted into search-only styles', () => {
  for (const file of [
    'app/views/auth/login-form.php',
    'app/views/auth/register-form.php',
    'app/views/auth/forgot-password-form.php',
  ]) {
    assert.doesNotMatch(read(file), /couponhub-search-form/);
  }
});

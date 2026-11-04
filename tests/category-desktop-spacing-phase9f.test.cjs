'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const read = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const css = read('assets/css/category-navigation.css');
const phaseE = css.slice(css.indexOf('/* Phase 9E'));
const phaseF = phaseE.slice(phaseE.indexOf('/* Phase 9F'));

test('Phase 9F is inside the existing desktop-only Phase 9E media query', () => {
  assert.ok(phaseE.includes('/* Phase 9F'));
  assert.match(phaseE, /@media \(min-width: 992px\)/);
  assert.equal((phaseE.match(/@media/g) || []).length, 1);
  assert.doesNotMatch(phaseF, /@media|!important|\.editorial-home/);
});

test('Community category toolbar adds 18px of reliable internal spacing', () => {
  const community = read('app/views/coupons/index.php');
  assert.match(community, /class="community-search couponhub-search-form"/);
  assert.match(community, /<div class="couponhub-category-tools" data-category-picker>/);
  assert.match(phaseF, /\.community-catalog \.community-board \.community-search > \.couponhub-category-tools\[data-category-picker-ready="true"\]\s*\{\s*padding-top: 18px;/);
});

test('Category directory chips gain 24px before coupon cards', () => {
  const directory = read('app/views/categories/index.php');
  assert.match(directory, /class="editorial-block category-picks"/);
  assert.match(directory, /class="couponhub-category-tools" data-category-picker/);
  assert.match(directory, /class="community-ticket-grid"/);
  assert.match(phaseF, /\.discovery-page \.category-picks > \.couponhub-category-tools\[data-category-picker-ready="true"\]\s*\{\s*margin-bottom: 24px;/);
});

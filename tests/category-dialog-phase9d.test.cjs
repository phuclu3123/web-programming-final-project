'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const read = filename => fs.readFileSync(path.join(__dirname, '..', filename), 'utf8');
const views = [
  read('app/views/home.php'),
  read('app/views/coupons/index.php'),
  read('app/views/categories/index.php'),
];
const dialog = read('app/views/partials/category-picker-dialog.php');
const css = read('assets/css/category-navigation.css');
const picker = read('assets/js/category-picker.js');

test('all three routes render the same PHP dialog partial with unique ids', () => {
  const ids = ['home-category-dialog', 'community-category-dialog', 'directory-category-dialog'];
  views.forEach((view,i) => {
    assert.ok(view.includes(`$categoryPickerDialogId = '${ids[i]}'`));
    assert.match(view,/require .*category-picker-dialog\.php/);
    assert.equal((view.match(/data-category-picker/g)||[]).length,1);
  });
  assert.match(dialog,/aria-labelledby/);
  assert.match(dialog,/data-category-dialog-search/);
  assert.match(dialog,/data-category-dialog-options/);
  assert.match(dialog,/data-category-dialog-status/);
  assert.match(dialog,/data-category-close/);
});

test('the Explore-specific pseudo-element reset cannot hide dialog Font Awesome icons', () => {
  assert.match(css,/\.couponhub-category-dialog \.fa-solid::before/);
  assert.match(css,/content: var\(--fa\) !important/);
  assert.match(css,/display: inline-block !important/);
  assert.match(css,/\.couponhub-category-dialog \.couponhub-category-dialog-header h2/);
  assert.match(dialog,/fa-solid fa-xmark/);
  assert.match(dialog,/fa-solid fa-magnifying-glass/);
  assert.match(picker,/selectedIcon\.className = 'fa-solid fa-check'/);
});

test('desktop shows at most seven featured categories and an overflow trigger when needed', () => {
  assert.match(picker,/options\.length <= 8/);
  assert.match(picker,/phoneMedia\.matches \? 3 : compactMedia\.matches \? 5 : 7/);
  assert.match(css,/@media \(min-width: 992px\)/);
  assert.match(css,/data-category-overflow="true"/);
});

test('dialog search is accent-insensitive, announces result count, and prevents form submission', () => {
  assert.match(picker,/normalize\('NFD'\)/);
  assert.match(picker,/data-category-dialog-status/);
  assert.match(picker,/localeCompare\(b\.textContent\.trim\(\), 'vi'/);
  assert.match(picker,/button\.hidden = Boolean\(query\)/);
  assert.match(picker,/event\.preventDefault\(\)/);
  assert.match(picker,/input\?\.click\(\)/);
  assert.doesNotMatch(picker,/\.innerHTML\s*=/);
});

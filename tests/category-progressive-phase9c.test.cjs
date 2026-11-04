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
const picker = read('assets/js/category-picker.js');
const css = read('assets/css/category-navigation.css');
const sharedDialog = read('app/views/partials/category-picker-dialog.php');

test('Home, Community and Categories use a shared progressive category chooser', () => {
  const ids = ['home-category-dialog', 'community-category-dialog', 'directory-category-dialog'];
  views.forEach((view, i) => {
    assert.match(view, /data-category-picker/);
    assert.equal((view.match(/data-category-rail/g) || []).length, 1);
    assert.match(view, /data-category-open/);
    assert.match(view, /<span>Xem thêm<\/span>/);
    assert.match(view, /fa-solid fa-chevron-down/);
    assert.ok(view.includes(`$categoryPickerDialogId = '${ids[i]}'`));
    assert.match(view, /require .*category-picker-dialog\.php/);
    assert.match(sharedDialog, /data-category-dialog-options/);
  });
});

test('Category directory retains its full primary navigation while the preview tabs are progressive', () => {
  const directory = views[2];
  assert.match(directory, /<nav class="category-directory-list"/);
  assert.match(directory, /<nav class="category-pick-tabs" data-category-rail/);
  assert.match(directory, /\?category='\.\(int\)\$category\['id'\]/);
  assert.match(directory, /\$selectedCategory===\(int\)\$category\['id'\]/);
  assert.match(directory, /aria-current="true"/);
});

test('Compact chips include the currently selected category and do not create duplicate inputs', () => {
  assert.match(picker, /const selected = options\.find\(isSelected\)/);
  assert.match(picker, /new Set\(\[selected, options\[0\], \.\.\.options\]/);
  assert.match(picker, /phoneMedia\.matches \? 3 : compactMedia\.matches \? 5 : 7/);
  assert.match(picker, /option\.dataset\.categoryCollapsed = 'true'/);
  assert.match(picker, /options\.length <= 8/);
  assert.match(picker, /source\.click\(\)/);
  assert.match(picker, /input\?\.click\(\)/);
  assert.doesNotMatch(picker, /document\.createElement\('input'\)/);
  assert.match(picker, /couponhub:catalog-updated/);
});

test('Compact category chips remain accessible and selected states are visually distinct', () => {
  assert.match(css, /\[data-category-rail\] > \[data-category-collapsed="true"\][\s\S]*?display: none !important/);
  assert.match(css, /min-height: 44px/);
  assert.match(css, /\.category-pick-tabs > a\[aria-current\]/);
  assert.match(css, /\.community-categories input:checked \+ span/);
  assert.match(css, /\.community-categories input:focus-visible \+ span/);
  assert.match(css, /\.couponhub-category-option:focus-visible/);
  assert.match(picker, /dialog\.showModal\(\)/);
  assert.match(picker, /dialog\.addEventListener\('close'/);
  assert.match(picker, /dialog\.close\(\)/);
  assert.match(picker, /floating\.inert\s*=\s*overlaps/);
});

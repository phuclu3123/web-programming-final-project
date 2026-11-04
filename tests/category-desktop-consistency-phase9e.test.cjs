'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const load = file => fs.readFileSync(path.join(__dirname, '..', file), 'utf8');
const style = load('assets/css/category-navigation.css');
const phase = style.slice(style.indexOf('/* Phase 9E'));
const views = [
  load('app/views/home.php'),
  load('app/views/coupons/index.php'),
  load('app/views/categories/index.php'),
];

test('all three public views expose the same desktop category toolbar hooks', () => {
  assert.ok(phase.startsWith('/* Phase 9E'));
  for (const view of views) {
    assert.equal((view.match(/data-category-picker/g) || []).length, 1);
    assert.match(view, /data-category-rail/);
    assert.match(view, /data-category-open/);
    assert.match(view, /category-picker-dialog\.php/);
  }
});

test('desktop chip rail is content-sized and overflow button stays adjacent', () => {
  assert.match(phase, /@media \(min-width: 992px\)/);
  assert.match(phase, /justify-content:\s*flex-start/);
  assert.match(phase, /gap:\s*10px/);
  assert.match(phase, /flex:\s*0 1 auto/);
  assert.match(phase, /width:\s*max-content/);
  assert.match(phase, /\[data-category-rail\] > label/);
  assert.doesNotMatch(phase, /flex:\s*1 1 0/);
});

test('desktop chips and overflow control share height and brand selection color', () => {
  assert.match(phase, /\.category-chip\.selected/);
  assert.match(phase, /\.category-pick-tabs > a\[aria-current\]/);
  assert.match(phase, /\.community-categories input:checked \+ span/);
  assert.match(phase, /background:\s*#2856cc/);
  assert.match(phase, /min-height:\s*46px/);
  assert.match(phase, /height:\s*46px/);
  assert.match(phase, /\.couponhub-category-open/);
});

test('desktop polish remains CSS-only, preserving mobile/tablet and filter behavior', () => {
  assert.equal((phase.match(/@media/g) || []).length, 1);
  assert.doesNotMatch(phase, /@media\s*\(max-width/);
  const js = load('assets/js/category-picker.js');
  assert.match(js, /input\?\.click\(\)/);
  assert.match(js, /source\.click\(\)/);
});


test('Explore desktop restores only its Font Awesome chevron hidden by legacy CSS', () => {
  assert.match(views[0], /couponhub-category-open[\s\S]*?fa-solid fa-chevron-down/);
  assert.match(phase, /\.editorial-home \.discovery-section \.couponhub-category-tools \.couponhub-category-open > \.fa-solid::before/);
  assert.match(phase, /content:\s*var\(--fa\)\s*!important/);
});

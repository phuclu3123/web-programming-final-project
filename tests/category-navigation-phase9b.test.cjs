'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = filename => fs.readFileSync(path.join(root, filename), 'utf8');
const home = read('app/views/home.php');
const community = read('app/views/coupons/index.php');
const css = read('assets/css/category-navigation.css').split('/* Phase 9B —')[1];
const picker = read('assets/js/category-picker.js');
const sharedDialog = read('app/views/partials/category-picker-dialog.php');

test('search buttons on home/community use the same Font Awesome action component', () => {
  assert.match(home, /class="btn btn-lime search-submit couponhub-search-action"/);
  assert.match(community, /class="btn btn-brand community-search-submit couponhub-search-action"/);
  assert.match(home, /fa-solid fa-magnifying-glass/);
  assert.match(community, /fa-solid fa-magnifying-glass/);
  assert.doesNotMatch(community, /community-search-submit-label">Tìm kiếm<\/span>\s*<\?= icon\('search'\)/);
  assert.match(css, /\.community-search-input > \.couponhub-search-action,[\s\S]*?\.hero-search \.couponhub-search-action/);
});

test('both pages use a single original category source and one accessible dialog', () => {
  for (const [source, id] of [[home, 'home-category-dialog'], [community, 'community-category-dialog']]) {
    assert.match(source, /data-category-picker/);
    assert.match(source, /data-category-rail/);
    assert.match(source, /data-category-open\s+aria-haspopup="dialog"/);
    assert.ok(source.includes(`$categoryPickerDialogId = '${id}'`));
    assert.match(source, /require .*category-picker-dialog\.php/);
    assert.match(sharedDialog, /<dialog id="<\?= e\(\$categoryPickerDialogId\) \?>" class="couponhub-category-dialog"/);
    assert.match(sharedDialog, /data-category-dialog-options/);
    assert.match(sharedDialog, /data-category-close aria-label="Đóng danh mục"/);
    assert.ok(source.includes('fa-chevron-down')); // Category labels can remain text-only.
    assert.match(sharedDialog, /fa-xmark/);
    assert.equal((source.match(/data-category-rail/g) || []).length, 1);
  }
  assert.match(community, /name="category_id"/);
  assert.match(home, /data-home-filter/);
});

test('category dialog reuses native home anchors and community radios without cloned inputs', () => {
  assert.match(picker, /\.filter\(node => node\.matches\('a, label'\)\)/);
  assert.match(picker, /document\.createElement\('button'\)/);
  assert.match(picker, /source\.textContent\.replace\(/);
  assert.match(picker, /button\.textContent =/);
  assert.match(picker, /button\.setAttribute\('aria-pressed'/);
  assert.match(picker, /source\.click\(\)/);
  assert.match(picker, /input\?\.click\(\)/);
  assert.doesNotMatch(picker, /\.innerHTML\s*=/);
  assert.doesNotMatch(picker, /document\.createElement\('input'\)/);
});

test('category modal handles escape/backdrop/focus and reinitializes after home AJAX', () => {
  assert.match(picker, /dialog\.showModal\(\)/);
  assert.match(picker, /dialog\.addEventListener\('close'/);
  assert.match(picker, /trigger\.focus\(\{ preventScroll: true \}\)/);
  assert.match(picker, /if \(event\.target === dialog\) dialog\.close\(\)/);
  assert.match(picker, /'couponhub:catalog-updated'/);
  assert.match(picker, /dialog\.close\(\)/);
  assert.match(css, /\.couponhub-category-dialog::backdrop/);
});

test('category navigation progressively hides overflow below tablet breakpoint and remains accessible without JS', () => {
  assert.ok(css.includes('@media (max-width: 991.98px)'));
  assert.match(css, /\[data-category-picker-ready="true"\][\s\S]*?flex-wrap: nowrap;/);
  assert.match(css, /\[data-category-rail\] > \[data-category-collapsed="true"\]/);
  assert.match(css, /\.couponhub-category-open\s*\{ display: none; \}/);
  assert.match(css, /\.couponhub-category-dialog-options\s*\{[^}]*grid-template-columns: repeat\(2, minmax\(0, 1fr\)\)/);
  assert.match(css, /max-height: min\(78dvh, 640px\)/);
});

test('self-hosted Font Awesome and phase9b script load on both public layouts', () => {
  for (const layout of ['app/views/layouts/head.php', 'app/views/layouts/main.php']) {
    const source = read(layout);
    assert.match(source, /category-picker\.js/);
    assert.match(source, /category-navigation\.css/);
    assert.match(source, /fontawesome\/css\/all\.min\.css/);
  }
  const fa = read('assets/vendor/fontawesome/css/all.min.css');
  for (const name of ['fa-magnifying-glass', 'fa-chevron-down', 'fa-xmark', 'fa-border-all']) {
    assert.ok(fa.includes(name), `Font Awesome asset missing ${name}`);
  }
});

// Regression for issues seen on actual mobile Chrome after Phase 9B.
test('legacy generated SVG magnifier is disabled; one self-hosted FA icon remains', () => {
  const release = read('assets/css/release.css');
  const legacy = release.match(/\.couponhub-search-form button::after\s*\{([^}]+)\}/)?.[1];
  assert.ok(legacy, 'Legacy rule must be neutralized, not left drawing an SVG');
  assert.match(legacy, /content:\s*none\s*!important/);
  assert.match(legacy, /display:\s*none\s*!important/);
  assert.doesNotMatch(legacy, /(?:-webkit-)?mask:\s*url|data:image\/svg\+xml/);
  for (const source of [home, community]) {
    assert.equal((source.match(/class="fa-solid fa-magnifying-glass"/g) || []).length, 1);
  }
});

test('mobile category trigger is a short horizontal labeled pill, not a two-row tile', () => {
  const mobile = css.split('@media (max-width: 600px)')[1].split('@media (max-width: 359.98px)')[0];
  const trigger = mobile.match(/\.couponhub-category-open\s*\{([^}]+)\}/)?.[1];
  assert.ok(trigger);
  assert.match(trigger, /flex-direction:\s*row/);
  assert.match(trigger, /height:\s*44px/);
  assert.match(trigger, /border-radius:\s*999px/);
  assert.doesNotMatch(trigger, /width:\s*64px|flex-direction:\s*column/);
  assert.match(picker, /is-category-overlap/);
  assert.match(picker, /floating\.inert\s*=\s*overlaps/);
  assert.match(css, /\.floating-support\.is-category-overlap\s*\{/);
});

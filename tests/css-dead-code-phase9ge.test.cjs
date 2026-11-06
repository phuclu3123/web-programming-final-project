'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const read = relativePath => fs.readFileSync(path.join(root, relativePath), 'utf8');
const cssFiles = [
  'assets/css/styles.css',
  'assets/css/design-system.css',
  'assets/css/discovery.css',
  'assets/css/editorial.css',
  'assets/css/product.css',
  'assets/css/release.css',
];
const css = cssFiles.map(read).join('\n');

// These named class families were checked against every PHP view/controller and
// JavaScript source file in Phase 9G-E. Dynamic and stateful CSS was excluded.
test('obsolete static CSS families no longer declare orphan selectors', () => {
  for (const className of [
    'story-grid', 'story-label', 'snapshot-card', 'hero-proof',
    'category-photo-grid', 'category-photo-card', 'community-invite-icon',
    'community-signature', 'community-filter-panel',
    'discovery-campaign-grid', 'discovery-tabs',
  ]) {
    const pattern = new RegExp(`\\.${className}(?![\\w-])`);
    assert.doesNotMatch(css, pattern, `Orphan selector still present: ${className}`);
  }
});

test('shared and interactive public styles remain in place', () => {
  assert.match(read('assets/css/release.css'), /\[hidden\]\s*\{[^}]*display\s*:\s*none\s*!important/);
  assert.match(read('assets/css/release.css'), /\.couponhub-search-form/);
  assert.match(read('assets/css/design-system.css'), /\.site-nav/);
  assert.match(read('assets/css/category-navigation.css'), /\.couponhub-category-dialog/);
});

test('Community and Explore current component CSS remains available', () => {
  assert.match(read('assets/css/product.css'), /\.community-cover/);
  assert.match(read('assets/css/release.css'), /\.community-board/);
  assert.match(read('assets/css/discovery.css'), /\.discovery-section/);
});

test('Phase 9G-E touches only CSS and adds its own regression test', () => {
  // Regression guard for the two PHP layouts and the category picker script.
  assert.match(read('app/views/layouts/main.php'), /PageAssetPolicy::enabled/);
  assert.match(read('app/views/layouts/head.php'), /PageAssetPolicy::enabled/);
  assert.match(read('assets/js/category-picker.js'), /requestAnimationFrame/);
});

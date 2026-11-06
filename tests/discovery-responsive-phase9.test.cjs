'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const home = fs.readFileSync(path.join(root, 'app/views/home.php'), 'utf8');
const community = fs.readFileSync(path.join(root, 'app/views/coupons/index.php'), 'utf8');
const css = fs.readFileSync(path.join(root, 'assets/css/integration.css'), 'utf8');
// Current styles use scoped selectors; do not depend on historical comments.
const phase9 = css;

// Find a media block by its declaration and contents, not by source order.
// The stylesheet contains separate Community and Explore rules at 600px.
function mediaBlockWith(source, maxWidth, selector) {
  const declaration = `@media (max-width: ${maxWidth}px) {`;
  let from = 0;
  while ((from = source.indexOf(declaration, from)) !== -1) {
    const start = from + declaration.length;
    let depth = 1;
    let end = start;
    for (; end < source.length && depth > 0; end++) {
      if (source[end] === '{') depth++;
      if (source[end] === '}') depth--;
    }
    const block = source.slice(start, end - 1);
    if (block.includes(selector)) return block;
    from = end;
  }
  return null;
}

test('home search keeps its PHP form behavior, visible label and accessible icon-only mode', () => {
  assert.match(home, /id="home-filter-form"/);
  assert.match(home, /id="q"[^>]*name="q"/);
  assert.match(home, /type="submit" class="btn btn-lime search-submit couponhub-search-action" aria-label="Khám phá ưu đãi"/);
  assert.match(home, /<span class="search-submit-label">Khám phá<\/span>/);
  assert.match(home, /class="fa-solid fa-magnifying-glass"/);
});

test('home and community search share pill dimensions and control rhythm', () => {
  assert.match(community, /community-search-submit-label">Tìm kiếm<\/span>/);
  assert.match(css, /\.community-search-input > \.community-search-submit\s*\{[^}]*min-height:\s*48px/);
  assert.match(phase9, /\.editorial-home \.discovery-section \.hero-search\.couponhub-search-form\s*\{[^}]*border-radius:\s*999px/);
  assert.match(phase9, /\.editorial-home \.discovery-section \.hero-search \.search-submit\s*\{[^}]*min-width:\s*132px/);
  assert.match(phase9, /\.editorial-home \.discovery-section \.hero-search \.search-submit\s*\{[^}]*border-radius:\s*999px/);
});

test('mobile search stays in one row with accessible icon-only control', () => {
  assert.match(phase9, /\.editorial-home \.discovery-section \.hero-search\.couponhub-search-form\s*\{[^}]*flex-wrap:\s*nowrap/);
  const mobile = mediaBlockWith(phase9, 600, '.editorial-home .discovery-section .hero-search .search-submit {');
  assert.ok(mobile);
  assert.match(mobile, /\.hero-search \.search-submit\s*\{[^}]*width:\s*48px/);
  assert.match(mobile, /\.hero-search \.search-submit-label\s*\{[^}]*position:\s*absolute/);
});

test('category chips wrap and filter hint remains visible', () => {
  assert.match(home, /class="category-strip"/);
  assert.match(phase9, /\.editorial-home \.discovery-section \.category-strip\s*\{[^}]*flex-wrap:\s*wrap/);
  assert.match(phase9, /\.editorial-home \.discovery-section \.category-strip\s*\{[^}]*overflow:\s*visible/);
  assert.match(phase9, /\.editorial-home \.discovery-section \.home-refine > summary > span\s*\{[^}]*display:\s*block/);
});

'use strict';
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.join(__dirname, '..');
const read = relative => fs.readFileSync(path.join(root, relative), 'utf8');

test('Community hero has responsive WebP sources and retains eager priority and fallback', () => {
  const view = read('app/views/coupons/index.php');
  const start = view.indexOf('<img class="community-cover-photo"');
  const end = view.indexOf('decoding="async">', start);
  const img = start !== -1 && end !== -1 ? view.slice(start, end + 'decoding="async">'.length) : null;
  assert.ok(img, 'Community hero missing');
  assert.match(img, /src="<\?= e\(url\('\/assets\/images\/hero\/category-food\.webp'\)\) \?>"/);
  assert.match(img, /srcset="/);
  for (const width of [640, 960, 1280, 1920]) {
    assert.ok(img.includes(`category-food-${width}.webp`), `missing ${width}w source`);
    assert.ok(img.includes(`${width}w`));
  }
  assert.match(img, /category-food\.webp'\)\) \?> 2560w/);
  assert.match(img, /sizes="\(max-width: 767px\)[^"]+"/);
  // Each attribute is contractual; formatting and line wrapping are not.
  for (const [attribute, value] of [['width', '2560'], ['height', '1707'],
    ['fetchpriority', 'high'], ['decoding', 'async']]) {
    assert.match(img, new RegExp(`\\b${attribute}="${value}"`));
  }
  assert.doesNotMatch(img, /loading="lazy"/);
});

test('Responsive hero images exist and substantially reduce mobile transfer', () => {
  const orig = fs.statSync(path.join(root, 'assets/images/hero/category-food.webp')).size;
  for (const width of [640, 960, 1280, 1920]) {
    const file = fs.readFileSync(path.join(root, `assets/images/hero/category-food-${width}.webp`));
    assert.equal(file.toString('ascii', 0, 4), 'RIFF');
    assert.equal(file.toString('ascii', 8, 12), 'WEBP');
    assert.ok(file.length < orig, `${width}w image should be smaller than original`);
  }
  const mobile = fs.statSync(path.join(root, 'assets/images/hero/category-food-640.webp')).size;
  assert.ok(mobile < orig / 5, `mobile delivery insufficiently reduced (${mobile} vs ${orig})`);
});

test('The existing collision controller checks the search submit without adding another scroll listener', () => {
  const picker = read('assets/js/category-picker.js');
  assert.match(picker, /\[data-category-open\], \.community-catalog \.community-search-submit/);
  assert.match(picker, /window\.addEventListener\('scroll', scheduleFloatingCollision, \{ passive: true \}\)/);
  assert.equal((picker.match(/window\.addEventListener\('scroll'/g) || []).length, 1);
  assert.match(picker, /floating\.inert\s*=\s*overlaps/);
  assert.match(read('assets/css/category-navigation.css'), /\.floating-support\.is-category-overlap\s*\{/);
});

test('Primary search control is uncovered while scrolling, and contact CTA returns after overlap ends', () => {
  const callbacks = [];
  const docListeners = new Map();
  const winListeners = new Map();
  const flags = new Set();
  let buttonRect = { left: 395, right: 455, top: 455, bottom: 515 };
  const button = { getBoundingClientRect: () => buttonRect };
  const actions = { getBoundingClientRect: () => ({ left: 390, right: 505, top: 460, bottom: 520 }) };
  const floating = {
    inert: false,
    querySelector: sel => sel === '.floating-support-actions' ? actions : null,
    classList: {
      contains: cls => flags.has(cls),
      toggle: (cls, yes) => yes ? flags.add(cls) : flags.delete(cls),
    },
  };
  const document = {
    readyState: 'loading',
    addEventListener(name, cb) { docListeners.set(name, cb); },
    querySelector(sel) { return sel === '[data-floating-support]' ? floating : null; },
    querySelectorAll(sel) {
      if (sel.includes('.community-catalog .community-search-submit')) return [button];
      return [];
    },
  };
  const window = {
    matchMedia(query) { return { matches: query.includes('991.98px') }; },
    requestAnimationFrame(cb) { callbacks.push(cb); return callbacks.length; },
    addEventListener(name, cb) { winListeners.set(name, cb); },
  };
  vm.runInNewContext(read('assets/js/category-picker.js'), { document, window, HTMLDialogElement: undefined,
    getComputedStyle: () => ({ display: 'inline-flex' }) });

  docListeners.get('DOMContentLoaded')();
  assert.equal(callbacks.length, 1);
  callbacks.shift()();
  assert.equal(floating.inert, true);
  assert.equal(flags.has('is-category-overlap'), true);

  buttonRect = { left: 10, right: 60, top: 70, bottom: 120 };
  for (let i = 0; i < 30; i++) winListeners.get('scroll')();
  assert.equal(callbacks.length, 1, 'scroll burst should produce one geometry check');
  callbacks.shift()();
  assert.equal(floating.inert, false);
  assert.equal(flags.has('is-category-overlap'), false);
});

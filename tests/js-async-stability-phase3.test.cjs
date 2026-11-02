'use strict';

const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../assets/js/app.js'), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));

function createHarness({clipboardWrite = async () => {}} = {}) {
  const handlers = new Map();
  const windowHandlers = new Map();
  const requests = [];
  const navigations = [];
  const rendered = [];
  const historyUrls = [];
  const clocks = new Map();
  let nextClock = 1;
  let currentResults;
  const elementClass = () => ({ add() {}, remove() {}, toggle() {} });
  const makeResults = id => ({
    id,
    querySelector: () => null,
    querySelectorAll: () => [],
    setAttribute() {},
    removeAttribute() {},
    replaceWith(incoming) { currentResults = incoming; rendered.push(incoming.id); },
  });
  currentResults = makeResults('initial');
  const form = {
    values: { q: '' },
    elements: { namedItem: key => ({ value: form.values[key] || '' }) },
    closest(selector) { return selector === '#home-filter-form' ? form : null; },
    querySelector() { return null; },
  };
  const filterForm = {
    values: { q: '' },
    closest(selector) { return selector === '[data-catalog-filter-form]' ? filterForm : null; },
  };
  const search = {
    id: 'q', value: '',
    closest(selector) { return selector === '#home-filter-form' ? form : null; },
    focus() {}, setSelectionRange() {},
  };
  const elements = { 'home-filter-form': form, 'q': search, 'discovery-results': currentResults };
  const document = {
    documentElement: { classList: elementClass() },
    body: { classList: elementClass() },
    getElementById(id) { return id === 'discovery-results' ? currentResults : elements[id] || null; },
    querySelector() { return null; },
    querySelectorAll() { return []; },
    addEventListener(type, cb) {
      if (!handlers.has(type)) handlers.set(type, []);
      handlers.get(type).push(cb);
    },
    dispatchEvent() {},
  };
  const location = { href: 'http://localhost:8003/index.php', origin: 'http://localhost:8003' };
  Object.defineProperty(location, 'href', {
    get() { return this._href || 'http://localhost:8003/index.php'; },
    set(next) { navigations.push(next); this._href = next; },
  });
  const window = {
    location, isSecureContext: true,
    matchMedia: () => ({matches: true}),
    addEventListener(type, cb) {
      if (!windowHandlers.has(type)) windowHandlers.set(type, []);
      windowHandlers.get(type).push(cb);
    },
    dispatchEvent() {},
  };
  const fetch = (url, options) => {
    let resolve;
    let reject;
    const promise = new Promise((res, rej) => { resolve = res; reject = rej; });
    requests.push({ url, options, resolve, reject });
    return promise;
  };
  const context = {
    document, window, navigator: {clipboard: {writeText: clipboardWrite}},
    fetch, URL, URLSearchParams,
    FormData: class { constructor(form) { return new URLSearchParams(form.values); } },
    DOMParser: class { parseFromString(html) { return {querySelector(selector) {
      if (selector === '#discovery-results') return makeResults(html);
      return null;
    }}; } },
    history: {
      pushState(_state, _title, url) { historyUrls.push(url); },
      replaceState(_state, _title, url) { historyUrls.push(url); },
    },
    AbortController, CustomEvent: class {}, Event: class {},
    console: {error() {}},
    setTimeout(fn, ms) { const id = nextClock++; clocks.set(id, {fn, ms}); return id; },
    clearTimeout(id) { clocks.delete(id); },
  };
  vm.runInNewContext(source, context, {filename: 'assets/js/app.js'});
  const fire = async (type, target) => {
    const event = {target, preventDefault() {}, isComposing: false};
    for (const callback of handlers.get(type) || []) await callback(event);
  };
  const submit = async () => fire('submit', form);
  return {
    requests, navigations, rendered, historyUrls, form, filterForm, search, clocks, submit, fire,
    submitFilter: () => fire('submit', filterForm),
    resolveRequest(index, text) { requests[index].resolve({ok: true, text: async () => text}); },
    async advance(ms) {
      for (const [id, timer] of [...clocks]) if (timer.ms === ms) {
        clocks.delete(id);
        timer.fn();
      }
      await flush();
    },
  };
}

test('late stale AJAX responses cannot replace the latest coupon results', async () => {
  const app = createHarness();
  app.filterForm.values.q = 'old';
  await app.submitFilter();
  app.filterForm.values.q = 'new';
  await app.submitFilter();
  assert.equal(app.requests.length, 2);
  assert.equal(app.requests[0].options.signal.aborted, true);

  // Simulate transport that resolves despite its AbortSignal, e.g. a completed response body.
  app.resolveRequest(1, 'new-results');
  await flush();
  app.resolveRequest(0, 'old-results');
  await flush();

  assert.deepEqual(app.rendered, ['new-results']);
  assert.equal(app.navigations.length, 0);
  assert.equal(app.historyUrls.length, 1);
});

test('obsolete network failure does not redirect away from a newer search', async () => {
  const app = createHarness();
  app.filterForm.values.q = 'old';
  await app.submitFilter();
  app.filterForm.values.q = 'new';
  await app.submitFilter();
  app.requests[0].reject(new Error('network interrupted after abort'));
  await flush();
  app.resolveRequest(1, 'new-results');
  await flush();
  assert.deepEqual(app.rendered, ['new-results']);
  assert.deepEqual(app.navigations, []);
});

test('explicit form submission cancels pending debounced search', async () => {
  const app = createHarness();
  app.search.value = 'keyword';
  app.form.values.q = 'keyword';
  await app.fire('input', app.search);
  assert.equal(app.clocks.size, 1);
  await app.submit();
  assert.equal(app.clocks.size, 0);
  await app.advance(350);
  assert.equal(app.requests.length, 1);
});

test('rapid copy clicks keep original label until the latest reset timer completes', async () => {
  const app = createHarness();
  const classes = new Set();
  const label = {textContent: 'Sao chép'};
  const button = {
    dataset: {copyCode: 'SAVE10', couponId: '1'},
    querySelector(selector) { return selector === 'span:last-child' ? label : null; },
    classList: { add(cls) { classes.add(cls); }, remove(cls) { classes.delete(cls); } },
    closest(selector) { return selector === '[data-copy-code]' ? this : null; },
  };
  await app.fire('click', button);
  await flush();
  assert.equal(label.textContent, 'Đã chép');
  await app.fire('click', button);
  await flush();
  assert.equal(app.clocks.size, 1, 'only the latest reset timer should be active');
  await app.advance(1500);
  assert.equal(label.textContent, 'Sao chép');
  assert.equal(classes.has('copied'), false);
});

test('overlapping clipboard promises still leave one reset timer', async () => {
  const pending = [];
  const app = createHarness({clipboardWrite: () => new Promise(resolve => pending.push(resolve))});
  const label = {textContent: 'Sao chép'};
  const button = {
    dataset: {copyCode: 'SAVE20', couponId: '20'},
    querySelector: () => label,
    classList: {add() {}, remove() {}},
    closest(selector) { return selector === '[data-copy-code]' ? this : null; },
  };
  const first = app.fire('click', button);
  const second = app.fire('click', button);
  assert.equal(pending.length, 2);
  pending[0]();
  await first;
  pending[1]();
  await second;
  assert.equal(app.clocks.size, 1);
  await app.advance(1500);
  assert.equal(label.textContent, 'Sao chép');
});

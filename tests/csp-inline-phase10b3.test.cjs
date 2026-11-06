'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');
const root = path.resolve(__dirname, '..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');

const migratedViews = [
  'app/views/campaigns/show.php',
  'app/views/merchant/apply.php',
  'app/views/admin/categories/index.php',
  'app/views/admin/coupons/index.php',
  'app/views/merchant/dashboard.php',
  'app/views/merchant/coupons/index.php',
];

test('active migrated views no longer embed executable script blocks or event handler attributes', () => {
  for (const file of migratedViews) {
    const contents = read(file);
    assert.doesNotMatch(contents, /<script\s*>/i, file);
    assert.doesNotMatch(contents, /\bon(?:click|submit|change)\s*=/i, file);
    assert.match(contents, /\/assets\/js\/csp\//, file);
  }
});

test('campaign filter keeps its asynchronous behavior without dynamic inline opacity styles', () => {
  const view = read('app/views/campaigns/show.php');
  const js = read('assets/js/csp/campaign-filter.js');
  assert.match(view, /csp\/campaign-filter\.js/);
  assert.match(js, /new URL\(form\.action/);
  assert.match(js, /'X-Requested-With': 'XMLHttpRequest'/);
  assert.match(js, /window\.history\.replaceState/);
  assert.match(js, /currentRequest !== requestNumber/);
  assert.match(js, /classList\.add\('opacity-50'\)/);
  assert.match(js, /classList\.remove\('opacity-50'\)/);
  assert.doesNotMatch(js, /\.style\.opacity/);
});

test('all destructive action confirmation messages are preserved as form data, not onclick', () => {
  assert.match(read('app/views/admin/categories/index.php'), /data-confirm-submit="Chỉ xóa được danh mục/);
  assert.match(read('app/views/admin/coupons/index.php'), /data-confirm-submit="Buộc hết hạn coupon #<\?= \$id \?>\?"/);
  assert.match(read('app/views/admin/coupons/index.php'), /data-confirm-submit="Xóa mềm coupon #<\?= \$id \?>\?/);
  assert.match(read('app/views/merchant/coupons/index.php'), /<form[^>]*data-confirm-submit="Ngừng coupon này\?"/);
});

test('confirmation handler cancels rejected submit and allows accepted submit', () => {
  const listeners = {};
  class HTMLFormElement {
    getAttribute(name) { return name === 'data-confirm-submit' ? 'Confirm?' : null; }
  }
  class HTMLSelectElement {}
  const window = { confirm: () => false };
  const document = { addEventListener: (kind, listener) => { listeners[kind] = listener; } };
  vm.runInNewContext(read('assets/js/csp/page-actions.js'), { document, window, HTMLFormElement, HTMLSelectElement });
  const target = new HTMLFormElement();
  let prevented = 0;
  listeners.submit({ target, preventDefault: () => prevented++ });
  assert.equal(prevented, 1);
  window.confirm = () => true;
  listeners.submit({ target, preventDefault: () => prevented++ });
  assert.equal(prevented, 1);
  // Ordinary forms without a confirmation attribute remain untouched.
  target.getAttribute = () => null;
  window.confirm = () => { throw new Error('confirm must not be called'); };
  listeners.submit({ target, preventDefault: () => prevented++ });
  assert.equal(prevented, 1);
});

test('merchant selector uses original form.submit behavior after change', () => {
  const listeners = {};
  class HTMLFormElement {}
  class HTMLSelectElement {
    matches(s) { return s === '[data-auto-submit]'; }
    constructor() { this.form = { submit: () => { this.submits++; } }; this.submits = 0; }
  }
  const document = { addEventListener: (kind, listener) => { listeners[kind] = listener; } };
  vm.runInNewContext(read('assets/js/csp/page-actions.js'), { document, window: {}, HTMLFormElement, HTMLSelectElement });
  const target = new HTMLSelectElement();
  listeners.change({ target });
  assert.equal(target.submits, 1);
});

test('merchant apply toggle is in an external JS file and still handles claim/new modes', () => {
  const source = read('assets/js/csp/merchant-apply.js');
  assert.match(read('app/views/merchant/apply.php'), /csp\/merchant-apply\.js/);
  assert.match(source, /mode\.addEventListener\('change', sync\)/);
  assert.match(source, /claim\.classList\.toggle\('d-none', isNew\)/);
  assert.match(source, /fresh\.classList\.toggle\('d-none', !isNew\)/);
});

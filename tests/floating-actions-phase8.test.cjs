'use strict';
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const read = name => fs.readFileSync(path.join(__dirname, '..', name), 'utf8');

test('footer no longer repeats the jump-to-top action', () => {
  const footer = read('app/views/partials/public-footer.php');
  assert.doesNotMatch(footer, /<a\s+href="#main"\s*>\s*Về đầu trang/i);
  assert.match(footer, /footer-bottom/);
});
test('both layouts share one accessible floating component', () => {
  for (const layout of ['app/views/layouts/head.php', 'app/views/layouts/main.php']) {
    const text = read(layout);
    for (const expected of ['partials/floating-support.php', 'assets/css/floating-actions.css', 'assets/js/floating-support.js']) {
      assert.ok(text.includes(expected), `${layout} missing ${expected}`);
    }
    assert.doesNotMatch(text, /id="back-to-top"/);
  }
  const partial = read('app/views/partials/floating-support.php');
  for (const expected of ['data-floating-support', 'data-contact-trigger', 'data-back-to-top', 'aria-controls="contact-popover"', 'aria-hidden="true" inert']) {
    assert.ok(partial.includes(expected), `missing ${expected}`);
  }
  assert.doesNotMatch(read('assets/js/app.js'), /initFloatingSupport/);
});
test('buttons remain horizontally aligned with accessible states on narrow screens', () => {
  const css = read('assets/css/floating-actions.css');
  assert.match(css, /\.floating-support\s+\.floating-support-actions\s*\{[^}]*flex-direction:\s*row/s);
  assert.match(css, /\.floating-support\s+\.back-to-top\.is-visible/);
  assert.match(css, /:focus-visible/);
  assert.match(css, /safe-area-inset-bottom/);
  assert.match(css, /prefers-reduced-motion:\s*reduce/);
});
test('scroll button and contact popover remain keyboard-operable', () => {
  function makeNode() {
    const listeners = {};
    const attrs = {};
    const flags = new Set();
    return {
      listeners, attrs, flags, tabIndex: -1, inert: false, focusCount: 0,
      classList: { toggle(name, yes) { if (yes) flags.add(name); else flags.delete(name); } },
      getAttribute(name) { return attrs[name] ?? null; },
      setAttribute(name, value) { attrs[name] = value; },
      addEventListener(name, callback) { listeners[name] = callback; },
      focus() { this.focusCount++; },
    };
  }
  const trigger = makeNode(), close = makeNode(), top = makeNode(), panel = makeNode(), option = makeNode(), shell = makeNode();
  trigger.attrs['aria-expanded'] = 'false';
  shell.querySelector = s => ({ '[data-contact-trigger]': trigger, '#contact-popover': panel, '[data-contact-close]': close, '[data-back-to-top]': top })[s] || null;
  shell.contains = node => [trigger,close,top,panel].includes(node);
  panel.querySelector = () => option;
  const docListeners = {}, winListeners = {};
  const document = { querySelector: () => shell, addEventListener: (event, callback) => docListeners[event] = callback };
  let scrollArgs;
  const window = { scrollY: 0, innerHeight: 800, requestAnimationFrame: cb => { cb(); return 1; },
    addEventListener: (event, callback) => winListeners[event] = callback,
    matchMedia: () => ({ matches: false }), scrollTo: options => scrollArgs = options };
  vm.runInNewContext(read('assets/js/floating-support.js'), { document, window });
  assert.equal(panel.inert, true);
  assert.equal(top.tabIndex, -1);
  trigger.listeners.click();
  assert.equal(trigger.attrs['aria-expanded'], 'true');
  assert.equal(option.focusCount, 1);
  docListeners.keydown({ key: 'Escape' });
  assert.equal(panel.inert, true);
  assert.equal(trigger.focusCount, 1);
  trigger.listeners.click();
  panel.listeners.click({ target: { closest: () => option } });
  assert.equal(panel.inert, true);
  assert.equal(trigger.focusCount, 2, 'closing a selected link restores focus outside the inert panel');
  window.scrollY = 650; winListeners.scroll();
  assert.equal(top.tabIndex, 0);
  assert.equal(top.attrs['aria-hidden'], 'false');
  top.listeners.click();
  assert.equal(scrollArgs.top, 0);
  assert.equal(scrollArgs.behavior, 'smooth');
  window.scrollY = 0; winListeners.resize();
  assert.equal(top.tabIndex, -1);
});

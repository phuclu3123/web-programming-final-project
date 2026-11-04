'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const root = path.resolve(__dirname, '..');
const load = name => fs.readFileSync(path.join(root, name), 'utf8');

test('shared category stylesheet is loaded once, inside <head> on both layouts', () => {
  for (const page of ['app/views/layouts/head.php', 'app/views/layouts/main.php']) {
    const html = load(page);
    const marker = '/assets/css/category-navigation.css';
    assert.equal(html.split(marker).length - 1, 2, page + ': href and filemtime should each reference the CSS once');
    assert.ok(html.indexOf(marker) > html.indexOf('<head>'), page);
    assert.ok(html.indexOf(marker) < html.indexOf('</head>'), page);
  }
});

test('category picker coalesces bursts of resize events into one animation frame', () => {
  const callbacks = [];
  const listeners = new Map();
  const window = {
    matchMedia: () => ({ matches: false }),
    requestAnimationFrame(callback) {
      callbacks.push(callback);
      return callbacks.length;
    },
    addEventListener(type, callback) { listeners.set(type, callback); },
  };
  const document = {
    readyState: 'loading',
    addEventListener() {},
    querySelectorAll() { return []; },
    querySelector() { return null; },
  };
  vm.runInNewContext(load('assets/js/category-picker.js'), {window, document, HTMLDialogElement: undefined});
  const resize = listeners.get('resize');
  assert.equal(typeof resize, 'function');
  for (let i = 0; i < 150; i++) resize();
  assert.equal(callbacks.length, 1, '150 resize events should queue only one layout pass');
  callbacks.shift()();
  assert.equal(callbacks.length, 1, 'collision checking may schedule one follow-up frame');
  for (let i = 0; i < 20; i++) resize();
  assert.equal(callbacks.length, 2, 'next burst schedules one additional layout pass');
});

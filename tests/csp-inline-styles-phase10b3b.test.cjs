// Phase 10B-B3B: static CSP-style contract; no DB or DOM required.
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
const active = [
  'app/views/admin/merchants/requests.php',
  'app/views/admin/coupons/index.php',
  'app/views/coupons/saved.php',
  'app/views/coupons/show.php',
  'app/views/dashboard/index.php'
];
test('targeted active templates have no style attributes', () => {
  for (const file of active) {
    assert.doesNotMatch(read(file), /\sstyle\s*=/i, file);
  }
});
test('progress is native, accessible, bounded and carries a dynamic value', () => {
  const html = read('app/views/dashboard/index.php');
  assert.match(html, /<progress\b[^>]*class="member-progress"/);
  assert.match(html, /aria-label="Tiến độ trong cấp hiện tại"/);
  assert.match(html, /value="<\?= \(int\) max\(0, min\(100, \$progress\)\) \?>"/);
  assert.match(html, /max="100"/);
  assert.doesNotMatch(html, /style="width:/);
});
test('progress colors stay in stylesheet and maintain chrome/firefox value styling', () => {
  const css = read('assets/css/member.css');
  assert.match(css, /\.member-progress::-webkit-progress-value/);
  assert.match(css, /\.member-progress::-moz-progress-bar/);
  assert.match(css, /#9ab4ff/);
});
test('CSP utility selectors exist and main MVC layouts load their stylesheet', () => {
  const css = read('assets/css/integration.css');
  for (const name of ['csp-wrap-anywhere', 'csp-max-w-240', 'csp-max-w-640', 'csp-gap-035']) {
    assert.match(css, new RegExp('\\.' + name + '\\s*\\{'));
  }
  for (const name of ['app/views/layouts/head.php', 'app/views/layouts/main.php']) {
    assert.match(read(name), /assets\/css\/integration\.css/);
  }
});
test('saved coupons use Bootstrap gap utilities rather than inline gap styles', () => {
  const html = read('app/views/coupons/saved.php');
  assert.equal((html.match(/\bgap-2\b/g) || []).length >= 3, true);
});

'use strict';
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = p => fs.readFileSync(path.join(root, p), 'utf8');

test('auth CSS has one authoritative field-icon and password-toggle rule', () => {
  const css = read('assets/css/auth/auth.css');
  assert.equal((css.match(/#loginModal \.auth-field-icon\s*\{\s*display:/g) || []).length, 1);
  assert.equal((css.match(/#loginModal \[data-password-toggle\]\s*\{\s*display:\s*grid;/g) || []).length, 1);
  assert.doesNotMatch(css, /\.auth-icon-sprite\s*\{/);
  assert.match(css, /#loginModal \.auth-field-icon\s*\{[^}]*min-width:\s*20px;/s);
  assert.match(css, /#loginModal \[data-password-toggle\]\s*\{[^}]*min-width:\s*44px;[^}]*cursor:\s*pointer;/s);
});

test('font icons keep their accessible eye-toggle states without SVG styling', () => {
  const css = read('assets/css/auth/auth.css');
  assert.equal((css.match(/#loginModal \.auth-password-eye\s*\{/g) || []).length, 1);
  assert.doesNotMatch(css, /auth-password-eye\s*\{[^}]*\bfill\s*:/s);
  assert.match(css, /\[data-password-toggle\] \.auth-password-eye--hide\s*\{\s*display:\s*none;/);
  assert.match(css, /\[data-password-toggle\]\[aria-pressed="true"\] \.auth-password-eye--hide\s*\{\s*display:\s*inline-flex;/);
  for (const p of ['app/views/auth/login-form.php','app/views/auth/register-form.php']) {
    const html = read(p);
    assert.match(html, /fa-solid fa-eye auth-password-eye auth-password-eye--show/);
    assert.match(html, /aria-pressed="false"/);
  }
});

test('editorial header resets do not match generic image-adjacent div and header', () => {
  const css = read('assets/css/release.css');
  assert.doesNotMatch(css, /(^|\n)\s*(?:div|header):has\(\+/m);
  assert.match(css, /\.article-reading > div:has\(\+ img\)/);
  assert.match(css, /\.guide-page > \.guide-masthead:has\(\+ img\)/);
  assert.match(read('app/views/articles/show.php'), /class="[^\"]*\barticle-reading\b[^\"]*"/);
  assert.match(read('app/views/articles/guide.php'), /class="guide-page"/);
  assert.match(read('app/views/articles/guide.php'), /class="guide-masthead"/);
});

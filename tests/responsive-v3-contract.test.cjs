const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('auth close uses a Font Awesome xmark and remains an accessible button', () => {
  const modal = read('app/views/auth/auth-modal.php');
  const css = read('assets/css/auth/auth.css');
  assert.match(modal, /data-auth-close[^>]*aria-label="Đóng cửa sổ xác thực"/);
  assert.match(modal, /fa-solid fa-xmark auth-close-icon/);
  assert.match(css, /#loginModal\.auth-dialog \.auth-close:focus-visible/);
  assert.match(css, /#loginModal\.auth-dialog \.auth-close:active/);
  assert.match(css, /\.login-input > \.auth-field-icon\s*\{[^}]*margin-left:\s*8px !important;/s);
});

test('responsive category directory does not force two columns on tablets', () => {
  const css = read('assets/css/integration.css');
  assert.match(css, /@media \(max-width:\s*1199\.98px\)\s*\{[\s\S]*?\.discovery-page \.category-directory\s*\{[^}]*grid-template-columns:\s*minmax\(0, 1fr\)/);
  assert.match(css, /@media \(max-width:\s*767\.98px\)\s*\{[\s\S]*?\.discovery-page \.category-directory-visual\s*\{[^}]*display:\s*none/);
});

test('community search has one semantic submit button and explicit search icon', () => {
  const html = read('app/views/coupons/index.php');
  const css = read('assets/css/integration.css');
  assert.match(html, /class="btn btn-brand community-search-submit"\s*type="submit" aria-label="Tìm kiếm ưu đãi"/);
  assert.match(html, /community-search-submit-label">Tìm kiếm<\/span>\s*<\?= icon\('search'\)/);
  assert.match(css, /\.community-search-input > \.community-search-submit\s*\{[^}]*min-height:\s*48px/);
  assert.match(css, /\.community-search-input > \.community-search-submit:focus-visible/);
});

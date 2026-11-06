'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.resolve(__dirname, '..');
const read = rel => fs.readFileSync(path.join(root, rel), 'utf8');
const policy = read('app/core/PageAssetPolicy.php');
const head = read('app/views/layouts/head.php');
const main = read('app/views/layouts/main.php');
const selected = [
  'discovery.css', 'editorial.css', 'category-navigation.css',
  'discovery.js', 'editorial.js', 'hero-carousel.js',
  'campaign-clock.js', 'category-picker.js',
];

test('route-aware assets are controlled by exact view names, not URL fragments', () => {
  assert.match(policy, /class PageAssetPolicy/);
  assert.match(policy, /array_key_exists\(\$view, self::PROFILES\)/);
  assert.match(policy, /return true;\s*}\s*return in_array\(\$asset, self::PROFILES\[\$view\], true\)/);
  assert.match(policy, /'coupons\/index'/);
  assert.match(policy, /'categories\/index'/);
  assert.match(policy, /'home'/);
  assert.doesNotMatch(policy, /REQUEST_URI|parse_url|strpos/);
});

test('only explicitly scoped assets are conditional: global auth, header, Bootstrap, FA remain', () => {
  for (const layout of [head, main]) {
    for (const asset of ['discovery.css','editorial.css','category-navigation.css', 'discovery.js','editorial.js','campaign-clock.js','category-picker.js']) {
      assert.match(layout, new RegExp('PageAssetPolicy::enabled\\([^;]*\\x27'+asset.replace('.', '\\.')+'\\x27\\)'));
      assert.ok(layout.includes('assets/' + (asset.endsWith('.css') ? 'css/' : 'js/') + asset));
    }
    assert.match(layout, /fontawesome\/css\/all\.min\.css/);
    assert.match(layout, /bootstrap@5\.3\.8/);
    assert.match(layout, /assets\/css\/member\.css/);
    assert.match(layout, /assets\/css\/release\.css/);
    assert.match(layout, /assets\/css\/product\.css/);
    assert.match(layout, /assets\/js\/floating-support\.js/);
    assert.match(layout, /assets\/js\/coupon-events\.js/);
  }
  assert.match(head, /PageAssetPolicy::enabled\([^;]*'hero-carousel\.js'\)/);
  assert.match(main, /\$pageScripts\s*\?\?\s*\[\]/);
});

test('page profiles preserve category picker only for the 3 public catalog views', () => {
  const coupon = policy.match(/'coupons\/index'\s*=>\s*\[([^]*?)\],/)[1];
  const categories = policy.match(/'categories\/index'\s*=>\s*\[([^]*?)\],/)[1];
  const home = policy.match(/'home'\s*=>\s*\[([^]*?)\],/)[1];
  for (const [profile, kind] of [[coupon,'coupon'],[categories,'categories'],[home,'home']]) {
    assert.match(profile, /'category-navigation\.css'/);
    assert.match(profile, /'category-picker\.js'/);
    assert.match(profile, /'discovery\.css'/);
    assert.match(profile, /'editorial\.css'/);
  }
  assert.doesNotMatch(coupon, /'campaign-clock\.js'|'discovery\.js'|'editorial\.js'/);
  assert.match(categories, /'editorial\.js'/);
  assert.doesNotMatch(categories, /'campaign-clock\.js'|'discovery\.js'/);
  for (const name of ['shop','admin','error']) assert.match(policy, new RegExp("'"+name+"'\\s*=>\\s*\\[\\]"));
});

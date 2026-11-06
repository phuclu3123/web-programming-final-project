'use strict';
const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const http = require('node:http');
const os = require('node:os');
const { parseArgs, publicAssets, assetInventory, main } = require('../scripts/performance-baseline-phase9gc.cjs');
const read = f => fs.readFileSync(path.join(__dirname, '..', f), 'utf8');

function ruleBody(css, selector) {
  const escaped = selector.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return css.match(new RegExp(escaped + '\\s*\\{([^}]+)\\}'))?.[1] || '';
}

test('community search has only the outer focus ring, not a nested input border', () => {
  const release = read('assets/css/release.css');
  const integration = read('assets/css/integration.css');
  assert.doesNotMatch(release, /\.community-catalog \.community-search-input > input\[type="search"\]/);
  assert.match(release, /\.editorial-home \.hero-search > input\[name="q"\]/);
  const input = ruleBody(integration, '.community-catalog .community-board .community-search-input input');
  assert.match(input, /border:\s*0\s*!important/);
  assert.match(input, /box-shadow:\s*none\s*!important/);
  assert.match(input, /background:\s*transparent\s*!important/);
  assert.match(integration, /\.community-catalog \.community-board \.community-search-input:focus-within\s*\{[^}]*outline:\s*2px solid #3159d0/);
  assert.match(read('app/views/coupons/index.php'), /fa-solid fa-magnifying-glass/);
});

test('baseline inventory lists only existing local CSS/JS and estimates gzip', () => {
  const inventory = assetInventory();
  assert.ok(inventory.css.bytes > inventory.css.gzip_estimate_bytes);
  assert.ok(inventory.js.bytes > inventory.js.gzip_estimate_bytes);
  assert.ok(inventory.top_files.some(item => item.file === 'assets/css/editorial.css'));
});

test('HTTP baseline blocks nonlocal hosts and never follows third-party assets', () => {
  assert.throws(() => parseArgs(['--http', '--base', 'https://example.com']), /local HTTP/);
  assert.equal(parseArgs(['--root', path.join(__dirname, '..')]).root, path.join(__dirname, '..'));
  const html = `<base href="/app/"><link rel="stylesheet" href="/assets/css/a.css?v=1"><script defer src="/assets/js/app.js"></script><script src="https://cdn.example.org/ext.js"></script>`;
  const assets = publicAssets(html, 'http://127.0.0.1:8003/');
  assert.deepEqual(assets.map(a => a.path), ['/assets/css/a.css', '/assets/js/app.js']);
});

test('anonymous read-only HTTP baseline records response samples and asset status', async () => {
  const server = http.createServer((req, res) => {
    if (req.url.startsWith('/assets/css/')) {
      res.writeHead(200, { 'Content-Type': 'text/css', 'Cache-Control': 'public, max-age=60' });
      return res.end('.demo{color:#123}');
    }
    res.writeHead(200, { 'Content-Type': 'text/html' });
    res.end('<html><link rel="stylesheet" href="/assets/css/demo.css"><main>OK</main></html>');
  });
  await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
  const out = path.join(os.tmpdir(), `couponhub-phase9gc-${process.pid}-${Date.now()}.json`);
  try {
    const base = `http://127.0.0.1:${server.address().port}`;
    const report = await main(['--http', '--base', base, '--out', out]);
    assert.equal(report.http.routes.length, 3);
    assert.ok(report.http.routes.every(route => route.status === 200));
    assert.equal(report.http.assets.length, 1);
    assert.equal(report.http.assets[0].status, 200);
    assert.ok(!fs.readFileSync(out, 'utf8').includes('<main>OK</main>'));
  } finally {
    await new Promise(resolve => server.close(resolve));
    fs.rmSync(out, { force: true });
  }
});

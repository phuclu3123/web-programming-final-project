'use strict';
/** Read-only baseline recorder. Node >= 18, no npm dependencies. */
const fs = require('node:fs');
const path = require('node:path');
const zlib = require('node:zlib');
const { performance } = require('node:perf_hooks');

const defaultRoot = path.resolve(__dirname, '..');
const routes = ['/', '/coupons', '/categories'];
const assetRe = /\.(?:css|js)$/i;

function parseArgs(argv) {
  const opt = { http: false, base: 'http://127.0.0.1:8003', out: null, root: defaultRoot };
  for (let i = 0; i < argv.length; i++) {
    if (argv[i] === '--http') opt.http = true;
    else if (argv[i] === '--static') opt.http = false;
    else if (argv[i] === '--base' || argv[i] === '--out' || argv[i] === '--root') {
      if (!argv[i + 1]) throw new Error(`Missing value for ${argv[i]}`);
      opt[argv[i].slice(2)] = argv[++i];
    } else throw new Error(`Unrecognized option: ${argv[i]}`);
  }
  opt.root = path.resolve(opt.root);
  if (!fs.existsSync(path.join(opt.root, 'app/views/coupons/index.php'))) throw new Error('--root must point at the CouponHub source directory');
  if (opt.http) {
    const u = new URL(opt.base);
    if (u.protocol !== 'http:' || !['127.0.0.1', 'localhost', '[::1]'].includes(u.hostname)) {
      throw new Error('--base must be a local HTTP address (localhost / 127.0.0.1 / ::1)');
    }
    opt.base = u.origin;
  }
  return opt;
}

function assetInventory(root = defaultRoot) {
  const files = [];
  for (const type of ['css', 'js']) {
    const dir = path.join(root, 'assets', type);
    if (!fs.existsSync(dir)) continue;
    for (const f of fs.readdirSync(dir)) {
      if (!f.endsWith(`.${type}`)) continue;
      const bytes = fs.readFileSync(path.join(dir, f));
      files.push({ file: `assets/${type}/${f}`, bytes: bytes.length, gzip_estimate_bytes: zlib.gzipSync(bytes).length });
    }
  }
  files.sort((a, b) => b.bytes - a.bytes);
  const sum = (key, list) => list.reduce((n, f) => n + f[key], 0);
  return {
    scope: 'Local source files, not total transferred bytes or page weight',
    css: { files: files.filter(f => f.file.endsWith('.css')).length, bytes: sum('bytes', files.filter(f => f.file.endsWith('.css'))), gzip_estimate_bytes: sum('gzip_estimate_bytes', files.filter(f => f.file.endsWith('.css'))) },
    js: { files: files.filter(f => f.file.endsWith('.js')).length, bytes: sum('bytes', files.filter(f => f.file.endsWith('.js'))), gzip_estimate_bytes: sum('gzip_estimate_bytes', files.filter(f => f.file.endsWith('.js'))) },
    top_files: files.slice(0, 12),
  };
}

function getAttribute(tag, attr) {
  const m = tag.match(new RegExp(`(?:^|\\s)${attr}\\s*=\\s*(?:"([^"]*)"|'([^']*)'|([^\\s>]+))`, 'i'));
  return m ? (m[1] ?? m[2] ?? m[3]) : null;
}
function publicAssets(html, pageUrl) {
  const htmlBase = html.match(/<base\b[^>]*>/i);
  const baseHref = htmlBase && getAttribute(htmlBase[0], 'href');
  let base = pageUrl;
  if (baseHref) {
    try { base = new URL(baseHref, pageUrl).href; } catch { /* use pageUrl */ }
  }
  const paths = new Map();
  for (const m of html.matchAll(/<(?:link|script)\b[^>]*>/gi)) {
    const tag = m[0];
    const isCss = /^<link\b/i.test(tag) && /(?:^|\s)stylesheet(?:\s|$)/i.test(getAttribute(tag, 'rel') || '');
    const isJs = /^<script\b/i.test(tag);
    if (!isCss && !isJs) continue;
    const ref = getAttribute(tag, isCss ? 'href' : 'src');
    if (!ref) continue;
    let u;
    try { u = new URL(ref, base); } catch { continue; }
    if (u.origin !== new URL(pageUrl).origin || !assetRe.test(u.pathname)) continue;
    paths.set(u.href, { url: u.href, path: u.pathname, versioned: !!u.search });
  }
  return [...paths.values()].slice(0, 48);
}

async function probe(url, readBody) {
  const start = performance.now();
  const response = await fetch(url, { redirect: 'manual', signal: AbortSignal.timeout(8000), headers: { 'Accept': 'text/html, text/css, application/javascript;q=0.9, */*;q=0.8' } });
  const ttfb = performance.now() - start; // rough response-header latency, NOT web vital TTFB
  const buf = Buffer.from(await response.arrayBuffer());
  const total = performance.now() - start;
  return {
    status: response.status,
    response_header_ms: +ttfb.toFixed(1),
    total_fetch_ms: +total.toFixed(1),
    decoded_body_bytes: buf.length, // fetch automatically decompresses, not network transfer size
    cache_control: response.headers.get('cache-control') || null,
    content_encoding: response.headers.get('content-encoding') || null,
    ...(readBody ? { body: buf.toString('utf8') } : {}),
  };
}
function median(nums) {
  const sorted = nums.slice().sort((a, b) => a - b);
  return sorted[Math.floor(sorted.length / 2)];
}
async function httpBaseline(base) {
  const summary = { scope: 'Anonymous, read-only HTTP GET from Node on localhost (not browser performance)', base, routes: [], assets: [] };
  const resources = new Map();
  for (const route of routes) {
    const url = new URL(route, base).href;
    const samples = [];
    try {
      for (let i = 0; i < 3; i++) samples.push(await probe(url, i === 0));
      const first = samples[0];
      if (first.status === 200 && first.body) for (const asset of publicAssets(first.body, url)) resources.set(asset.url, asset);
      summary.routes.push({ route, status: first.status, median_header_ms: median(samples.map(s => s.response_header_ms)), median_total_ms: median(samples.map(s => s.total_fetch_ms)), decoded_html_bytes: first.decoded_body_bytes, cache_control: first.cache_control });
    } catch (err) { summary.routes.push({ route, error: err.message }); }
  }
  for (const asset of [...resources.values()].slice(0, 48)) {
    try {
      const data = await probe(asset.url, false);
      summary.assets.push({ path: asset.path, versioned: asset.versioned, ...data });
    } catch (err) { summary.assets.push({ path: asset.path, error: err.message }); }
  }
  return summary;
}
async function main(args = process.argv.slice(2)) {
  const options = parseArgs(args);
  const report = {
    tool: 'CouponHub Phase 9G-C baseline v1',
    collected_at: new Date().toISOString(),
    inventory: assetInventory(options.root),
    ...(options.http ? { http: await httpBaseline(options.base) } : {}),
    limitations: [
      'LCP/CLS/INP require Chrome DevTools or Lighthouse and are NOT measured by this script.',
      'CSS/JS gzip values are local estimates, not actual wire transfer sizes.',
      'HTTP samples use anonymous sequential Node fetch, not browser caching, parallel downloads, or user interactions.',
      'No cookies or authentication data are sent or stored; dynamic responses are not saved.',
    ],
  };
  if (options.out) {
    fs.mkdirSync(path.dirname(path.resolve(options.out)), { recursive: true });
    fs.writeFileSync(path.resolve(options.out), JSON.stringify(report, null, 2) + '\n');
    process.stdout.write(`Saved: ${path.resolve(options.out)}\n`);
  } else process.stdout.write(JSON.stringify(report, null, 2) + '\n');
  process.stdout.write(`CSS: ${report.inventory.css.bytes} bytes; JS: ${report.inventory.js.bytes} bytes.\n`);
  if (report.http) process.stdout.write(`HTTP: ${report.http.routes.map(x => `${x.route}=${x.status || 'ERR'}`).join(' ')}; assets=${report.http.assets.length}\n`);
  return report;
}
if (require.main === module) {
  main().catch(err => { console.error(`Baseline failed: ${err.message}`); process.exitCode = 1; });
}
module.exports = { parseArgs, publicAssets, assetInventory, median, main };

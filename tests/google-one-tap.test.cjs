const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/auth/google-one-tap.js'), 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));

function setup({ deferred = false, redirect = '/dashboard', enabled = true, consent = true } = {}) {
    const events = {}, requests = [], scripts = [], destinations = [], initialized = [];
    const message = { dataset: {}, hidden: true }, csrf = { value: 'session-csrf' }, returns = { value: '/saved-coupons' };
    const view = { hidden: false };
    const form = { querySelector: selector => selector === '.auth-message' ? message : selector === '[name="csrf"]' ? csrf : returns };
    const modal = { open: false, dataset: enabled ? { oneTapStart: '/api/oauth/google-one-tap/start', oneTapLogin: '/api/oauth/google-one-tap' } : {},
        querySelector: selector => selector === '#loginForm' ? form : view,
        addEventListener(name, handler) { (events[name] ||= []).push(handler); } };
    const googleId = { initialize(options) { initialized.push(options); }, prompts: 0, cancels: 0,
        prompt() { this.prompts++; }, cancel() { this.cancels++; } };
    let release;
    const delayed = new Promise(resolve => { release = resolve; });
    const window = { couponhubCookieConsent: { externalSignIn: consent }, addEventListener(name, handler) { (events[name] ||= []).push(handler); }, location: { origin: 'http://localhost:8000', assign(url) { destinations.push(url); } } };
    vm.runInNewContext(source, { window, URL, URLSearchParams, Date, Promise,
        document: { baseURI: 'http://localhost:8000/', getElementById: () => modal,
            createElement: () => ({}), head: { appendChild(script) { scripts.push(script); window.google = { accounts: { id: googleId } }; script.onload(); } } },
        fetch: async (url, options) => {
            requests.push({ url, options });
            if (url.endsWith('/start') && deferred) await delayed;
            return { ok: true, json: async () => url.endsWith('/start')
                ? { success: true, state: 'intent', nonce: 'nonce', client_id: 'client', expires: Date.now() / 1000 + 600 }
                : { success: true, redirect } };
        } });
    const emit = (name, detail = {}) => { for (const handler of events[name] || []) handler({ detail }); };
    return { modal, view, requests, scripts, initialized, googleId, destinations, message, returns, release, emit, window,
        open() { modal.open = true; emit('couponhub:auth-open', { view: 'login' }); },
        close() { modal.open = false; emit('close'); } };
}
test('SDK and nonce request start only when login opens; Google owns prompt rendering', async () => {
    const app = setup();
    assert.equal(app.requests.length, 0); assert.equal(app.scripts.length, 0);
    app.open(); await settle();
    assert.equal(app.scripts[0].src, 'https://accounts.google.com/gsi/client');
    assert.equal(app.googleId.prompts, 1);
    assert.equal(app.initialized[0].nonce, 'nonce');
    assert.equal(app.initialized[0].auto_select, false);
    assert.equal(app.requests[0].options.body.get('csrf'), 'session-csrf');
    assert.equal(app.requests[0].options.credentials, 'same-origin');
    app.close(); app.open(); await settle();
    assert.equal(app.initialized.length, 1); assert.equal(app.requests.length, 1);
    assert.equal(app.googleId.prompts, 2);
});
test('closing while preparation is in flight never opens a late prompt', async () => {
    const app = setup({ deferred: true }); app.open(); app.close(); app.release(); await settle();
    assert.equal(app.googleId.prompts, 0);
});
test('credential POST includes CSRF/current return and follows server role redirect', async () => {
    const app = setup(); app.open(); await settle(); app.returns.value = '/coupons/2';
    await app.initialized[0].callback({ credential: 'signed-provider-token' });
    const request = app.requests.at(-1);
    assert.equal(request.options.body.get('credential'), 'signed-provider-token');
    assert.equal(request.options.body.get('state'), 'intent');
    assert.equal(request.options.body.get('return_url'), '/coupons/2');
    assert.equal(request.options.body.get('csrf'), 'session-csrf');
    assert.equal(new URL(request.url).search, '');
    assert.deepEqual(app.destinations, ['http://localhost:8000/dashboard']);
});
test('changing to registration cancels picker and ignores a stale callback', async () => {
    const app = setup(); app.open(); await settle(); app.view.hidden = true;
    app.emit('couponhub:auth-view', { view: 'register' });
    await app.initialized[0].callback({ credential: 'stale-token' });
    assert.ok(app.googleId.cancels > 0); assert.equal(app.requests.length, 1);
});
test('untrusted redirect is rejected and normal login stays available', async () => {
    const app = setup({ redirect: 'https://evil.test' }); app.open(); await settle();
    await app.initialized[0].callback({ credential: 'token' });
    assert.equal(app.destinations.length, 0); assert.equal(app.message.dataset.state, 'error');
});
test('missing configuration makes no SDK or API calls', async () => {
    const app = setup({ enabled: false }); app.open(); await settle();
    assert.equal(app.scripts.length, 0); assert.equal(app.requests.length, 0);
});
test('duplicate SDK credentials cannot submit the same intent twice', async () => {
    const app = setup(); app.open(); await settle();
    await app.initialized[0].callback({ credential: 'token' });
    await app.initialized[0].callback({ credential: 'token' });
    app.close(); app.open(); await settle();
    assert.equal(app.requests.length, 2);
    assert.equal(app.destinations.length, 1);
    assert.equal(app.googleId.prompts, 1);
});

test('no consent loads no optional Google SDK; consent and withdrawal apply while login is open', async () => {
    const app = setup({ consent: false }); app.open(); await settle();
    assert.equal(app.requests.length, 0); assert.equal(app.scripts.length, 0);
    app.window.couponhubCookieConsent.externalSignIn = true;
    app.emit('couponhub:cookie-consent'); await settle();
    assert.equal(app.googleId.prompts, 1);
    app.window.couponhubCookieConsent.externalSignIn = false;
    app.emit('couponhub:cookie-consent');
    await app.initialized[0].callback({ credential: 'late-credential' });
    assert.equal(app.requests.length, 1); assert.equal(app.destinations.length, 0);
    assert.ok(app.googleId.cancels > 0);
});

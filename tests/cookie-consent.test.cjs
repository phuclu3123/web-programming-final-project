const { test } = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const source = fs.readFileSync(require('node:path').join(__dirname, '../assets/js/cookie-consent.js'), 'utf8');
function setup({ saved = null, visited = false, blocked = false } = {}) {
    const values = new Map(saved ? [['couponhub_cookie_consent_v1', JSON.stringify(saved)]] : []);
    const session = new Map(visited ? [['couponhub_cookie_notice_v1', '1']] : []);
    const buttons = ['necessary', 'all'].map(choice => ({ dataset: { cookieChoice: choice }, addEventListener(name, fn) { this.click = fn; } }));
    const feedback = { textContent: '' }, summary = { focus() { this.focused = true; } };
    const notice = { hidden: true, querySelectorAll: () => buttons, querySelector: s => s === 'summary' ? summary : feedback };
    const settings = { addEventListener(name, fn) { this.click = fn; } }, events = [];
    const storage = map => ({ getItem: key => { if (blocked) throw Error('blocked'); return map.get(key) ?? null; }, setItem: (key, value) => { if (blocked) throw Error('blocked'); map.set(key, value); } });
    const window = { dispatchEvent(event) { events.push(event); } };
    vm.runInNewContext(source, { window, Date, JSON, Number, localStorage: storage(values), sessionStorage: storage(session),
        CustomEvent: class { constructor(name, detail) { this.type = name; this.detail = detail.detail; } },
        document: { querySelector: () => notice, querySelectorAll: () => [settings] } });
    return { notice, buttons, window, values, session, settings, events, feedback, summary };
}
test('first visit defaults to necessary cookies and shows consent notice', () => {
    const app = setup(); assert.equal(app.notice.hidden, false);
    assert.equal(app.window.couponhubCookieConsent.externalSignIn, false);
    assert.equal(app.values.size, 0);
});
test('explicit accept persists a versioned choice and hides notice', () => {
    const app = setup(); app.buttons[1].click();
    assert.equal(app.window.couponhubCookieConsent.externalSignIn, true);
    assert.equal(app.notice.hidden, true); assert.equal(app.session.size, 1);
    assert.equal(JSON.parse(app.values.get('couponhub_cookie_consent_v1')).externalSignIn, true);
});
test('necessary-only withdrawal takes effect and settings can reopen', () => {
    const app = setup({ saved: { version: 1, externalSignIn: true, updatedAt: Date.now() }, visited: true });
    assert.equal(app.notice.hidden, true); app.settings.click();
    assert.equal(app.notice.hidden, false); assert.equal(app.summary.focused, true);
    app.buttons[0].click(); assert.equal(app.window.couponhubCookieConsent.externalSignIn, false);
    assert.equal(app.events.at(-1).detail.externalSignIn, false);
});
test('new visit reminds visitor; navigation in acknowledged visit does not nag', () => {
    const saved = { version: 1, externalSignIn: false, updatedAt: Date.now() };
    assert.equal(setup({ saved }).notice.hidden, false);
    assert.equal(setup({ saved, visited: true }).notice.hidden, true);
});
test('expired, malformed or future choice never silently opts visitor in', () => {
    for (const saved of [ { version: 0, externalSignIn: true, updatedAt: Date.now() }, { version: 1, externalSignIn: 'yes', updatedAt: Date.now() },
        { version: 1, externalSignIn: true, updatedAt: 1 }, { version: 1, externalSignIn: true, updatedAt: Date.now() + 60000 } ]) {
        assert.equal(setup({ saved }).window.couponhubCookieConsent.externalSignIn, false);
    }
});
test('blocked browser storage preserves essential login and reports memory-only choice', () => {
    const app = setup({ blocked: true }); app.buttons[0].click();
    assert.equal(app.window.couponhubCookieConsent.necessary, true);
    assert.match(app.feedback.textContent, /không cho phép lưu/);
});

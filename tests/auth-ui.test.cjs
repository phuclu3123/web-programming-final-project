// ---- KIỂM TRA JS BẰNG DOM/FETCH GIẢ: không gọi API/MySQL hoặc nhà cung cấp thật ----
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/auth/auth.js'), 'utf8');

function setup(fetchResult, active = 'login') {
    const input = (id, name, type, value, autocomplete = '') => ({
        id, name, type, value, autocomplete, required: type !== 'hidden', maxLength: -1,
        validity: { typeMismatch: false }, attrs: {},
        setAttribute(k, v) { this.attrs[k] = v; }, removeAttribute(k) { delete this.attrs[k]; }, focus() {},
    });
    const csrf = () => input('', 'csrf', 'hidden', 'test-csrf');
    const loginFields = [csrf(), input('', 'return_url', 'hidden', 'admin.php?section=reports'),
        input('loginAccount', 'account', 'text', ' demo_user ', 'username'),
        input('loginPassword', 'password', 'password', ' Demo123! ', 'current-password')];
    const registerFields = [csrf(), input('registerFullName', 'full_name', 'text', 'Demo', 'name'),
        input('registerUsername', 'username', 'text', 'demo_user', 'username'),
        input('registerEmail', 'email', 'email', 'demo@example.com', 'email'),
        input('registerPhone', 'phone', 'tel', '+84901234567', 'tel'),
        input('registerPassword', 'password', 'password', 'Demo123!', 'new-password'),
        input('registerPasswordConfirm', 'password_confirmation', 'password', 'Demo123!', 'new-password')];
    function form(id, inputs) {
        const message = { dataset: {}, hidden: true, textContent: '', scrollIntoView() {} };
        const button = { type: 'submit', textContent: 'Submit', disabled: false };
        return { id, inputs, message, button, elements: [...inputs, button], handlers: {},
            querySelector(s) {
                if (s === '.auth-message') return message;
                if (s.startsWith('label')) return { textContent: 'Field' };
                return inputs.find(i => '#' + i.id === s);
            }, querySelectorAll() { return inputs; },
            addEventListener(k, fn) { this.handlers[k] = fn; }, reset() { inputs.filter(i => i.type !== 'hidden').forEach(i => i.value = ''); },
        };
    }
    const forms = [form('loginForm', loginFields), form('registerForm', registerFields),
        form('forgotPasswordForm', [input('forgotPasswordEmail', 'email', 'email', 'demo@example.com', 'email')])];
    const views = ['login', 'register', 'forgot-password'].map((name, i) => ({
        dataset: { authView: name }, hidden: name !== active,
        querySelector(s) { return s === '.modal-title' ? { id: name + 'Title' } : forms[i].inputs.find(i => i.type !== 'hidden'); },
    }));
    const requests = [], destinations = [];
    const modal = {
        classList: { contains() { return true; } },
        querySelector(s) {
            return s === '#loginForm' ? forms[0] : loginFields.find(i => '#' + i.id === s);
        }, querySelectorAll(s) { return s === 'form' ? forms : s === '[data-auth-view]' ? views : []; },
        addEventListener() {}, setAttribute() {},
    };
    vm.runInNewContext(source, {
        document: { getElementById() { return modal; } }, URL, TextEncoder,
        window: { location: { href: 'http://localhost:8000/login.php', origin: 'http://localhost:8000', assign(url) { destinations.push(url); } } },
        FormData: class { constructor(form) { this.fields = Object.fromEntries(form.inputs.map(i => [i.name, i.value])); } },
        fetch: async (url, options) => { requests.push({ url, options }); return fetchResult(); },
    });
    return { forms, views, requests, destinations,
        submit(i) { return forms[i].handlers.submit({ preventDefault() {} }); } };
}
const response = (body, ok = true) => ({ ok, json: async () => body });

test('login posts CSRF and password unchanged, follows role redirect', async () => {
    const app = setup(() => response({ success: true, redirect: 'admin.php?section=reports' }));
    await app.submit(0);
    assert.equal(app.requests[0].url, 'api/login.php');
    assert.equal(app.requests[0].options.method, 'POST');
    assert.equal(app.requests[0].options.body.fields.csrf, 'test-csrf');
    assert.equal(app.requests[0].options.body.fields.account, 'demo_user');
    assert.equal(app.requests[0].options.body.fields.password, ' Demo123! ');
    assert.deepEqual(app.destinations, ['http://localhost:8000/admin.php?section=reports']);
    assert.equal(app.forms[0].button.disabled, false);
});
test('server errors, failed JSON and network failure leave inline feedback and unlock', async () => {
    for (const result of [() => response({ success: false, message: 'Rejected' }),
        () => response({ success: false, message: 'CSRF' }, false),
        () => ({ ok: true, json: async () => { throw new Error('Invalid JSON'); } }),
        () => { throw new Error('Offline'); }]) {
        const app = setup(result);
        await app.submit(0);
        assert.equal(app.forms[0].message.dataset.state, 'error');
        assert.equal(app.forms[0].button.disabled, false);
        assert.equal(app.destinations.length, 0);
    }
});
test('external redirect is not followed', async () => {
    const app = setup(() => response({ success: true, redirect: 'https://evil.example/' }));
    await app.submit(0);
    assert.equal(app.destinations.length, 0);
    assert.equal(app.forms[0].message.dataset.state, 'error');
});
test('duplicate submit only sends once', async () => {
    let finish;
    const pending = new Promise(resolve => finish = resolve);
    const app = setup(() => pending);
    const first = app.submit(0);
    await app.submit(0);
    assert.equal(app.requests.length, 1);
    finish(response({ success: false, message: 'Rejected' }));
    await first;
    assert.equal(app.forms[0].button.disabled, false);
});
test('register returns to login with username, without password or redirect', async () => {
    const app = setup(() => response({ success: true, message: 'Created' }), 'register');
    await app.submit(1);
    assert.equal(app.requests[0].url, 'api/register.php');
    assert.equal(app.views[0].hidden, false);
    assert.equal(app.forms[0].inputs.find(i => i.name === 'account').value, 'demo_user');
    assert.equal(app.forms[0].inputs.find(i => i.name === 'password').value, '');
    assert.equal(app.forms[0].message.dataset.state, 'success');
    assert.equal(app.destinations.length, 0);
});
test('forgot-password remains preview and does not send OTP', async () => {
    const app = setup(() => { throw new Error('Must not fetch'); });
    await app.submit(2);
    assert.equal(app.requests.length, 0);
    assert.equal(app.forms[2].message.dataset.state, 'success');
});

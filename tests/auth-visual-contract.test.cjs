const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');

test('authentication inputs use local Font Awesome HTML classes; no inline SVG sprite', () => {
    for (const [file, glyphs] of [
        ['app/views/auth/login-form.php', ['envelope','lock']],
        ['app/views/auth/register-form.php', ['user','envelope','phone','lock']],
        ['app/views/auth/forgot-password-form.php', ['envelope']],
    ]) {
        const html = read(file);
        for (const glyph of glyphs) {
            assert.match(html, new RegExp(`fa-solid fa-${glyph} auth-field-icon`), file);
        }
        assert.doesNotMatch(html, /<svg|#auth-fa-/, file);
    }
    for (const file of ['app/views/auth/auth-modal.php','app/views/auth/page.php']) {
        assert.doesNotMatch(read(file), /icon-sprite\.php/);
    }
    assert.equal(fs.existsSync(path.join(root, 'app/views/auth/icon-sprite.php')), false);
});

test('fontawesome CSS is loaded from same origin across all three layout entry points', () => {
    for (const file of ['app/views/layouts/head.php','app/views/layouts/main.php','app/views/auth/page.php']) {
        const html = read(file);
        assert.match(html, /url\('\/assets\/vendor\/fontawesome\/css\/all\.min\.css'\)/, file);
        assert.doesNotMatch(html, /cdnjs\.cloudflare\.com\/ajax\/libs\/font-awesome/);
    }
});

test('login and registration password toggles retain accessible Font Awesome states', () => {
    for (const file of ['app/views/auth/login-form.php','app/views/auth/register-form.php']) {
        const html = read(file);
        assert.match(html, /aria-pressed="false"/);
        assert.match(html, /fa-solid fa-eye auth-password-eye auth-password-eye--show/);
        assert.match(html, /fa-solid fa-eye-slash auth-password-eye auth-password-eye--hide/);
    }
    const css = read('assets/css/auth/auth.css');
    assert.match(css, /\[data-password-toggle\]\[aria-pressed="true"\]/);
    assert.match(css, /auth-password-eye--hide\s*\{\s*display:\s*none;/);
    assert.match(css, /min-width:\s*44px;/);
});

test('mobile layout remains full screen with a single column and readable inputs', () => {
    const css = read('assets/css/auth/auth.css');
    assert.match(css, /@media \(max-width:\s*575\.98px\)/);
    assert.match(css, /dialog#loginModal\.auth-dialog\s*\{[^}]*width:\s*100vw;/s);
    assert.match(css, /font-size:\s*16px !important;/);
    assert.match(css, /@media \(max-width:\s*767\.98px\)/);
    assert.doesNotMatch(read('app/views/auth/register-form.php'), /col-12 col-sm-6/);
});

test('sticky public header remains configured', () => {
    const css = read('assets/css/integration.css');
    assert.match(css, /\.public-header\.site-header\s*\{[^}]*position:\s*sticky;[^}]*top:\s*0;/s);
});

test('password buttons update type and accessible states while preserving value', () => {
    const vm = require('node:vm');
    const source = read('assets/js/auth/auth.js');
    const input = {id:'loginPassword',type:'password',value:'Secret!123'};
    const attrs = {};
    const button = {
        dataset:{passwordToggle:'loginPassword'},
        setAttribute(name,value){ attrs[name] = value; },
        addEventListener(name,cb){ if(name==='click') this.onClick=cb; },
    };
    const modal = {
        dataset:{},
        contains(el){return el===input;},
        querySelectorAll(sel){return sel==='[data-password-toggle]'?[button]:[];},
        querySelector(){return null;},
        addEventListener(){},
    };
    const document={getElementById(id){return id==='loginModal'?modal:id==='loginPassword'?input:null;}};
    vm.runInNewContext(source,{document});
    assert.equal(typeof button.onClick,'function');
    button.onClick();
    assert.equal(input.type,'text');
    assert.equal(input.value,'Secret!123');
    assert.equal(attrs['aria-pressed'],'true');
    assert.equal(attrs['aria-label'],'Ẩn mật khẩu');
    button.onClick();
    assert.equal(input.type,'password');
    assert.equal(attrs['aria-pressed'],'false');
    assert.equal(attrs['aria-label'],'Hiện mật khẩu');
});

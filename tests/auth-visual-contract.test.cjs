const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');

test('auth forms render local Font Awesome SVG icons without relying on external font loading', () => {
    const sprite = read('app/views/auth/icon-sprite.php');
    for (const glyph of ['user','envelope','lock','phone','eye','eye-slash']) {
        assert.match(sprite, new RegExp(`id="auth-fa-${glyph}"`));
    }
    for (const [file, glyphs] of [
        ['app/views/auth/login-form.php', ['envelope','lock']],
        ['app/views/auth/register-form.php', ['user','envelope','phone','lock']],
        ['app/views/auth/forgot-password-form.php', ['envelope']],
    ]) {
        const content = read(file);
        for (const glyph of glyphs) assert.match(content, new RegExp(`href="#auth-fa-${glyph}"`));
        assert.doesNotMatch(content, /class="fa-(regular|solid).*?auth-field-icon/);
    }
    for (const file of ['app/views/auth/auth-modal.php','app/views/auth/page.php']) {
        assert.match(read(file), /icon-sprite\.php/, `${file} must include the sprite`);
    }
});

test('login and registration buttons have accessible SVG show/hide states', () => {
    for (const file of ['app/views/auth/login-form.php','app/views/auth/register-form.php']) {
        const content = read(file);
        assert.match(content, /aria-pressed="false"/);
        assert.match(content, /href="#auth-fa-eye"/);
        assert.match(content, /href="#auth-fa-eye-slash"/);
    }
    const css = read('assets/css/auth/auth.css');
    assert.match(css, /\[data-password-toggle\]\[aria-pressed="true"\]/);
    assert.match(css, /auth-password-eye--hide\s*\{\s*display:\s*none;/);
    assert.match(css, /min-width:\s*44px;/);
});

test('mobile layout is full screen with one column and readable inputs', () => {
    const css = read('assets/css/auth/auth.css');
    assert.match(css, /@media \(max-width:\s*575\.98px\)/);
    assert.match(css, /dialog#loginModal\.auth-dialog\s*\{[^}]*width:\s*100vw;/s);
    assert.match(css, /font-size:\s*16px !important;/);
    assert.match(css, /@media \(max-width:\s*767\.98px\)/);
    assert.doesNotMatch(read('app/views/auth/register-form.php'), /col-12 col-sm-6/);
});

test('sticky public header is preserved', () => {
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

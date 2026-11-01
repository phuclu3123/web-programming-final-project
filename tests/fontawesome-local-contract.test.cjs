const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const read = f => fs.readFileSync(path.join(root, f), 'utf8');

test('Font Awesome local installer pins exact package and installs CSS, fonts, license', () => {
    const source = read('scripts/install-fontawesome-free.sh');
    assert.match(source, /version=6\.7\.2/);
    assert.match(source, /npm pack/);
    assert.match(source, /assets\/vendor\/fontawesome/);
    assert.match(source, /webfonts\/fa-solid-900\.woff2/);
    assert.match(source, /LICENSE\.txt/);
});

test('auth components do not load Bootstrap Icons or external icon CDN', () => {
    for (const file of ['app/views/layouts/head.php','app/views/auth/page.php','app/views/auth/auth-modal.php']) {
        assert.doesNotMatch(read(file), /bootstrap-icons(?:@|\/)/);
    }
    for (const file of ['app/views/auth/login-form.php','app/views/auth/register-form.php','app/views/auth/auth-modal.php']) {
        assert.doesNotMatch(read(file), /<svg/);
    }
});

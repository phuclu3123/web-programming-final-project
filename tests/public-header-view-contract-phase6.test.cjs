const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const read = file => fs.readFileSync(path.join(root, file), 'utf8');

test('notification partial only renders data supplied by controller', () => {
    const template = read('app/views/partials/notifications.php');
    assert.doesNotMatch(template, /Database::connection|new\s+\\App\\Services\\NotificationService|new\s+\\App\\Repositories\\NotificationRepository/);
    assert.match(template, /\$notificationItems/);
});

test('central renderer prepares notification view-data only for public-header layouts', () => {
    const renderer = read('app/core/Controller.php');
    assert.match(renderer, /\$layout === 'main'/);
    assert.match(renderer, /\['home', 'auth\/page'\]/);
    assert.match(renderer, /PublicHeaderComposer/);
});

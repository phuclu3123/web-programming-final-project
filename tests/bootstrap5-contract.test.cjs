'use strict';
const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const root = path.join(__dirname, '..');
const read = name => fs.readFileSync(path.join(root, name), 'utf8');
function views(dir) {
  return fs.readdirSync(dir, {withFileTypes:true}).flatMap(item =>
    item.isDirectory() ? views(path.join(dir,item.name)) : item.name.endsWith('.php') ? [path.join(dir,item.name)] : []);
}
test('shared layouts load Bootstrap 5.3.8 only', () => {
  for (const name of ['app/views/layouts/main.php','app/views/layouts/head.php','app/views/auth/page.php']) {
    const s=read(name);
    assert.match(s, /bootstrap@5\.3\.8\/dist\/css\/bootstrap\.min\.css/);
    assert.doesNotMatch(s, /bootstrap@4\./);
  }
});
test('views do not use Bootstrap 4 dismiss/toggle attributes', () => {
  for (const file of views(path.join(root, 'app/views'))) {
    const s=fs.readFileSync(file, 'utf8');
    assert.doesNotMatch(s, /data-dismiss="(?:modal|alert)"|data-toggle="modal"/);
    assert.doesNotMatch(s, /class="close"/);
  }
});
test('AJAX copy tracking is connected on card and detail renderers', () => {
  const all = ['app/views/partials/coupon-card.php','app/views/partials/hero-carousel.php','app/views/partials/community-coupon-card.php','app/views/coupons/show.php'];
  for (const name of all) assert.match(read(name), /data-coupon-id=/);
  assert.match(read('config/routes.php'), /\/api\/coupons\/\{id\}\/events/);
  assert.match(read('assets/js/coupon-events.js'), /fetch\(url/);
});

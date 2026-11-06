const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const read = p => fs.readFileSync(path.join(__dirname, '..', p), 'utf8');
const controller = read('app/controllers/CouponInteractionController.php');
const limiter = read('app/core/InteractionRateLimit.php');

test('all four authenticated coupon interaction endpoints enforce actor-scoped policy', () => {
  for (const action of ['favorite', 'vote', 'comment', 'report']) {
    assert.ok(controller.includes(`InteractionRateLimit::check('${action}', $actorId)`));
  }
});
test('interaction throttling preserves intentional 429 and 503 JSON codes', () => {
  assert.match(controller, /\$error instanceof HttpError/);
  assert.match(controller, /\$error->status/);
});
test('comment and report limits include IP ceilings and do not trust forwarded IPs', () => {
  assert.match(limiter, /'comment' => \[80, 60\]/);
  assert.match(limiter, /'report' => \[50, 900\]/);
  assert.match(limiter, /REMOTE_ADDR/);
  assert.doesNotMatch(limiter, /HTTP_X_FORWARDED_FOR/);
});

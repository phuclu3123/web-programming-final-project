'use strict';

const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/coupon-events.js'), 'utf8');

function client({csrf='csrf123', success=true, status=200, throwNetwork=false}={}) {
  const requests=[];
  const window={};
  const document={querySelector: selector => {
    if (selector === 'meta[name="csrf-token"]') return {content:csrf};
    if (selector === 'meta[name="couponhub-base-url"]') return {content:'/couponhub/'};
    return null;
  }};
  const fetch=async (url, options) => {
    requests.push([url, options]);
    if (throwNetwork) throw new Error('offline');
    return {ok:status >= 200 && status < 300, json:async () => ({success})};
  };
  vm.runInNewContext(source,{window,document,fetch,Number,JSON});
  return {events:window.CouponHubEvents,requests};
}

test('COPY and SHARE use existing AJAX JSON endpoint and CSRF', async () => {
  const {events,requests}=client();
  assert.equal(await events.copy(34),true);
  assert.equal(await events.share(34),true);
  assert.equal(requests.length,2);
  for (const [url,opt] of requests) {
    assert.equal(url,'/couponhub/api/coupons/34/events');
    assert.equal(opt.method,'POST');
    assert.equal(opt.credentials,'same-origin');
    assert.equal(opt.headers['Content-Type'],'application/json');
    assert.equal(JSON.parse(opt.body)._token,'csrf123');
  }
  assert.equal(JSON.parse(requests[0][1].body).event,'COPY');
  assert.equal(JSON.parse(requests[1][1].body).event,'SHARE');
});

test('invalid id or missing CSRF does not send request', async () => {
  const a=client();
  assert.equal(await a.events.copy(0),false);
  assert.equal(await a.events.share('NaN'),false);
  assert.equal(a.requests.length,0);
  const b=client({csrf:''});
  assert.equal(await b.events.copy(34),false);
  assert.equal(b.requests.length,0);
});

test('server rejection, failure JSON and network error are not reported as successful events', async () => {
  for (const options of [{status:422},{success:false},{throwNetwork:true}]) {
    const {events}=client(options);
    assert.equal(await events.share(34),false);
  }
});

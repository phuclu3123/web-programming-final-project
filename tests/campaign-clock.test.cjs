const {test}=require('node:test');
const assert=require('node:assert/strict');
const clock=require('../assets/js/campaign-clock.js');
const start=Date.parse('2026-11-01T00:00:00Z'),end=Date.parse('2026-11-11T16:59:59Z');
test('counts actual days/hours/minutes/seconds until the UTC start',()=>{
  assert.deepEqual(clock(start,end,start-((2*86400+3*3600+4*60+5)*1000)),{phase:'upcoming',days:2,hours:3,minutes:4,seconds:5});
});
test('at the exact start switches to time remaining until end',()=>{
  assert.deepEqual(clock(start,end,start),{phase:'active',days:10,hours:16,minutes:59,seconds:59});
});
test('ends at exact deadline and never displays negative numbers',()=>{
  for(const now of [end,end+86400000]) assert.deepEqual(clock(start,end,now),{phase:'ended',days:0,hours:0,minutes:0,seconds:0});
});
test('UTC and Vietnam representations identify the same instant',()=>{
  const vietnam=Date.parse('2026-11-11T23:59:59+07:00');
  assert.equal(vietnam,end);
  assert.equal(clock(start,end,vietnam-1000).seconds,1);
});

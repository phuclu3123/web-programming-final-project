const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('public/js/deal-calendar.js', 'utf8');
const settle = () => new Promise(resolve => setImmediate(resolve));
function deferred() { let resolve, reject; const promise = new Promise((a,b) => { resolve=a; reject=b; }); return { promise, resolve, reject }; }
function fixture() {
  const events={}, calls=[], replaced=[], history=[];
  const feedback={hidden:true,textContent:''};
  const focused={id:'filter-month',matches:()=>false,getAttribute:()=>null};
  const restored={focus:()=>{restored.focused=true;}};
  const root={busy:false,contains:n=>n===focused,setAttribute:()=>{root.busy=true;},removeAttribute:()=>{root.busy=false;},querySelector:s=>s==='.calendar-disclosure'?{open:true}:s==='[data-calendar-feedback]'?feedback:null,replaceWith:n=>replaced.push(n)};
  const document={activeElement:focused,title:'Calendar',querySelector:()=>root,addEventListener:(name,fn)=>{events[name]=fn;}};
  const updated={querySelector:s=>s==='.calendar-disclosure'?{open:false}:s==='#filter-month'?restored:null,querySelectorAll:()=>[]};
  const window={location:{href:'https://example.test/app/campaign-calendar?month=11',origin:'https://example.test'},history:{pushState:(_a,_b,url)=>history.push(url)},addEventListener:(name,fn)=>{events[name]=fn;}};
  vm.runInNewContext(source,{document,window,URL,URLSearchParams,AbortController,CSS:{escape:x=>x},DOMParser:class {parseFromString(){return {title:'Updated calendar',querySelector:()=>updated};}},fetch:(url,options)=>{const d=deferred();calls.push({url,options,...d});return d.promise;}});
  function click() { const event={button:0,target:{closest:()=>({href:'https://example.test/app/campaign-calendar?month=12'})},preventDefault(){this.prevented=true;}};events.click(event);assert.equal(event.prevented,true); }
  function response(text='ok') { return {ok:true,headers:{get:()=> 'text/html; charset=utf-8'},text:async()=>text}; }
  return {calls,root,feedback,replaced,history,restored,click,response};
}
test('calendar GET keeps session credentials, updates URL and restores field focus',async()=>{
  const f=fixture();f.click();assert.equal(f.calls[0].options.credentials,'same-origin');
  f.calls[0].resolve(f.response());await settle();
  assert.equal(f.replaced.length,1);assert.equal(f.history.length,1);assert.equal(f.restored.focused,true);assert.equal(f.root.busy,false);
});
test('calendar network failure retains the old content and URL with retry feedback',async()=>{
  const f=fixture();f.click();f.calls[0].reject(new Error('offline'));await settle();
  assert.equal(f.replaced.length,0);assert.equal(f.history.length,0);assert.equal(f.feedback.hidden,false);assert.match(f.feedback.textContent,/thử lại/);assert.equal(f.root.busy,false);
});
test('a stale body finishing after a newer calendar request cannot replace its content',async()=>{
  const f=fixture(),body=deferred();f.click();f.calls[0].resolve({...f.response(),text:()=>body.promise});await settle();
  f.click();assert.equal(f.calls[0].options.signal.aborted,true);
  f.calls[1].resolve(f.response('new'));await settle();body.resolve('stale');await settle();
  assert.equal(f.replaced.length,1);assert.equal(f.history.length,1);assert.equal(f.feedback.hidden,true);
});
test('a non-HTML error response never replaces the calendar',async()=>{
  const f=fixture();f.click();f.calls[0].resolve({ok:false,headers:{get:()=> 'application/json'}});await settle();
  assert.equal(f.replaced.length,0);assert.equal(f.feedback.hidden,false);assert.equal(f.root.busy,false);
});

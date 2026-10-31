const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../app/views/campaigns/show.php','utf8').match(/<script>([\s\S]*?)<\/script>/)[1];
const tick=()=>new Promise(resolve=>setImmediate(resolve));
function setup(fetcher) {
  const node=(value='')=>({value,handlers:{},style:{},attributes:{},innerHTML:'old results',textContent:'',hidden:true,
    addEventListener(k,fn){this.handlers[k]=fn;},setAttribute(k,v){this.attributes[k]=v;},removeAttribute(k){delete this.attributes[k];}});
  const nodes={
    'campaign-coupon-filter':node(),'campaign-category':node('3'),'campaign-channel':node('OFFLINE'),
    'campaign-platform':node('7'),'campaign-coupon-results':node(),'reset-campaign-filter':node()
  };
  nodes['campaign-coupon-filter'].action='http://localhost:8000/CouponHub_UI/campaigns/1#campaign-offers';
  const feedback=node(),action=node(),count=node(),urls=[];
  vm.runInNewContext(source,{URL,fetch:fetcher,
    DOMParser:class{parseFromString(html){return {querySelector(s){return s==='#campaign-coupon-results'?{innerHTML:html}:null;}};}},
    document:{getElementById:id=>nodes[id],querySelector:s=>s.includes('feedback')?feedback:s.includes('action')?action:count,addEventListener(k,fn){if(k==='DOMContentLoaded')fn();}},
    window:{location:{href:'http://localhost:8000/CouponHub_UI/campaigns/1'},history:{replaceState(a,b,url){urls.push(url);}}}
  });
  return {nodes,feedback,urls};
}
test('campaign AJAX includes platform and reset clears all three filters',async()=>{
  const requests=[];
  const app=setup(async url=>{requests.push(new URL(url));return {ok:true,text:async()=>'<new cards>'};});
  await app.nodes['campaign-platform'].handlers.change();
  assert.equal(requests[0].searchParams.get('platform'),'7');
  assert.equal(requests[0].searchParams.get('category_id'),'3');
  assert.equal(app.nodes['campaign-coupon-results'].innerHTML,'<new cards>');
  app.nodes['reset-campaign-filter'].handlers.click({preventDefault(){}});await tick();
  assert.equal(requests[1].searchParams.get('platform'),'0');
  assert.equal(requests[1].searchParams.get('category_id'),'0');
  assert.equal(requests[1].searchParams.get('usage_channel'),'ALL');
  assert.ok(app.urls[1].includes('/CouponHub_UI/campaigns/1'));
});
test('late campaign response cannot overwrite the latest selection',async()=>{
  const pending=[];
  const app=setup(url=>new Promise(resolve=>pending.push(resolve)));
  const first=app.nodes['campaign-platform'].handlers.change();
  app.nodes['campaign-platform'].value='9';
  const second=app.nodes['campaign-platform'].handlers.change();
  pending[1]({ok:true,text:async()=>'latest'});await second;
  pending[0]({ok:true,text:async()=>'stale'});await first;
  assert.equal(app.nodes['campaign-coupon-results'].innerHTML,'latest');
  assert.equal(new URL(app.urls[0]).searchParams.get('platform'),'9');
});
test('failed campaign filter keeps current results and URL, and releases busy state',async()=>{
  const app=setup(async()=>{throw new Error('offline');});
  await app.nodes['campaign-platform'].handlers.change();
  assert.equal(app.nodes['campaign-coupon-results'].innerHTML,'old results');
  assert.equal(app.feedback.hidden,false);
  assert.equal(app.urls.length,0);
  assert.equal(app.nodes['campaign-coupon-results'].attributes['aria-busy'],undefined);
});
test('broken banner falls back once and unrelated image errors remain untouched',()=>{
  const handlers={};
  vm.runInNewContext(fs.readFileSync(__dirname+'/../assets/js/editorial.js','utf8'),{document:{documentElement:{classList:{add(){}}},addEventListener(k,fn){handlers[k]=fn;}}});
  const picture={tagName:'IMG',dataset:{campaignFallback:'/fallback.webp'},getAttribute(){return 'https://example.test/missing.webp';}};
  handlers.error({target:picture});assert.equal(picture.src,'/fallback.webp');
  picture.src='changed';handlers.error({target:picture});assert.equal(picture.src,'changed');
  const other={tagName:'IMG',dataset:{}};handlers.error({target:other});assert.equal(other.src,undefined);
});

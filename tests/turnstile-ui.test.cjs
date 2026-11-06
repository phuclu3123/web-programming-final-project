const {test}=require('node:test');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(require('node:path').join(__dirname,'../assets/js/turnstile.js'),'utf8');
function setup(fields){
 const events={},scripts=[],renders=[],resets=[];let observer;
 const document={querySelectorAll:()=>fields,head:{append:s=>scripts.push(s)},createElement:()=>({}),body:{},addEventListener:(n,fn)=>events[n]=fn};
 const window={turnstile:{render:(node,options)=>{renders.push({node,options});return renders.length;},reset:id=>resets.push(id)}};
 vm.runInNewContext(source,{document,window,WeakMap,MutationObserver:class{constructor(fn){observer=fn;}observe(){}},requestAnimationFrame:fn=>{fn();return 0;}});
 return {events,scripts,renders,resets,window,changed:()=>observer()};
}
const field=(visible=true,width=350)=>({visible,clientWidth:width,dataset:{sitekey:'public-site',action:'login'},status:{textContent:''},widget:{},getClientRects(){return this.visible?[{}]:[];},querySelector(s){return s.includes('status')?this.status:this.widget;}});
test('blank configuration does not load Cloudflare or install listeners',()=>{
 const x=setup([]);assert.equal(x.scripts.length,0);assert.deepEqual(Object.keys(x.events),[]);
});
test('hidden auth panels defer rendering; each form owns its compact/flexible widget',()=>{
 const login=field(true,280),register=field(false),x=setup([login,register]);
 assert.match(x.scripts[0].src,/^https:\/\/challenges\.cloudflare\.com\/turnstile/);
 x.window.couponhubTurnstileReady();assert.equal(x.renders.length,1);assert.equal(x.renders[0].options.size,'compact');
 register.visible=true;x.changed();assert.equal(x.renders.length,2);assert.equal(x.renders[1].options.size,'flexible');
 x.changed();assert.equal(x.renders.length,2);
 x.events['couponhub:form-finished']({target:{querySelectorAll:()=>[login]}});assert.deepEqual(x.resets,[1]);
 x.renders[0].options['expired-callback']();assert.ok(login.status.textContent);
 x.renders[0].options.callback();assert.equal(login.status.textContent,'');
});
test('script/network and missing-sitekey errors remain visible without rerender loops',()=>{
 const f=field();f.dataset.sitekey='';const x=setup([f]);x.window.couponhubTurnstileReady();x.changed();assert.equal(x.renders.length,0);assert.ok(f.status.textContent);
 x.scripts[0].onerror();assert.ok(f.status.textContent);
 x.events['couponhub:form-finished']({target:{querySelectorAll:()=>[f]}});assert.equal(x.resets.length,0);
});

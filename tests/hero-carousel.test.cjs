const {test}=require('node:test');
const assert=require('node:assert/strict');
const {mount}=require('../assets/js/hero-carousel.js');

class Element {
  constructor() { this.events=new Map(); this.attributes=new Map(); this.hidden=false; this.textContent=''; this.dataset={}; this.isConnected=true; this.classList={add(){},remove(){}}; }
  addEventListener(type,fn) { if(!this.events.has(type)) this.events.set(type,new Set()); this.events.get(type).add(fn); }
  removeEventListener(type,fn) { this.events.get(type)?.delete(fn); }
  async fire(type,fields={}) { await Promise.all(Array.from(this.events.get(type)||[]).map(fn=>fn({target:this,...fields}))); }
  setAttribute(key,value) { this.attributes.set(key,String(value)); }
  removeAttribute(key) { this.attributes.delete(key); }
  getAttribute(key) { return this.attributes.get(key); }
}
function fixture(reduced=false) {
  const root=new Element(), document=new Element(), media=new Element();
  media.matches=reduced; document.hidden=false;
  const slides=Array.from({length:3},(_,i)=>{
    const slide=new Element(); slide.hidden=i!==0; slide.dataset.merchant=['Coolmate','Grab','Traveloka'][i];
    slide.image={loading:i ? 'lazy':'eager',decode:()=>Promise.resolve()};
    slide.querySelector=()=>slide.image;
    return slide;
  });
  const previous=new Element(), next=new Element(), toggle=new Element(), symbol=new Element(), counter=new Element(), status=new Element(), toolbar=new Element();
  previous.hidden=next.hidden=toolbar.hidden=true;
  const dots=slides.map(()=>new Element());
  const selectors={'[data-hero-prev]':previous,'[data-hero-next]':next,'[data-hero-toggle]':toggle,'[data-hero-toggle-symbol]':symbol,'[data-hero-current]':counter,'[data-hero-status]':status,'[data-hero-toolbar]':toolbar};
  root.querySelector=key=>selectors[key];
  root.querySelectorAll=key=>key==='[data-hero-slide]' ? slides : dots;
  const timers=new Map(); let serial=0;
  const env={document,media,setTimeout(fn,delay){assert.equal(delay,7000); timers.set(++serial,fn); return serial;},clearTimeout(id){timers.delete(id);}};
  const release=mount(root,env);
  const active=()=>slides.findIndex(slide=>!slide.hidden);
  const tick=async()=>{const entry=timers.entries().next().value; assert.ok(entry,'a timer is scheduled'); timers.delete(entry[0]); await entry[1]();};
  return {root,slides,previous,next,toggle,counter,status,toolbar,dots,document,media,timers,release,active,tick};
}

test('arrows wrap complete coupons and manual selection keeps automatic rotation',async()=>{
  const f=fixture();
  assert.equal(f.toolbar.hidden,false);
  await f.previous.fire('click');
  assert.equal(f.active(),2);
  assert.equal(f.slides.filter(s=>!s.hidden).length,1);
  assert.equal(f.counter.textContent,'03');
  assert.match(f.status.textContent,/Traveloka/);
  assert.equal(f.dots[2].getAttribute('aria-current'),'true');
  assert.equal(f.timers.size,1);
  await f.next.fire('click');
  assert.equal(f.active(),0);
  await f.tick();
  assert.equal(f.active(),1);
  f.release();
});
test('automatic rotation stays quiet and loops without a stop control',async()=>{
  const f=fixture();
  for(let i=0;i<8;i++) await f.tick();
  assert.equal(f.active(),2);
  assert.equal(f.status.textContent,'');
  assert.equal(f.timers.size,1);
  await f.root.fire('pointerenter',{pointerType:'mouse'});
  await f.root.fire('focusin',{target:f.next});
  assert.equal(f.timers.size,1);
  f.release();
});
test('hidden tabs suspend rotation and returning resumes; keyboard selection does not disable it',async()=>{
  const f=fixture();
  f.document.hidden=true; await f.document.fire('visibilitychange');
  assert.equal(f.timers.size,0);
  f.document.hidden=false; await f.document.fire('visibilitychange');
  assert.equal(f.timers.size,1);
  let prevented=false;
  await f.root.fire('keydown',{key:'ArrowRight',preventDefault(){prevented=true;}});
  assert.equal(f.active(),1);
  assert.equal(prevented,true);
  assert.equal(f.timers.size,1);
  f.release();
});
test('reduced motion is respected; the last image selection wins when decoding finishes late',async()=>{
  const f=fixture(true);
  assert.equal(f.timers.size,0);
  let finish;
  f.slides[1].image.decode=()=>new Promise(resolve=>{finish=resolve;});
  const first=f.next.fire('click');
  await f.next.fire('click');
  assert.equal(f.active(),2);
  finish(); await first;
  assert.equal(f.active(),2);
  f.media.matches=false; await f.media.fire('change');
  assert.equal(f.timers.size,1);
  f.release();
});
test('AJAX replacement destroys timers and detaches old controls',async()=>{
  const f=fixture();
  f.release(); f.root.isConnected=false;
  assert.equal(f.timers.size,0);
  await f.next.fire('click');
  assert.equal(f.active(),0);
  const replacement=fixture();
  await replacement.tick();
  assert.equal(replacement.active(),1);
  replacement.release();
});

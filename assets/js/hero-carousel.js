/* One coupon per slide: photos, conditions, links and copy buttons stay together. */
(function (scope) {
  'use strict';
  function mount(root, environment) {
    const env=environment || {document:scope.document, media:scope.matchMedia('(prefers-reduced-motion: reduce)'), setTimeout:scope.setTimeout.bind(scope), clearTimeout:scope.clearTimeout.bind(scope)};
    const slides=Array.from(root.querySelectorAll('[data-hero-slide]'));
    if (slides.length<2) return function () {};
    const previous=root.querySelector('[data-hero-prev]');
    const next=root.querySelector('[data-hero-next]');
    const counter=root.querySelector('[data-hero-current]');
    const status=root.querySelector('[data-hero-status]');
    const dots=Array.from(root.querySelectorAll('[data-hero-go]'));
    const removers=[];
    let current=0, wanted=0, sequence=0, timer=null, destroyed=false;
    function on(target, type, handler) {
      target.addEventListener(type,handler);
      removers.push(()=>target.removeEventListener(type,handler));
    }
    function clear() {
      if (timer!==null) env.clearTimeout(timer);
      timer=null;
    }
    function schedule() {
      clear();
      if (!destroyed && !env.media.matches && !env.document.hidden && root.isConnected) {
        timer=env.setTimeout(()=>go(current+1,false),7000);
      }
    }
    function sync() {
      counter.textContent=String(current+1).padStart(2,'0');
      dots.forEach((dot,index)=>index===current ? dot.setAttribute('aria-current','true') : dot.removeAttribute('aria-current'));
    }
    function warmNext() {
      const image=slides[(current+1)%slides.length].querySelector('img');
      if (image) image.loading='eager';
    }
    async function go(index, manual) {
      clear();
      wanted=(index+slides.length)%slides.length;
      const target=wanted, request=++sequence;
      const image=slides[target].querySelector('img');
      if (image) {
        image.loading='eager';
        try { if (image.decode) await image.decode(); } catch (_) { /* The browser can still render its normal image fallback. */ }
      }
      if (destroyed || request!==sequence || !root.isConnected) return;
      slides.forEach((slide,position)=>{
        slide.hidden=position!==target;
        slide.classList.remove('hero-slide-enter');
      });
      current=target;
      slides[current].classList.add('hero-slide-enter');
      sync();
      if (manual) status.textContent='Coupon '+(current+1)+' / '+slides.length+' — '+slides[current].dataset.merchant;
      warmNext();
      schedule();
    }
    function manual(index) { return go(index,true); }
    on(previous,'click',()=>manual(wanted-1));
    on(next,'click',()=>manual(wanted+1));
    dots.forEach((dot,index)=>on(dot,'click',()=>manual(index)));
    on(root,'keydown',event=>{
      if (event.key!=='ArrowLeft' && event.key!=='ArrowRight') return;
      event.preventDefault();
      manual(wanted+(event.key==='ArrowLeft' ? -1 : 1));
    });
    on(env.document,'visibilitychange',schedule);
    if (env.media.addEventListener) on(env.media,'change',schedule);
    previous.hidden=false;
    next.hidden=false;
    root.querySelector('[data-hero-toolbar]').hidden=false;
    sync();
    warmNext();
    schedule();
    return function destroy() {destroyed=true; sequence++; clear(); removers.forEach(remove=>remove());};
  }
  if (typeof module!=='undefined' && module.exports) module.exports={mount};
  if (scope.document) {
    let release=function () {};
    function initialize() {
      release();
      const root=scope.document.querySelector('[data-hero-carousel]');
      release=root ? mount(root) : function () {};
    }
    if (scope.document.readyState==='loading') scope.document.addEventListener('DOMContentLoaded',initialize,{once:true});
    else initialize();
    scope.document.addEventListener('couponhub:hero-updated',initialize);
  }
})(typeof window!=='undefined' ? window : globalThis);

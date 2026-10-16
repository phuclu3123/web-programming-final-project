/* Pure UTC phase/clock calculation; no hardcoded campaign dates or duration resets. */
(function (root) {
  'use strict';
  function campaignTime(start,end,now) {
    const phase=now<start?'upcoming':now<end?'active':'ended';
    const seconds=Math.max(0,Math.floor(((phase==='upcoming'?start:end)-now)/1000));
    return {phase,days:Math.floor(seconds/86400),hours:Math.floor(seconds%86400/3600),minutes:Math.floor(seconds%3600/60),seconds:seconds%60};
  }
  if (typeof module!=='undefined' && module.exports) module.exports=campaignTime;
  if (typeof document==='undefined') return;
  const bases=new WeakMap();
  const timeFormat=new Intl.DateTimeFormat('vi-VN',{timeZone:'Asia/Ho_Chi_Minh',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false});
  const dateFormat=new Intl.DateTimeFormat('vi-VN',{timeZone:'Asia/Ho_Chi_Minh',day:'2-digit',month:'2-digit',year:'numeric'});
  function tick() {
    if (document.hidden) return;
    document.querySelectorAll('[data-campaign-clock]').forEach(clock=>{
      if (!bases.has(clock)) bases.set(clock,{time:Number(clock.dataset.serverNow),tick:performance.now()});
      const base=bases.get(clock);
      const state=campaignTime(Number(clock.dataset.start),Number(clock.dataset.end),base.time+performance.now()-base.tick);
      clock.querySelector('[data-clock-label]').textContent=state.phase==='upcoming'?'Bắt đầu sau':state.phase==='active'?'Kết thúc sau':'Đã kết thúc';
      clock.querySelectorAll('[data-clock-part]').forEach(part=>{part.textContent=String(state[part.dataset.clockPart]).padStart(2,'0');});
      const card=clock.closest('.campaign-poster');
      if (card) {
        card.dataset.phase=state.phase;
        const phase=card.querySelector('.poster-phase');
        phase.lastChild.textContent=state.phase==='upcoming'?'Sắp bắt đầu':state.phase==='active'?'Đang diễn ra':'Đã kết thúc';
        const link=card.querySelector('[data-campaign-open]');
        if (state.phase==='ended') {link.setAttribute('aria-disabled','true');link.textContent='Đã kết thúc';}
      }
    });
    document.querySelectorAll('[data-time-vn]').forEach(time=>{
      const now=new Date();
      time.textContent=timeFormat.format(now);
      time.setAttribute('datetime',now.toISOString());
      const fields=Object.fromEntries(dateFormat.formatToParts(now).map(part=>[part.type,part.value]));
      const planner=time.closest('.campaign-planner');
      planner.querySelector('[data-planner-day]').textContent=fields.day;
      planner.querySelector('[data-planner-month]').textContent='THÁNG '+fields.month+' / '+fields.year;
    });
  }
  tick();
  setInterval(tick,1000);
  document.addEventListener('visibilitychange',tick);
  document.addEventListener('click',event=>{if(event.target.closest('[data-campaign-open][aria-disabled="true"]')) event.preventDefault();});
})(typeof window==='undefined'?globalThis:window);

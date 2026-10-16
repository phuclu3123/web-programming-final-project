/* Lưu coupon qua backend chung; không dùng localStorage làm dữ liệu tài khoản. */
(() => {
  'use strict';
  let timer;
  const notify = (message, failed = false) => {
    let box = document.getElementById('discovery-feedback');
    if (!box) { box = document.createElement('div'); box.id = 'discovery-feedback'; box.setAttribute('role','status'); box.setAttribute('aria-live','polite'); document.body.append(box); }
    box.textContent = message;
    box.className = 'discovery-feedback is-visible' + (failed ? ' is-error' : '');
    clearTimeout(timer); timer = setTimeout(() => box.classList.remove('is-visible'), 4500);
  };
  document.addEventListener('submit', async event => {
    const form = event.target.closest('[data-save-form]');
    if (!form) return;
    event.preventDefault();
    const button = form.querySelector('button');
    if (button.disabled) return;
    button.disabled = true;
    try {
      const response = await fetch(form.action,{method:'POST',body:new FormData(form),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error(result.message || 'Chưa thể lưu coupon. Vui lòng thử lại.');
      document.querySelectorAll('[data-save-form]').forEach(other => {
        if (other.dataset.couponId !== form.dataset.couponId) return;
        const saved = Boolean(result.data.saved);
        const trigger = other.querySelector('button');
        other.elements.saved.value = saved ? '0' : '1';
        trigger.classList.toggle('is-saved',saved);
        trigger.setAttribute('aria-pressed',String(saved));
        trigger.setAttribute('aria-label',saved ? 'Bỏ lưu coupon' : 'Lưu coupon');
        trigger.title = saved ? 'Đã lưu coupon' : 'Lưu coupon';
      });
      notify(result.message);
    } catch (error) { notify(error.message || 'Kết nối bị gián đoạn. Vui lòng thử lại.', true); }
    finally { button.disabled = false; }
  });
  document.addEventListener('click', async event => {
    const button = event.target.closest('[data-community-copy]');
    if (!button) return;
    try {
      await navigator.clipboard.writeText(button.dataset.communityCopy);
      notify('Đã sao chép mã. Xem điều kiện trước khi sử dụng.');
    } catch { notify('Chưa thể sao chép tự động. Bạn có thể chọn và sao chép mã trên thẻ.',true); }
  });
  let search = document.getElementById('q');
  const suggestions = document.getElementById('home-suggestions');
  if (!search || !suggestions) return;
  // Đặt lớp gợi ý ngoài hero có overflow:hidden để không bị cắt trên màn hình nhỏ.
  document.body.append(suggestions);
  let suppressed = false;
  const hide = () => { suggestions.hidden = true; };
  const show = () => {
    const keyword = search.value.trim();
    if (suppressed || document.activeElement !== search || keyword.length < 2
      || (new URL(location.href).searchParams.get('q') || '') !== keyword) { hide(); return; }
    const cards = [...document.querySelectorAll('#coupon-grid .coupon-card')].slice(0,5);
    const bounds = search.closest('.discovery-search-shell').getBoundingClientRect();
    if (bounds.bottom < 0 || bounds.bottom > innerHeight - 80) { hide(); return; }
    suggestions.style.left = Math.max(16,bounds.left) + 'px';
    suggestions.style.top = (bounds.bottom + 10) + 'px';
    suggestions.style.width = Math.min(bounds.width,innerWidth-32) + 'px';
    suggestions.style.maxHeight = Math.min(340,innerHeight-bounds.bottom-24) + 'px';
    suggestions.replaceChildren();
    if (!cards.length) { hide(); return; }
    const label = document.createElement('span'); label.className='discovery-suggestion-label'; label.textContent='Ưu đãi phù hợp'; suggestions.append(label);
    cards.forEach(card => {
      const source = card.querySelector('h3 a');
      if (!source) return;
      const link = document.createElement('a'); link.href=source.href;
      const title = document.createElement('strong'); title.textContent=source.textContent;
      const store = document.createElement('small'); store.textContent=card.querySelector('.store-name')?.textContent || '';
      link.append(title,store); suggestions.append(link);
    });
    suggestions.hidden=false;
  };
  window.addEventListener('couponhub:catalog-updated',()=>{search=document.getElementById('q'); document.querySelector('.catalog-search-shell #home-suggestions')?.remove(); show();});
  document.addEventListener('input',event => { if(event.target.id!=='q') return; search=event.target; suppressed=false; hide(); });
  document.addEventListener('focusin',event => { if(event.target.id!=='q') return; search=event.target; suppressed=false; show(); });
  document.addEventListener('keydown',event => {
    if(event.target.id!=='q') return;
    if (event.key === 'Escape') { suppressed=true; hide(); }
    if (event.key === 'ArrowDown' && !suggestions.hidden) { event.preventDefault(); suggestions.querySelector('a')?.focus(); }
  });
  suggestions.addEventListener('keydown',event => { if (event.key === 'Escape') { search.focus(); suppressed=true; hide(); } });
  document.addEventListener('submit',event => { if(event.target.id==='home-filter-form'){suppressed=true; hide();} });
  document.addEventListener('click',event => { if (!event.target.closest('.discovery-search-shell') && !suggestions.contains(event.target)) hide(); });
  document.addEventListener('focusin',event => { if (!event.target.closest('.discovery-search-shell') && !suggestions.contains(event.target)) hide(); });
  window.addEventListener('scroll',hide,{passive:true});
  window.addEventListener('resize',hide);
})();

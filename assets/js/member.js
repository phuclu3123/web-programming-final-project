/* Shared public chrome. Native details/anchors remain usable without JavaScript. */
(() => {
  'use strict';
  document.querySelectorAll('[data-notifications-read]').forEach(form => form.addEventListener('submit', async event => {
    event.preventDefault();
    const button = form.querySelector('button');
    const panel = form.closest('.header-notification-wrapper');
    const feedback = panel.querySelector('.notification-feedback');
    button.disabled = true;
    try {
      const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' } });
      const result = await response.json();
      if (!response.ok || !result.success) throw new Error('read failed');
      panel.querySelectorAll('.unread').forEach(item => item.classList.remove('unread'));
      panel.querySelector('.notification-dot')?.remove();
      panel.querySelector('summary').setAttribute('aria-label', 'Thông báo');
      button.textContent = 'Đã đọc';
      feedback.hidden = true;
    } catch {
      feedback.textContent = 'Chưa thể cập nhật. Bạn thử lại nhé.';
      feedback.hidden = false;
      button.disabled = false;
    }
  }));
  const menus = [...document.querySelectorAll('[data-account-dropdown]')];
  menus.forEach(menu => menu.addEventListener('toggle', () => {
    if (menu.open) menus.forEach(other => { if (other !== menu) other.open = false; });
  }));
  document.addEventListener('click', event => {
    menus.forEach(menu => { if (!menu.contains(event.target)) menu.open = false; });
  });
  document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    const open = menus.find(menu => menu.open);
    if (open) { open.open = false; open.querySelector('summary').focus(); }
  });
  const rails = [...document.querySelectorAll('[data-section-rail]')];
  if (!rails.length) return;
  const links = rails.flatMap(rail => [...rail.querySelectorAll('a[href^="#"]')]);
  let frame = 0;
  const sync = () => {
    frame = 0;
    // Bộ lọc trang chủ thay vùng kết quả: luôn lấy section hiện tại trong DOM.
    const targets = links.map(link => document.getElementById(link.hash.slice(1))).filter(Boolean);
    const anchor = Math.min(window.innerHeight * .32, 220);
    // Kính đổi sắc theo nền tại vị trí thanh, giữ vạch rõ trên hero tối và nội dung sáng.
    const railCenter = window.innerHeight / 2;
    const onDark = [...document.querySelectorAll('.hero-compact, .editorial-home .savings-lab, .public-footer')].some(section => {
      const bounds = section.getBoundingClientRect();
      return bounds.top <= railCenter && bounds.bottom >= railCenter;
    });
    rails.forEach(rail => rail.classList.toggle('on-dark', onDark));
    let active = targets[0];
    targets.forEach(target => { if (target.getBoundingClientRect().top <= anchor) active = target; });
    links.forEach(link => {
      if (link.hash === '#' + active?.id) link.setAttribute('aria-current', 'location');
      else link.removeAttribute('aria-current');
    });
  };
  const schedule = () => { if (!frame) frame = requestAnimationFrame(sync); };
  window.addEventListener('scroll', schedule, { passive: true });
  window.addEventListener('resize', schedule);
  window.addEventListener('hashchange', schedule);
  links.forEach(link => link.addEventListener('click', event => {
    const target = document.getElementById(link.hash.slice(1));
    if (!target) return;
    event.preventDefault();
    history.pushState(null, '', link.hash);
    target.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    if (!target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
    target.focus({ preventScroll: true });
  }));
  sync();
})();

/* Shared public chrome. Native details/anchors remain usable without JavaScript. */
(() => {
  'use strict';
  const notificationPanel = document.querySelector('[data-notifications-endpoint]');
  if (notificationPanel) {
    const form = notificationPanel.querySelector('[data-notifications-read]');
    const button = form.querySelector('button');
    const feedback = notificationPanel.querySelector('.notification-feedback');
    let fetching = false, reading = false, signature = '', revision = 0;
    const read = async eventKey => {
      const body = new FormData(form); if (eventKey) body.set('event_key', eventKey);
      const response = await fetch(form.action, { method: 'POST', body, headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(8000) });
      const result = await response.json(); if (!response.ok || !result.success) throw new Error('read failed');
    };
    const refresh = async () => {
      if (document.hidden || fetching || reading) return;
      fetching = true;
      const currentRevision = revision;
      try {
        const response = await fetch(notificationPanel.dataset.notificationsEndpoint, { headers: { Accept: 'application/json' }, signal: AbortSignal.timeout(8000) });
        const result = await response.json(); if (!response.ok || !result.success) return;
        if (reading || currentRevision !== revision) return;
        const data = result.data, nextSignature = JSON.stringify(data);
        if (signature === nextSignature) return; signature = nextSignature;
        const summary = notificationPanel.querySelector('summary');
        summary.setAttribute('aria-label', 'Thông báo' + (data.unread_count ? ' · ' + data.unread_count + ' chưa đọc' : ''));
        summary.querySelector('.notification-dot')?.remove();
        if (data.unread_count) { const dot = document.createElement('span'); dot.className = 'notification-dot'; summary.append(dot); }
        form.hidden = !data.unread_count;
        const focusedKey = document.activeElement.closest?.('[data-notification-key]')?.dataset.notificationKey;
        const body = notificationPanel.querySelector('.notif-body'); body.replaceChildren();
        data.items.slice(0, 5).forEach(item => {
          const link = document.createElement('a'); link.className = 'notif-item' + (item.unread ? ' unread' : ''); link.href = item.href; link.dataset.notificationKey = item.event_key;
          const title = document.createElement('p'); title.className = 'notif-title'; title.textContent = item.title;
          const time = document.createElement('time'); time.className = 'notif-time'; time.textContent = item.occurred_at_display; link.append(title, time); body.append(link);
        });
        if (!data.items.length) { const empty = document.createElement('p'); empty.className = 'notification-empty'; empty.textContent = 'Bạn chưa có thông báo mới.'; body.append(empty); }
        if (focusedKey) [...body.querySelectorAll('[data-notification-key]')].find(link => link.dataset.notificationKey === focusedKey)?.focus();
      } catch { /* Keep the current feed during a transient network failure. */ }
      finally { fetching = false; if (!reading && currentRevision !== revision) refresh(); }
    };
    form.addEventListener('submit', async event => {
      event.preventDefault(); if (reading) return; reading = true; revision++; button.disabled = true;
      try { await read(''); feedback.hidden = true; signature = ''; }
      catch { feedback.textContent = 'Chưa thể cập nhật. Bạn thử lại nhé.'; feedback.hidden = false; }
      finally { reading = false; button.disabled = false; await refresh(); }
    });
    document.addEventListener('click', async event => {
      const link = event.target.closest('[data-notification-key]');
      if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
      event.preventDefault();
      try { await read(link.dataset.notificationKey); } catch { /* Reading must not block opening the discussion. */ }
      window.location.assign(link.href);
    });
    // State comes from MySQL; no localStorage/sessionStorage or client-generated notifications.
    notificationPanel.addEventListener('toggle', () => { if (notificationPanel.open) refresh(); });
    window.addEventListener('focus', refresh);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    window.addEventListener('couponhub:notifications-refresh', refresh);
    window.setInterval(refresh, 30000);
  }
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

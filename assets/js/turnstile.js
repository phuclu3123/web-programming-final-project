/* Each form owns a one-use token. Hidden auth views render only when visible. */
(() => {
  'use strict';
  const fields = () => [...document.querySelectorAll('[data-turnstile-field]')];
  if (!fields().length) return;
  const ids = new WeakMap();
  function render() {
    if (!window.turnstile) return;
    fields().forEach(field => {
      if (ids.has(field) || !field.getClientRects().length) return;
      const status = field.querySelector('[data-turnstile-status]');
      const unavailable = () => { status.textContent = 'Chưa thể xác minh. Kiểm tra kết nối hoặc tải lại trang.'; };
      if (!field.dataset.sitekey) { status.textContent='Chưa cấu hình xác minh chống bot. Vui lòng liên hệ quản trị viên.'; ids.set(field,null); return; }
      try { const id = window.turnstile.render(field.querySelector('[data-turnstile-widget]'), {
        sitekey: field.dataset.sitekey, action: field.dataset.action,
        size: field.clientWidth < 300 ? 'compact' : 'flexible', theme: 'auto',
        callback: () => { status.textContent = ''; },
        'expired-callback': () => { status.textContent = 'Xác minh đã hết hạn. Vui lòng xác minh lại.'; },
        'error-callback': unavailable,
      });
      ids.set(field, id);
      } catch { ids.set(field,null); unavailable(); }
    });
  }
  window.couponhubTurnstileReady = render;
  const script = document.createElement('script');
  script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?onload=couponhubTurnstileReady&render=explicit';
  script.async = true; script.defer = true;
  script.onerror = () => fields().forEach(f => { f.querySelector('[data-turnstile-status]').textContent = 'Không tải được xác minh. Vui lòng tải lại trang.'; });
  document.head.append(script);
  let frame;
  new MutationObserver(() => { if (!frame) frame = requestAnimationFrame(() => { frame = 0; render(); }); })
    .observe(document.body, {subtree: true, childList: true, attributes: true, attributeFilter: ['hidden', 'open', 'class']});
  document.addEventListener('couponhub:form-finished', event => {
    event.target.querySelectorAll('[data-turnstile-field]').forEach(field => {
      const id = ids.get(field);
      if (id !== undefined && id !== null && window.turnstile) window.turnstile.reset(id);
    });
  });
})();

/* CouponHub copy tracking: POST JSON request / JSON response; no page reload. */
(() => {
  'use strict';
  window.CouponHubEvents = window.CouponHubEvents || {};
  window.CouponHubEvents.copy = async (couponId) => {
    const id = Number(couponId);
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    if (!Number.isSafeInteger(id) || id <= 0 || !token) return false;
    const base = document.querySelector('meta[name="couponhub-base-url"]')?.content || '';
    const url = `${base.replace(/\/$/, '')}/api/coupons/${id}/events`;
    try {
      const body = JSON.stringify({_token: token, event: 'COPY'});
      const response = await fetch(url, {
        method: 'POST', body, credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
      });
      return response.ok;
    } catch { return false; }
  };
})();

/* CouponHub copy tracking: POST JSON request / JSON response; no page reload. */
(() => {
  'use strict';
  window.CouponHubEvents = window.CouponHubEvents || {};
  async function send(couponId, event) {
    const id = Number(couponId);
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    if (!Number.isSafeInteger(id) || id <= 0 || !token) return false;
    const base = document.querySelector('meta[name="couponhub-base-url"]')?.content || '';
    const url = `${base.replace(/\/$/, '')}/api/coupons/${id}/events`;
    try {
      const body = JSON.stringify({_token: token, event});
      const response = await fetch(url, {
        method: 'POST', body, credentials: 'same-origin',
        headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
      });
      if (!response.ok) return false;
      const result = await response.json();
      return result.success === true;
    } catch { return false; }
  }
  window.CouponHubEvents.copy = couponId => send(couponId, 'COPY');
  window.CouponHubEvents.share = couponId => send(couponId, 'SHARE');
})();

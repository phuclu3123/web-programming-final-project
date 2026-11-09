(() => {
    const notice = document.querySelector('[data-cookie-notice]');
    if (!notice) return;
    const key = 'couponhub_cookie_consent_v1';
    const visitKey = 'couponhub_cookie_notice_v1';
    const maxAge = 180 * 24 * 60 * 60 * 1000;
    let value = null;
    try {
        const saved = JSON.parse(localStorage.getItem(key));
        if (saved?.version === 1 && typeof saved.externalSignIn === 'boolean'
            && Number.isFinite(saved.updatedAt) && saved.updatedAt <= Date.now() && Date.now() - saved.updatedAt < maxAge) value = saved;
    } catch { /* Storage may be disabled; essential login remains available. */ }

    /** Only a real affirmative choice can enable the optional Google SDK. */
    function publish() {
        window.couponhubCookieConsent = { necessary: true, externalSignIn: value?.externalSignIn === true };
        window.dispatchEvent(new CustomEvent('couponhub:cookie-consent', { detail: window.couponhubCookieConsent }));
    }
    publish();
    let acknowledged = false;
    try { acknowledged = sessionStorage.getItem(visitKey) === '1'; } catch {}
    // Remind once per browser tab/visit; navigating within the site is not a new consent request.
    notice.hidden = acknowledged && value !== null;
    document.querySelectorAll('[data-cookie-settings]').forEach(button => button.addEventListener('click', () => {
        notice.hidden = false;
        notice.querySelector('summary').focus();
    }));
    notice.querySelectorAll('[data-cookie-choice]').forEach(button => button.addEventListener('click', () => {
        value = { version: 1, necessary: true, externalSignIn: button.dataset.cookieChoice === 'all', updatedAt: Date.now() };
        let persisted = true;
        try { localStorage.setItem(key, JSON.stringify(value)); } catch { persisted = false; }
        try { sessionStorage.setItem(visitKey, '1'); } catch {}
        publish();
        if (persisted) notice.hidden = true;
        else notice.querySelector('[data-cookie-feedback]').textContent = 'Đã áp dụng cho trang này. Trình duyệt hiện không cho phép lưu lựa chọn.';
    }));
})();

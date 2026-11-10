(() => {
    const notice = document.querySelector('[data-cookie-notice]');
    if (!notice) return;
    const key = 'couponhub_cookie_consent_v1';
    const maxAge = 180 * 24 * 60 * 60 * 1000;
    function readChoice(raw) {
        try {
            const saved = JSON.parse(raw);
            if (saved?.version === 1 && typeof saved.externalSignIn === 'boolean'
                && Number.isFinite(saved.updatedAt) && saved.updatedAt <= Date.now() && Date.now() - saved.updatedAt < maxAge) return saved;
        } catch { /* Invalid storage never enables optional cookies. */ }
        return null;
    }
    let value = null;
    try { value = readChoice(localStorage.getItem(key)); } catch { /* Essential login remains available without storage. */ }

    /** Only a real affirmative choice can enable the optional Google SDK. */
    function publish() {
        window.couponhubCookieConsent = { necessary: true, externalSignIn: value?.externalSignIn === true };
        window.dispatchEvent(new CustomEvent('couponhub:cookie-consent', { detail: window.couponhubCookieConsent }));
    }
    publish();
    // A valid choice applies to every tab on this origin for 180 days.
    notice.hidden = value !== null;
    window.addEventListener('storage', event => {
        if (event.storageArea !== localStorage || (event.key !== key && event.key !== null)) return;
        value = readChoice(event.newValue);
        notice.hidden = value !== null;
        publish();
    });
    document.querySelectorAll('[data-cookie-settings]').forEach(button => button.addEventListener('click', () => {
        notice.hidden = false;
        notice.querySelector('summary').focus();
    }));
    notice.querySelectorAll('[data-cookie-choice]').forEach(button => button.addEventListener('click', () => {
        value = { version: 1, necessary: true, externalSignIn: button.dataset.cookieChoice === 'all', updatedAt: Date.now() };
        let persisted = true;
        try { localStorage.setItem(key, JSON.stringify(value)); } catch { persisted = false; }
        publish();
        if (persisted) notice.hidden = true;
        else notice.querySelector('[data-cookie-feedback]').textContent = 'Đã áp dụng cho trang này. Trình duyệt hiện không cho phép lưu lựa chọn.';
    }));
})();

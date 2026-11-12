// Google renders the account picker. PHP verifies the signed ID token before creating a session.
(() => {
    const modal = document.getElementById('loginModal');
    if (!modal?.dataset.oneTapStart || !modal.dataset.oneTapLogin) return;
    const loginForm = modal.querySelector('#loginForm');
    let library, preparation, context, initialized = false, posting = false, consumed = false, epoch = 0, prompted = false;
    const isLoginOpen = () => modal.open && !modal.querySelector('[data-auth-view="login"]').hidden;

    /** Keep credentials in the request body; never write them to storage, a URL or console. */
    async function post(url, fields) {
        const endpoint = new URL(url, document.baseURI);
        if (endpoint.origin !== window.location.origin) throw new Error('Invalid endpoint');
        const csrf = loginForm.querySelector('[name="csrf"]').value;
        const response = await fetch(endpoint.href, { method: 'POST', credentials: 'same-origin',
            headers: { Accept: 'application/json' }, body: new URLSearchParams({ ...fields, csrf }) });
        const result = await response.json();
        if (!response.ok || result.success !== true) throw new Error(result.message || 'Không thể đăng nhập Google.');
        return result;
    }

    /** Load the official SDK only after the visitor opens login. Password login stays usable on failure. */
    function loadLibrary() {
        if (window.google?.accounts?.id) return Promise.resolve(window.google.accounts.id);
        if (!library) library = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://accounts.google.com/gsi/client';
            script.async = true;
            script.onload = () => window.google?.accounts?.id ? resolve(window.google.accounts.id) : reject(new Error('Google unavailable'));
            script.onerror = () => reject(new Error('Google unavailable'));
            document.head.appendChild(script);
        });
        return library;
    }

    /** Exchange a real Google credential for the same role-aware PHP session used by password login. */
    async function handleCredential(response) {
        if (!isLoginOpen() || posting || consumed || window.couponhubCookieConsent?.externalSignIn !== true || typeof response?.credential !== 'string') return;
        posting = true;
        consumed = true;
        try {
            const result = await post(modal.dataset.oneTapLogin, { state: context.state, credential: response.credential,
                return_url: loginForm.querySelector('[name="return_url"]').value });
            const destination = new URL(result.redirect, document.baseURI);
            if (destination.origin !== window.location.origin) throw new Error('Đường dẫn đăng nhập không hợp lệ.');
            window.location.assign(destination.href);
        } catch (error) {
            const message = loginForm.querySelector('.auth-message');
            message.textContent = error.message || 'Không thể đăng nhập Google. Vui lòng thử cách đăng nhập khác.';
            message.dataset.state = 'error';
            message.hidden = false;
        } finally { posting = false; }
    }

    /** Closing/changing the form cancels the picker, including a preparation still in flight. */
    function handleCancel() {
        epoch++;
        prompted = false;
        window.google?.accounts?.id?.cancel();
    }

    /** One initialization per page; a nonce expires after ten minutes and cannot be replayed. */
    async function handleOpen() {
        if (!isLoginOpen() || posting || prompted || consumed || window.couponhubCookieConsent?.externalSignIn !== true) return;
        const currentEpoch = ++epoch;
        try {
            if (!preparation) {
                const returnUrl = loginForm.querySelector('[name="return_url"]').value;
                preparation = post(modal.dataset.oneTapStart, { return_url: returnUrl });
            }
            const [intent, googleId] = await Promise.all([preparation, loadLibrary()]);
            if (currentEpoch !== epoch || !isLoginOpen() || window.couponhubCookieConsent?.externalSignIn !== true || intent.expires * 1000 <= Date.now()) return;
            context = intent;
            if (!initialized) {
                googleId.initialize({ client_id: intent.client_id, nonce: intent.nonce,
                    callback: handleCredential, auto_select: false, cancel_on_tap_outside: false,
                    prompt_parent_id: 'google-one-tap-prompt', context: 'signin' });
                initialized = true;
            }
            prompted = true;
            googleId.prompt();
        } catch {
            // Blocked SDK, unavailable provider or a dismissed browser prompt must not block the login form.
        }
    }

    modal.addEventListener('couponhub:auth-open', handleOpen);
    modal.addEventListener('couponhub:auth-view', event => {
        if (event.detail.view === 'login') handleOpen(); else handleCancel();
    });
    modal.addEventListener('close', handleCancel);
    window.addEventListener('couponhub:cookie-consent', () => {
        if (window.couponhubCookieConsent?.externalSignIn === true) handleOpen(); else handleCancel();
    });
    // /login may have opened the dialog before this deferred script executed.
    if (isLoginOpen()) handleOpen();
})();

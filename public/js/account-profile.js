(function () {
    'use strict';

    var forms = document.querySelectorAll('[data-account-submit-form]');

    function restore(form) {
        if (form.dataset.resetTimer) {
            window.clearTimeout(Number(form.dataset.resetTimer));
            delete form.dataset.resetTimer;
        }
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        var button = form.querySelector('[data-account-submit-button]');
        if (!button) return;
        button.disabled = false;
        if (button.dataset.originalLabel) button.textContent = button.dataset.originalLabel;
    }

    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            var button = form.querySelector('[data-account-submit-button]');
            if (!button) return;

            form.dataset.submitting = '1';
            form.setAttribute('aria-busy', 'true');
            button.dataset.originalLabel = button.textContent;
            button.disabled = true;
            button.textContent = button.dataset.pendingLabel || 'Đang xử lý...';

            // Recover if the request fails before the browser navigates away.
            form.dataset.resetTimer = String(window.setTimeout(function () {
                restore(form);
            }, 30000));
        });
    });

    // Restore controls when a page is returned from the browser back-forward cache.
    window.addEventListener('pageshow', function () {
        forms.forEach(restore);
    });
})();

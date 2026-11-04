(function () {
    'use strict';

    var requestController = null;
    var requestSequence = 0;

    function pageRoot() {
        return document.querySelector('.editorial-calendar-page');
    }

    function showError(message) {
        var feedback = pageRoot()?.querySelector('[data-calendar-feedback]');
        if (!feedback) return;
        feedback.textContent = message;
        feedback.hidden = false;
    }

    async function updateCalendar(url, addHistory) {
        var root = pageRoot();
        if (!root) return;

        if (requestController) requestController.abort();
        var controller = new AbortController();
        requestController = controller;
        var sequence = ++requestSequence;
        var disclosureWasOpen = Boolean(root.querySelector('.calendar-disclosure')?.open);
        var focused = document.activeElement;
        var focusId = root.contains(focused) ? focused.id : '';
        var focusLabel = root.contains(focused) ? focused.getAttribute('aria-label') : '';
        var filterWasFocused = root.contains(focused) && focused.matches('.btn-calendar-filter');
        root.setAttribute('aria-busy', 'true');

        try {
            var response = await fetch(url.href, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal
            });
            var contentType = response.headers.get('content-type') || '';
            if (!response.ok || !contentType.toLowerCase().includes('text/html')) {
                throw new Error('Calendar response unavailable');
            }

            var html = await response.text();
            var parsed = new DOMParser().parseFromString(html, 'text/html');
            var updatedRoot = parsed.querySelector('.editorial-calendar-page');
            if (!updatedRoot) throw new Error('Calendar response invalid');
            if (sequence !== requestSequence) return;

            var updatedDisclosure = updatedRoot.querySelector('.calendar-disclosure');
            if (updatedDisclosure) updatedDisclosure.open = disclosureWasOpen;
            root.replaceWith(updatedRoot);
            var focusTarget = focusId ? updatedRoot.querySelector('#' + CSS.escape(focusId)) : null;
            if (!focusTarget && focusLabel) focusTarget = Array.from(updatedRoot.querySelectorAll('[aria-label]')).find(function (node) { return node.getAttribute('aria-label') === focusLabel; });
            if (!focusTarget && filterWasFocused) focusTarget = updatedRoot.querySelector('.btn-calendar-filter');
            if (!focusTarget && root.contains(focused)) {
                focusTarget = updatedRoot.querySelector('.calendar-period');
                focusTarget?.setAttribute('tabindex', '-1');
            }
            focusTarget?.focus({ preventScroll: true });
            if (parsed.title) document.title = parsed.title;
            if (addHistory) window.history.pushState({ calendar: true }, '', url.href);
        } catch (error) {
            if (error.name !== 'AbortError' && sequence === requestSequence) {
                showError('Không thể cập nhật lịch lúc này. Vui lòng thử lại.');
            }
        } finally {
            if (sequence === requestSequence) {
                pageRoot()?.removeAttribute('aria-busy');
                requestController = null;
            }
        }
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('.editorial-calendar-page .calendar-filter-bar');
        if (!form) return;
        event.preventDefault();

        var url = new URL(form.action, window.location.href);
        url.search = new URLSearchParams(new FormData(form)).toString();
        updateCalendar(url, true);
    });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('.editorial-calendar-page .calendar-controls a');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        var url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        updateCalendar(url, true);
    });

    window.addEventListener('popstate', function () {
        updateCalendar(new URL(window.location.href), false);
    });
})();

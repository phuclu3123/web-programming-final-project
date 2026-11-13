// Điều hướng lịch chiến dịch và cập nhật thông tin theo dữ liệu trang đã tải.
(function () {
    'use strict';

    var requestController = null;
    var requestSequence = 0;
    var clockBases = new WeakMap();

    function pageRoot() {
        return document.querySelector('.editorial-calendar-page');
    }

    function updateCountdowns(root) {
        if (!root?.dataset?.calendarNow || typeof root.querySelectorAll !== 'function') return;
        if (!clockBases.has(root)) clockBases.set(root, { now: Number(root.dataset.calendarNow), tick: performance.now() });
        var base = clockBases.get(root);
        var now = base.now + performance.now() - base.tick;
        root.querySelectorAll('[data-calendar-offer]').forEach(function (row) {
            if (row.closest('.calendar-day-detail')) return;
            var start = Number(row.dataset.start), end = Number(row.dataset.end);
            var upcoming = now < start, ended = now >= end;
            var seconds = Math.max(0, Math.floor(((upcoming ? start : end) - now) / 1000));
            var days = Math.floor(seconds / 86400);
            var time = [Math.floor(seconds % 86400 / 3600), Math.floor(seconds % 3600 / 60), seconds % 60].map(function (part) { return String(part).padStart(2, '0'); }).join(':');
            var compact = row.classList.contains('is-compact');
            var hours = Math.floor(seconds % 86400 / 3600), minutes = Math.floor(seconds % 3600 / 60);
            var compactTime = days ? days + ' ngày ' + hours + ' giờ' : hours ? hours + ' giờ ' + minutes + ' phút' : minutes + ' phút ' + seconds % 60 + ' giây';
            row.querySelector('[data-calendar-timer-label]').textContent = ended ? 'Đã kết thúc' : upcoming ? 'Bắt đầu sau' : compact ? 'Còn' : 'Kết thúc sau';
            row.querySelector('[data-calendar-timer-value]').textContent = compact ? compactTime : (days ? days + ' ngày ' : '') + time;
            row.dataset.phase = ended ? 'ended' : upcoming ? 'upcoming' : 'active';
            row.classList.toggle('is-ending-soon', !upcoming && !ended && end - now <= 48 * 3600000);
        });
    }

    function selectDay(root, day, announce) {
        var panel = root.querySelector('[data-calendar-selected-day]');
        var content = day?.querySelector('.calendar-day-detail');
        if (!panel || !content) return;
        panel.replaceChildren(...Array.from(content.children, node => node.cloneNode(true)));
        panel.dataset.selectedDay = day.dataset.calendarDay;
        root.querySelectorAll('.calendar-day').forEach(function (item) {
            var selected = item === day;
            item.classList.toggle('is-selected', selected);
            item.open = false;
            var summary = item.querySelector('summary');
            summary.setAttribute('aria-expanded', String(selected));
            summary.tabIndex = selected ? 0 : -1;
        });
        var heading = panel.querySelector('h2');
        if (heading) { heading.id = 'calendar-selected-heading'; panel.setAttribute('aria-labelledby', heading.id); }
        if (announce) {
            var status = root.querySelector('[data-calendar-selection-status]');
            if (status) status.textContent = day.querySelector('summary').getAttribute('aria-label');
        }
        updateCountdowns(root);
    }

    function initialiseCalendar(root) {
        updateCountdowns(root);
        var panel = root?.querySelector('[data-calendar-selected-day]');
        if (!panel) return;
        root.classList.add('calendar-enhanced');
        var days = Array.from(root.querySelectorAll('[data-calendar-day]'));
        var initial = days.find(day => day.dataset.calendarDay === panel.dataset.initialDay) || days[0];
        selectDay(root, initial, false);
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

            root.replaceWith(updatedRoot);
            initialiseCalendar(updatedRoot);
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
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        var link = event.target.closest('.editorial-calendar-page [data-calendar-navigate], .editorial-calendar-page .calendar-controls a, .editorial-calendar-page .btn-calendar-reset');
        if (link) {
            var url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            event.preventDefault();
            updateCalendar(url, true);
            return;
        }
        var summary = event.target.closest('.calendar-enhanced .calendar-day > summary');
        if (!summary) return;
        event.preventDefault();
        var root = pageRoot();
        selectDay(root, summary.closest('.calendar-day'), true);
        if (window.innerWidth < 851 && event.detail > 0) {
            root.querySelector('[data-calendar-selected-day]').scrollIntoView({ block: 'nearest', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
        }
    });

    document.addEventListener('keydown', function (event) {
        var summary = event.target.closest('.calendar-enhanced .calendar-day > summary');
        var moves = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
        if (!summary || !(event.key in moves)) return;
        var root = pageRoot();
        var days = Array.from(root.querySelectorAll('.calendar-day'));
        var next = days[days.indexOf(summary.closest('.calendar-day')) + moves[event.key]];
        if (!next) return;
        event.preventDefault();
        selectDay(root, next, true);
        next.querySelector('summary').focus({ preventScroll: true });
    });

    window.addEventListener('popstate', function () {
        updateCalendar(new URL(window.location.href), false);
    });
    initialiseCalendar(pageRoot());
    if (typeof setInterval === 'function') setInterval(function () { if (!document.hidden) updateCountdowns(pageRoot()); }, 1000);
    document.addEventListener('visibilitychange', function () { updateCountdowns(pageRoot()); });
})();

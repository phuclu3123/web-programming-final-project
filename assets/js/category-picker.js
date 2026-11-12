/* Render category picker options from the existing source controls.
   No cloned form inputs, no duplicated GET parameters, no new navigation paths. */
(() => {
  'use strict';

  const selector = '[data-category-picker]';
  const getRail = root => root.querySelector('[data-category-rail]');
  const originalOptions = root => Array.from(getRail(root)?.children || [])
    .filter(node => node.matches('a, label'));

  const canOpenDialog = () => typeof HTMLDialogElement !== 'undefined'
    && typeof HTMLDialogElement.prototype.showModal === 'function';

  function initialize(root) {
    if (!canOpenDialog()) return;
    const rail = getRail(root);
    const dialog = root.querySelector('dialog.couponhub-category-dialog');
    const trigger = root.querySelector('[data-category-open]');
    if (!rail || !dialog || !trigger || !originalOptions(root).length) return;
    root.dataset.categoryPickerReady = 'true';
    if (root.dataset.categoryPickerBound === 'true') return;
    root.dataset.categoryPickerBound = 'true';
    // Backdrop click and native Escape both close the same top-layer dialog.
    dialog.addEventListener('click', event => {
      if (event.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
      if (trigger.isConnected) trigger.focus({ preventScroll: true });
    });
    syncCompact(root);
  }

  // Progressive disclosure: cap chip rows on mobile and avoid multi-row clutter on desktop.
  // Selected category is always considered first, then All, then configured order.
  const compactMedia = window.matchMedia('(max-width: 991.98px)');
  const phoneMedia = window.matchMedia('(max-width: 600px)');
  function syncCompact(root) {
    const rail = getRail(root);
    const trigger = root.querySelector('[data-category-open]');
    if (!rail || !trigger || root.dataset.categoryPickerReady !== 'true') return;
    const options = originalOptions(root);
    // Restore full list when leaving compact mode or measuring widths.
    options.forEach(option => {
      option.removeAttribute('data-category-collapsed');
      option.style.maxWidth = '';
    });
    root.dataset.categoryOverflow = 'true';
    if ((!compactMedia.matches && options.length <= 8) || options.length < 2) {
      root.dataset.categoryOverflow = 'false';
      return;
    }

    const totalWidth = root.getBoundingClientRect().width;
    if (!totalWidth) return;
    const gap = 8;
    const available = Math.max(60, totalWidth - trigger.getBoundingClientRect().width - gap);
    const maximum = phoneMedia.matches ? 3 : compactMedia.matches ? 5 : 7;
    const selected = options.find(isSelected);
    const priority = [...new Set([selected, options[0], ...options].filter(Boolean))];
    const visible = new Set();
    let used = 0;

    for (const option of priority) {
      if (visible.size >= maximum) break;
      let width = Math.ceil(option.getBoundingClientRect().width);
      // Long selected names stay visible by truncating, not by growing the page.
      if (option === selected && width > available) {
        option.style.maxWidth = `${available}px`;
        width = Math.min(width, available);
      }
      const required = width + (visible.size ? gap : 0);
      if (used + required <= available || (option === selected && !visible.size)) {
        visible.add(option);
        used += required;
      }
    }
    if (!visible.size) visible.add(options[0]);
    options.forEach(option => {
      if (!visible.has(option)) option.dataset.categoryCollapsed = 'true';
    });
    // With few categories, avoid showing a redundant 'Xem thêm' button.
    if (visible.size === options.length) {
      root.dataset.categoryOverflow = 'false';
      options.forEach(option => option.removeAttribute('data-category-collapsed'));
    }
  }

  function isSelected(source) {
    if (source.matches('a')) return source.matches('.selected, [aria-current]');
    return Boolean(source.querySelector('input[type="radio"]:checked'));
  }

  function choose(root, source) {
    const dialog = root.querySelector('dialog');
    dialog?.close();
    if (source.matches('a')) {
      // Reuse Home's delegated AJAX filter handler (with progressive GET fallback).
      source.click();
      return;
    }
    const input = source.querySelector('input[type="radio"]');
    // Native radio click fires change, handled by the existing Community AJAX form.
    input?.click();
    window.requestAnimationFrame(() => syncCompact(root));
  }

  const normalizeLabel = value => String(value || '').normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '').replace(/đ/gi, 'd').toLocaleLowerCase('vi').trim();

  function filterDialog(dialog) {
    const query = normalizeLabel(dialog.querySelector('[data-category-dialog-search]')?.value);
    const buttons = [...dialog.querySelectorAll('[data-category-dialog-options] > button')];
    let matches = 0;
    buttons.forEach(button => {
      button.hidden = Boolean(query) && !normalizeLabel(button.dataset.categoryLabel).includes(query);
      if (!button.hidden) matches += 1;
    });
    const empty = dialog.querySelector('[data-category-dialog-empty]');
    if (empty) empty.hidden = matches > 0;
    const status = dialog.querySelector('[data-category-dialog-status]');
    if (status) status.textContent = `Hiển thị ${matches}/${buttons.length} danh mục`;
  }

  function open(root) {
    initialize(root);
    if (root.dataset.categoryPickerReady !== 'true') return;
    const dialog = root.querySelector('dialog.couponhub-category-dialog');
    const options = dialog.querySelector('[data-category-dialog-options]');
    options.replaceChildren();
    const search = dialog.querySelector('[data-category-dialog-search]');
    if (search) search.value = '';
    const sources = originalOptions(root);
    // The three pages fetch categories from different repository queries.
    // Keep 'All' first, and sort remaining dialog entries consistently (Vietnamese A–Z).
    const sortedSources = [sources[0], ...sources.slice(1).sort((a, b) =>
      a.textContent.trim().localeCompare(b.textContent.trim(), 'vi', { sensitivity: 'base' }))];
    sortedSources.forEach(source => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'couponhub-category-option';
      button.textContent = source.textContent.replace(/\s+/g, ' ').trim();
      button.dataset.categoryLabel = button.textContent;
      const selectedIcon = document.createElement('i');
      selectedIcon.className = 'fa-solid fa-check';
      selectedIcon.setAttribute('aria-hidden', 'true');
      button.append(selectedIcon);
      button.setAttribute('aria-pressed', String(isSelected(source)));
      button.addEventListener('click', () => choose(root, source));
      options.append(button);
    });
    if (dialog.open) return;
    filterDialog(dialog);
    dialog.showModal();
    (search || options.querySelector('[aria-pressed="true"]') || options.querySelector('button'))?.focus();
  }

  // On compact screens, floating contact actions must not cover primary controls.
  // Reuse the category collision handler for the Community search submit button,
  // so scroll/resize still schedule only one geometry check per animation frame.
  let collisionFrame = 0;
  function syncFloatingCollision() {
    collisionFrame = 0;
    const floating = document.querySelector('[data-floating-support]');
    if (!floating) return;
    const actions = floating.querySelector('.floating-support-actions');
    if (!actions) return;
    const floatingRect = actions.getBoundingClientRect();
    const overlaps = window.matchMedia('(max-width: 991.98px)').matches
      && !floating.classList.contains('contact-open')
      && Array.from(document.querySelectorAll('[data-category-open], .community-catalog .community-search-submit')).some(trigger => {
        if (getComputedStyle(trigger).display === 'none') return false;
        const buttonRect = trigger.getBoundingClientRect();
        return buttonRect.left < floatingRect.right + 4
          && buttonRect.right > floatingRect.left - 4
          && buttonRect.top < floatingRect.bottom + 4
          && buttonRect.bottom > floatingRect.top - 4;
      });
    floating.classList.toggle('is-category-overlap', overlaps);
    floating.inert = overlaps;
  }
  function scheduleFloatingCollision() {
    if (!collisionFrame) collisionFrame = window.requestAnimationFrame(syncFloatingCollision);
  }

  function initializeAll() {
    document.querySelectorAll(selector).forEach(initialize);
    scheduleFloatingCollision();
  }

  document.addEventListener('input', event => {
    if (event.target.matches?.('[data-category-dialog-search]')) {
      const dialog = event.target.closest('dialog.couponhub-category-dialog');
      if (dialog) filterDialog(dialog);
    }
  });
  // The Community dialog is inside a GET form: Enter must not submit that form.
  document.addEventListener('keydown', event => {
    if (!event.target.matches?.('[data-category-dialog-search]')) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      event.target.closest('dialog')?.close();
      return;
    }
    if (event.key !== 'Enter') return;
    event.preventDefault();
    event.target.closest('dialog')?.querySelector('[data-category-dialog-options] > button:not([hidden])')?.click();
  });

  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-category-open]');
    if (trigger) {
      const root = trigger.closest(selector);
      if (root) open(root);
      return;
    }
    const close = event.target.closest('[data-category-close]');
    if (close) close.closest('dialog')?.close();
  });

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeAll, { once: true });
  else initializeAll();
  // Home AJAX replaces #discovery-results; initialize the newly rendered rail.
  window.addEventListener('couponhub:catalog-updated', initializeAll);
  // Resize can fire dozens of times per frame. Measure category widths once
  // per animation frame rather than forcing layout for every resize event.
  let resizeFrame = 0;
  function syncCategoryResize() {
    resizeFrame = 0;
    document.querySelectorAll(selector).forEach(root => {
      const dialog = root.querySelector('dialog');
      syncCompact(root);
      if (root.dataset.categoryOverflow === 'false' && dialog?.open) dialog.close();
    });
    scheduleFloatingCollision();
  }
  function scheduleCategoryResize() {
    if (!resizeFrame) resizeFrame = window.requestAnimationFrame(syncCategoryResize);
  }
  window.addEventListener('resize', scheduleCategoryResize, { passive: true });
  // Community radio selection changes in-place; Home and Categories use links.
  document.addEventListener('change', event => {
    const root = event.target.closest?.(selector);
    if (root && event.target.matches('input[type="radio"][name="category_id"]')) {
      window.requestAnimationFrame(() => syncCompact(root));
    }
  });
  window.addEventListener('scroll', scheduleFloatingCollision, { passive: true });
})();

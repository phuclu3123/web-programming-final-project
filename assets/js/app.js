/* CouponHub progressive enhancement.
 * Core GET/POST flows continue to work with JavaScript disabled.
 */
(() => {
  'use strict';

  document.documentElement.classList.add('js');

  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const get = id => document.getElementById(id);

  let toastTimer = null;
  let searchTimer = null;
  let searchRequest = null;
  let revealObserver = null;
  let publicNavObserver = null;

  function showToast(message) {
    const toast = get('copy-toast');
    const text = get('copy-toast-text');
    if (!toast || !text) return;

    text.textContent = message;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2200);
  }

  async function copyText(value) {
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(value);
      return;
    }

    const helper = document.createElement('textarea');
    helper.value = value;
    helper.setAttribute('readonly', '');
    helper.style.position = 'fixed';
    helper.style.opacity = '0';
    document.body.appendChild(helper);
    helper.select();
    const success = document.execCommand('copy');
    helper.remove();
    if (!success) throw new Error('Copy command failed');
  }

  function initCopyButtons() {
    document.addEventListener('click', async event => {
      const button = event.target.closest('[data-copy-code]');
      if (!button) return;

      const code = button.dataset.copyCode?.trim();
      if (!code) return;

      const label = $('span:last-child', button);
      const original = label?.textContent || 'Sao chép';

      try {
        await copyText(code);
        if (label) label.textContent = 'Đã chép';
        button.classList.add('copied');
        showToast(`Đã sao chép mã ${code}`);

        setTimeout(() => {
          if (label) label.textContent = original;
          button.classList.remove('copied');
        }, 1500);
      } catch (error) {
        console.error('Không thể sao chép mã:', error);
        showToast('Không thể sao chép mã. Vui lòng thử lại.');
      }
    });
  }

  function cleanParams(form) {
    const params = new URLSearchParams(new FormData(form));
    if ((params.get('q') || '').trim() === '') params.delete('q');
    if (params.get('category') === '0') params.delete('category');
    if (params.get('merchant') === '0') params.delete('merchant');
    if (!params.get('type')) params.delete('type');
    if (!params.get('channel')) params.delete('channel');
    if (params.get('ending') !== '1') params.delete('ending');
    if (params.get('sort') === 'newest') params.delete('sort');
    return params;
  }

  function syncHomeForm(fromDocument, preserveSearch = false) {
    const current = get('home-filter-form');
    const incoming = fromDocument.querySelector('#home-filter-form');
    if (!current || !incoming) return;

    ['q', 'category', 'merchant', 'type', 'channel', 'ending', 'sort'].forEach(name => {
      if (name === 'q' && preserveSearch) return;
      const currentField = current.elements.namedItem(name);
      const incomingField = incoming.elements.namedItem(name);
      if (currentField && incomingField) currentField.value = incomingField.value;
    });

    const clear = $('.search-clear', current);
    const incomingClear = $('.search-clear', incoming);
    if (clear && incomingClear) {
      clear.hidden = current.elements.namedItem('q').value.trim() === '';
      clear.setAttribute('href', incomingClear.getAttribute('href') || 'index.php#coupons');
    }
  }

  async function updateHome(url, { historyMode = 'push', keepFocus = false, scrollToResults = false } = {}) {
    const currentResults = get('discovery-results');
    if (!currentResults) {
      window.location.href = url;
      return;
    }

    if (searchRequest) searchRequest.abort();
    const controller = new AbortController();
    searchRequest = controller;

    const target = new URL(url, window.location.href);
    target.hash = '';

    document.body.classList.add('is-filtering');
    currentResults.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(target.toString(), {
        method: 'GET',
        headers: { 'X-Requested-With': 'CouponHub' },
        signal: controller.signal,
      });

      if (!response.ok) throw new Error(`HTTP ${response.status}`);

      const html = await response.text();
      if (keepFocus && (get('q')?.value.trim() || '') !== (target.searchParams.get('q') || '').trim()) return;
      const parsed = new DOMParser().parseFromString(html, 'text/html');
      const incomingResults = parsed.querySelector('#discovery-results');
      if (!incomingResults) throw new Error('Missing discovery results');

      syncHomeForm(parsed, keepFocus);
      const hasEstimate = Boolean(incomingResults.querySelector('#estimate'));
      $$('[data-estimate-link]').forEach(link => { link.hidden = !hasEstimate; });
      const estimateAction = $('[data-estimate-action]');
      if (estimateAction) {
        estimateAction.href = hasEstimate ? '#estimate' : '#coupons';
        const label = $('span', estimateAction);
        if (label) label.textContent = hasEstimate ? 'Thử tính ưu đãi' : 'Xem ưu đãi';
      }
      const currentFeature = $('.hero-art');
      const incomingFeature = parsed.querySelector('.hero-art');
      if (currentFeature && incomingFeature) currentFeature.replaceWith(incomingFeature);
      currentResults.replaceWith(incomingResults);
      initScrollReveal(incomingResults);
      initPublicScrollSpy();

      const finalUrl = new URL(url, window.location.href);
      const nextUrl = finalUrl.pathname + finalUrl.search + '#coupons';
      if (historyMode === 'push') history.pushState({ couponhub: true }, '', nextUrl);
      if (historyMode === 'replace') history.replaceState({ couponhub: true }, '', nextUrl);

      if (scrollToResults) {
        get('coupons')?.scrollIntoView({
          behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
          block: 'start',
        });
      }

      if (keepFocus) {
        const search = get('q');
        if (search) {
          search.focus({ preventScroll: true });
          const end = search.value.length;
          search.setSelectionRange(end, end);
        }
      }
    } catch (error) {
      if (error.name === 'AbortError') return;
      console.error('Không thể cập nhật danh sách coupon:', error);
      window.location.href = url;
    } finally {
      if (searchRequest === controller) {
        document.body.classList.remove('is-filtering');
        get('discovery-results')?.removeAttribute('aria-busy');
        searchRequest = null;
      }
    }
  }

  function initHome() {
    const form = get('home-filter-form');
    if (!form) return;

    const search = get('q');

    form.addEventListener('submit', event => {
      event.preventDefault();
      const params = cleanParams(form);
      updateHome(`index.php${params.toString() ? '?' + params : ''}#coupons`, { keepFocus: true });
    });

    document.addEventListener('submit', event => {
      const filterForm = event.target.closest('[data-catalog-filter-form]');
      if (!filterForm || !get('home-filter-form')) return;
      event.preventDefault();
      const params = cleanParams(filterForm);
      updateHome(`index.php${params.toString() ? '?' + params : ''}#coupons`, { scrollToResults: true });
    });

    search?.addEventListener('input', event => {
      if (event.isComposing) return;
      const clear = $('.search-clear', form);
      if (clear) clear.hidden = search.value.trim() === '';
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        const params = cleanParams(form);
        updateHome(`index.php${params.toString() ? '?' + params : ''}#coupons`, { historyMode: 'replace', keepFocus: true });
      }, 350);
    });

    document.addEventListener('click', event => {
      const link = event.target.closest('[data-home-filter]');
      if (!link || !get('home-filter-form')) return;
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;

      const target = new URL(link.href, window.location.href);
      if (target.origin !== window.location.origin) return;
      event.preventDefault();
      clearTimeout(searchTimer);
      updateHome(target.toString(), { scrollToResults: true });
    });

    document.addEventListener('change', event => {
      if (event.target.id !== 'sort-select' || !get('home-filter-form')) return;
      const mainForm = get('home-filter-form');
      mainForm.elements.namedItem('sort').value = event.target.value;
      const params = cleanParams(mainForm);
      updateHome(`index.php${params.toString() ? '?' + params : ''}#coupons`, { scrollToResults: true });
    });

    window.addEventListener('popstate', () => {
      if (get('home-filter-form')) updateHome(window.location.href, { historyMode: 'none' });
    });
  }

  function initShopPreview() {
    const form = get('coupon-form');
    if (!form) return;

    const field = id => get(id);
    const setText = (id, value) => {
      const node = get(id);
      if (node) node.textContent = value;
    };
    const formatMoney = value => new Intl.NumberFormat('vi-VN').format(value) + 'đ';

    function preview() {
      const percentage = field('discount-type').value === 'PERCENTAGE';
      const value = Number(field('discount-value').value || 0);
      field('discount-value').max = percentage ? '100' : '1000000000';
      field('max-discount').disabled = !percentage;
      field('end-date').min = field('start-date').value;

      setText('discount-unit', percentage ? '(%)' : '(đ)');
      setText(
        'preview-saving',
        'Giảm ' + new Intl.NumberFormat('vi-VN').format(value) + (percentage ? '%' : 'đ')
      );
      const merchantName = field('merchant-select').selectedOptions[0]?.textContent.trim() || 'Cửa hàng';
      setText('preview-merchant', merchantName);
      setText('preview-logo', Array.from(merchantName).slice(0, 2).join(''));
      setText('preview-category', field('coupon-category').selectedOptions[0]?.textContent.trim() || 'Danh mục');
      setText('preview-title', field('coupon-title').value.trim() || 'Tên ưu đãi của bạn');
      setText('preview-description', field('coupon-description').value.trim() || 'Mô tả ngắn giúp khách hàng hiểu giá trị ưu đãi.');
      setText('preview-code', field('coupon-code').value.trim().toUpperCase() || 'MÃ CỦA BẠN');
      setText('preview-terms', field('coupon-terms').value.trim() || 'Điều kiện áp dụng sẽ hiển thị tại đây.');
      const minOrder = Number(field('min-order').value || 0);
      setText('preview-min-order', minOrder > 0 ? 'Đơn từ ' + formatMoney(minOrder) : 'Không yêu cầu đơn tối thiểu');
      const maxDiscount = Number(field('max-discount').value || 0);
      const maxPreview = field('preview-max-discount');
      if (maxPreview) {
        maxPreview.hidden = !percentage || maxDiscount <= 0;
        setText('preview-max-discount', 'Tối đa ' + formatMoney(maxDiscount));
      }
      const channel = field('usage-channel').selectedOptions[0]?.textContent.trim() || 'Chưa chọn';
      setText('preview-channel', channel);

      const end = field('end-date').value;
      setText('preview-expiry', end ? 'Đến ' + end.split('-').reverse().join('/') : 'Chọn ngày kết thúc');
    }

    field('coupon-code')?.addEventListener('input', event => {
      const caret = event.target.selectionStart;
      event.target.value = event.target.value.toUpperCase().replace(/[^A-Z0-9_-]/g, '');
      event.target.setSelectionRange(caret, caret);
    });

    form.addEventListener('input', preview);
    form.addEventListener('change', preview);
    preview();
  }

  function initShopTable() {
    const search = get('shop-coupon-search');
    const rows = $$('[data-shop-row]');
    const tabs = $$('[data-shop-status]');
    const count = get('shop-visible-count');
    const empty = get('shop-filter-empty');
    if (!search || rows.length === 0) return;

    let status = 'ALL';

    function apply() {
      const query = search.value.trim().toLocaleLowerCase('vi');
      let visible = 0;

      rows.forEach(row => {
        const matchesStatus = status === 'ALL' || row.dataset.status === status;
        const haystack = (row.dataset.search || '').toLocaleLowerCase('vi');
        const matchesQuery = !query || haystack.includes(query);
        const show = matchesStatus && matchesQuery;
        row.hidden = !show;
        if (show) visible += 1;
      });

      tabs.forEach(tab => tab.setAttribute('aria-pressed', String(tab.dataset.shopStatus === status)));
      if (count) count.textContent = String(visible);
      if (empty) empty.hidden = visible !== 0;
    }

    tabs.forEach(tab => tab.addEventListener('click', () => {
      status = tab.dataset.shopStatus || 'ALL';
      apply();
    }));
    search.addEventListener('input', apply);
    apply();
  }

  function initAdminQueue() {
    const input = get('admin-queue-search');
    const items = $$('[data-queue-item]');
    const count = get('admin-visible-count');
    const empty = get('admin-filter-empty');
    if (!input || items.length === 0) return;

    function apply() {
      const query = input.value.trim().toLocaleLowerCase('vi');
      let visible = 0;
      items.forEach(item => {
        const haystack = (item.dataset.search || '').toLocaleLowerCase('vi');
        const show = !query || haystack.includes(query);
        item.hidden = !show;
        if (show) visible += 1;
      });
      if (count) count.textContent = String(visible);
      if (empty) empty.hidden = visible !== 0;
    }

    input.addEventListener('input', apply);
    apply();
  }

  function initWorkspaceNav() {
    const links = $$('.side-nav a[href^="#"], .mobile-workspace-nav a[href^="#"]');
    if (!links.length) return;

    function sync() {
      const target = get(window.location.hash.slice(1)) ? window.location.hash : '#overview';
      links.forEach(link => {
        const active = link.getAttribute('href') === target;
        link.classList.toggle('active', active);
        if (active) link.setAttribute('aria-current', 'location');
        else link.removeAttribute('aria-current');
      });
    }

    window.addEventListener('hashchange', sync);
    sync();
  }

  function initSavingsEstimate() {
    const formatter = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 });
    const money = value => formatter.format(Math.round(Number(value) || 0)) + 'đ';

    function calculate(lab) {
      const input = $('#order-value', lab);
      const output = $('#estimated-saving', lab);
      const note = $('#estimate-note', lab);
      if (!input || !output || !note) return;

      const amount = Number(input.value);
      const minimum = Number(lab.dataset.minOrder || 0);
      const value = Number(lab.dataset.discountValue || 0);
      const maximum = lab.dataset.maxDiscount === '' ? Infinity : Number(lab.dataset.maxDiscount);
      if (input.value === '' || !Number.isFinite(amount) || amount < 0) {
        output.textContent = '—';
        note.textContent = 'Nhập giá trị đơn hàng để xem mức giảm dự kiến.';
        return;
      }
      if (amount < minimum) {
        output.textContent = '0đ';
        note.textContent = 'Đơn hàng cần đạt tối thiểu ' + formatter.format(minimum) + 'đ để dùng mã.';
        return;
      }

      const raw = lab.dataset.discountType === 'PERCENTAGE' ? amount * value / 100 : Math.min(amount, value);
      const saving = Math.max(0, Math.min(raw, maximum));
      output.textContent = formatter.format(Math.round(saving)) + 'đ';
      note.textContent = 'Ước tính theo mức giảm và giới hạn của mã.';
    }

    document.addEventListener('input', event => {
      if (event.target.id !== 'order-value') return;
      const lab = event.target.closest('.savings-lab');
      if (lab) calculate(lab);
    });

    document.addEventListener('change', event => {
      if (event.target.id !== 'estimate-coupon') return;
      const select = event.target;
      const lab = select.closest('.savings-lab');
      const option = select.options[select.selectedIndex];
      if (!lab || !option) return;

      lab.dataset.discountType = option.dataset.discountType || '';
      lab.dataset.discountValue = option.dataset.discountValue || '0';
      lab.dataset.minOrder = option.dataset.minOrder || '0';
      lab.dataset.maxDiscount = option.dataset.maxDiscount || '';

      const code = $('#estimate-code', lab);
      const savingValue = $('#estimate-saving-value', lab);
      const minimum = $('#estimate-minimum', lab);
      const maximum = $('#estimate-maximum', lab);
      const maximumRow = $('#estimate-maximum-row', lab);
      const copyButton = $('#estimate-copy-code', lab);

      if (code) code.textContent = option.dataset.code || option.value;
      if (savingValue) savingValue.textContent = option.dataset.savingLabel || '—';
      if (minimum) minimum.textContent = money(option.dataset.minOrder);
      if (maximum) maximum.textContent = money(option.dataset.maxDiscount);
      if (maximumRow) maximumRow.hidden = !option.dataset.maxDiscount;
      if (copyButton) copyButton.dataset.copyCode = option.dataset.code || option.value;

      calculate(lab);
    });
  }

  function initScrollReveal(root = document) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reducedMotion) document.documentElement.classList.add('reveal-motion');
    const revealGroups = [
      ['.market-snapshot > *', 28],
      ['.coupon-grid .coupon-card', 36],
      ['.store-gallery .store-showcase', 32],
      ['.footer-nav > div', 28],
    ];
    const singleSelectors = [
      '.discovery-heading',
      '.category-strip',
      '.results-toolbar',
      '.savings-lab-copy',
      '.savings-sheet',
      '.stores-intro',
      '.footer-statement',
    ];

    if (!revealObserver && !reducedMotion && 'IntersectionObserver' in window) {
      revealObserver = new IntersectionObserver(entries => {
        entries.forEach(entry => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add('is-revealed');
          revealObserver.unobserve(entry.target);
        });
      }, { threshold: 0.08, rootMargin: '0px 0px -3% 0px' });
    }

    const register = (element, delay = 0) => {
      if (!element || element.dataset.revealReady === 'true') return;
      element.dataset.revealReady = 'true';
      element.classList.add('reveal-item');
      element.style.setProperty('--reveal-delay', `${delay}ms`);
      if (reducedMotion || !revealObserver) element.classList.add('is-revealed');
      else revealObserver.observe(element);
    };

    singleSelectors.forEach(selector => $$(selector, root).forEach(element => register(element)));
    revealGroups.forEach(([selector, step]) => {
      $$(selector, root).forEach((element, index) => register(element, Math.min(index, 3) * step));
    });
  }

  function initPublicScrollSpy() {
    const links = $$('.site-nav a[href^="#"]');
    if (!links.length || !('IntersectionObserver' in window)) return;

    if (publicNavObserver) publicNavObserver.disconnect();
    const sectionLinks = links
      .map(link => ({ link, section: get(link.getAttribute('href').slice(1)) }))
      .filter(item => item.section);

    publicNavObserver = new IntersectionObserver(entries => {
      const visible = entries
        .filter(entry => entry.isIntersecting)
        .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
      if (!visible) return;
      links.forEach(link => link.classList.toggle('active', link.getAttribute('href') === `#${visible.target.id}`));
    }, { threshold: [0.12, 0.35, 0.6], rootMargin: '-22% 0px -58% 0px' });

    sectionLinks.forEach(({ section }) => publicNavObserver.observe(section));
  }

  function initFloatingSupport() {
    const shell = $('[data-floating-support]');
    const trigger = $('[data-contact-trigger]', shell || document);
    const close = $('[data-contact-close]', shell || document);
    const panel = get('contact-popover');
    const topButton = $('[data-back-to-top]', shell || document);
    if (!shell || !trigger || !panel || !topButton) return;

    const setContactOpen = open => {
      shell.classList.toggle('contact-open', open);
      trigger.setAttribute('aria-expanded', String(open));
      panel.setAttribute('aria-hidden', String(!open));
      panel.inert = !open;
      if (open) $('.contact-option', panel)?.focus({ preventScroll: true });
    };

    trigger.addEventListener('click', () => {
      setContactOpen(trigger.getAttribute('aria-expanded') !== 'true');
    });
    close?.addEventListener('click', () => {
      setContactOpen(false);
      trigger.focus({ preventScroll: true });
    });
    panel.addEventListener('click', event => {
      if (event.target.closest('.contact-option')) setContactOpen(false);
    });
    document.addEventListener('click', event => {
      if (!shell.contains(event.target)) setContactOpen(false);
    });
    document.addEventListener('keydown', event => {
      if (event.key !== 'Escape' || trigger.getAttribute('aria-expanded') !== 'true') return;
      setContactOpen(false);
      trigger.focus({ preventScroll: true });
    });

    let scrollFrame = 0;
    const syncTopButton = () => {
      const visible = window.scrollY > Math.min(560, window.innerHeight * 0.7);
      topButton.classList.toggle('is-visible', visible);
      topButton.tabIndex = visible ? 0 : -1;
      topButton.setAttribute('aria-hidden', String(!visible));
      scrollFrame = 0;
    };
    window.addEventListener('scroll', () => {
      if (!scrollFrame) scrollFrame = window.requestAnimationFrame(syncTopButton);
    }, { passive: true });
    topButton.addEventListener('click', () => {
      window.scrollTo({
        top: 0,
        behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
      });
    });

    setContactOpen(false);
    syncTopButton();
  }

  initCopyButtons();
  initHome();
  initShopPreview();
  initShopTable();
  initAdminQueue();
  initWorkspaceNav();
  initSavingsEstimate();
  initScrollReveal();
  initPublicScrollSpy();
  initFloatingSupport();
})();

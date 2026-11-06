/* Shared floating actions for both public PHP layouts. */
(() => {
  'use strict';

  const shell = document.querySelector('[data-floating-support]');
  if (!shell) return;

  const trigger = shell.querySelector('[data-contact-trigger]');
  const panel = shell.querySelector('#contact-popover');
  const close = shell.querySelector('[data-contact-close]');
  const topButton = shell.querySelector('[data-back-to-top]');
  if (!trigger || !panel || !topButton) return;

  const setContactOpen = (open, restoreFocus = false) => {
    shell.classList.toggle('contact-open', open);
    trigger.setAttribute('aria-expanded', String(open));
    panel.setAttribute('aria-hidden', String(!open));
    panel.inert = !open;
    if (open) panel.querySelector('.contact-option')?.focus({ preventScroll: true });
    else if (restoreFocus) trigger.focus({ preventScroll: true });
  };

  trigger.addEventListener('click', () => {
    setContactOpen(trigger.getAttribute('aria-expanded') !== 'true');
  });
  close?.addEventListener('click', () => setContactOpen(false, true));
  panel.addEventListener('click', event => {
    if (event.target.closest('.contact-option')) setContactOpen(false, true);
  });
  document.addEventListener('click', event => {
    if (!shell.contains(event.target)) setContactOpen(false);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') {
      setContactOpen(false, true);
    }
  });

  let scrollFrame = 0;
  const syncTopButton = () => {
    const visible = window.scrollY > Math.min(240, window.innerHeight * 0.4);
    shell.classList.toggle('has-top-action', visible);
    topButton.classList.toggle('is-visible', visible);
    topButton.tabIndex = visible ? 0 : -1;
    topButton.setAttribute('aria-hidden', String(!visible));
    scrollFrame = 0;
  };
  window.addEventListener('scroll', () => {
    if (!scrollFrame) scrollFrame = window.requestAnimationFrame(syncTopButton);
  }, { passive: true });
  window.addEventListener('resize', syncTopButton);
  window.addEventListener('pageshow', syncTopButton);
  topButton.addEventListener('click', event => {
    setContactOpen(false);
    window.scrollTo({
      top: 0,
      behavior: event?.detail === 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
    });
  });

  setContactOpen(false);
  syncTopButton();
})();

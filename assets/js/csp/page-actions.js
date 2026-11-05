'use strict';

// Event delegation preserves confirmation for click and keyboard form submission.
document.addEventListener('submit', (event) => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement)) return;
  const message = form.getAttribute('data-confirm-submit');
  if (message !== null && !window.confirm(message)) {
    event.preventDefault();
  }
});

// Preserve the old select.form.submit() behavior (no extra validation event).
document.addEventListener('change', (event) => {
  const element = event.target;
  if (!(element instanceof HTMLSelectElement)) return;
  if (element.matches('[data-auto-submit]') && element.form) {
    element.form.submit();
  }
});

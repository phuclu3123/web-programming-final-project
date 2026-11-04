(() => {
  'use strict';
  const menu = document.querySelector('[data-workspace-menu]');
  if (!menu) return;
  const desktop = window.matchMedia('(min-width: 1100px)');
  const sync = () => { menu.open = desktop.matches; };
  sync();
  desktop.addEventListener('change', sync);
})();

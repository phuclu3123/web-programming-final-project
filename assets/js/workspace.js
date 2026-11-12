// Điều khiển thành phần workspace; không thay thế kiểm tra quyền ở backend.
(() => {
  'use strict';
  const menu = document.querySelector('[data-workspace-menu]');
  if (!menu) return;
  const desktop = window.matchMedia('(min-width: 1100px)');
  const sync = () => { menu.open = desktop.matches; };
  sync();
  desktop.addEventListener('change', sync);
})();

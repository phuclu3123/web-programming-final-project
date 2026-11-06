/* Manual gallery only; native scrolling remains available without JavaScript. */
document.querySelectorAll('[data-event-gallery]').forEach(gallery => {
    const rail = gallery.querySelector('.event-more-list');
    const previous = gallery.querySelector('[data-event-prev]');
    const next = gallery.querySelector('[data-event-next]');
    if (!rail || !previous || !next) return;
    const update = () => {
        previous.disabled = rail.scrollLeft < 2;
        next.disabled = rail.scrollLeft + rail.clientWidth >= rail.scrollWidth - 2;
    };
    const move = (direction, keyboard) => {
        const card = rail.querySelector('.event-feature');
        const gap = parseFloat(getComputedStyle(rail).columnGap) || 24;
        rail.scrollBy({left: direction * ((card?.getBoundingClientRect().width || rail.clientWidth) + gap), behavior: keyboard || matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'});
    };
    previous.addEventListener('click', event => move(-1, event.detail === 0));
    next.addEventListener('click', event => move(1, event.detail === 0));
    rail.addEventListener('scroll', update, {passive: true});
    if ('ResizeObserver' in window) new ResizeObserver(update).observe(rail);
    update();
});

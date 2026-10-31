/* Quiet navigation and the independently controlled brand strip. */
document.documentElement.classList.add('editorial-ready');

// Remote banners can disappear after publication. Fall back once, without loops.
document.addEventListener('error', event => {
  const picture=event.target;
  if (picture.tagName!=='IMG' || !picture.dataset.campaignFallback) return;
  const fallback=picture.dataset.campaignFallback;
  delete picture.dataset.campaignFallback;
  if (picture.getAttribute('src')!==fallback) picture.src=fallback;
}, true);

// Preview follows deliberate pointer or keyboard navigation; links stay native.
function previewCategory(event) {
  const link=event.target.closest('[data-category-visual]');
  if (!link || (event.type==='pointerover' && event.pointerType==='touch')) return;
  const directory=link.closest('.category-directory');
  if (!directory) return;
  const picture=directory.querySelector('[data-category-preview]');
  if (!picture || picture.getAttribute('src')===link.dataset.categoryVisual) return;
  picture.src=link.dataset.categoryVisual;
  directory.querySelector('[data-category-preview-name]').textContent=link.dataset.categoryName;
  directory.querySelector('[data-category-preview-caption]').textContent=link.dataset.categoryCaption;
}
document.addEventListener('pointerover',previewCategory);
document.addEventListener('focusin',previewCategory);
document.addEventListener('click',event => {
  const more=event.target.closest('[data-catalog-more]');
  if (more) {
    const section=more.closest('.discovery-section');
    section.querySelectorAll('.coupon-card[hidden]').forEach(card=>{card.hidden=false;});
    more.closest('.catalog-more').remove();
    return;
  }

});

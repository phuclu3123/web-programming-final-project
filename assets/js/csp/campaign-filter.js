'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('campaign-coupon-filter');

    if (!form) {
        return;
    }

    const categorySelect = document.getElementById('campaign-category');
    const channelSelect = document.getElementById('campaign-channel');
    const platformSelect = document.getElementById('campaign-platform');
    const results = document.getElementById('campaign-coupon-results');
    const resetButton = document.getElementById('reset-campaign-filter');
    const feedback = document.querySelector('.campaign-filter-feedback');

    let requestNumber = 0;

    async function filterCoupons() {
        const currentRequest = ++requestNumber;

        const url = new URL(form.action, window.location.href);

        url.searchParams.set('category_id', categorySelect.value);
        url.searchParams.set('usage_channel', channelSelect.value);
        url.searchParams.set('platform', platformSelect.value);

        results.classList.add('opacity-50');
        results.setAttribute('aria-busy', 'true');
        feedback.hidden = true;

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Không thể tải danh sách coupon.');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const newDocument = parser.parseFromString(html, 'text/html');

            const newResults = newDocument.querySelector(
                '#campaign-coupon-results'
            );

            const newTitleCount = newDocument.querySelector(
                '.campaign-offers-section .studio-section-title > span'
            );

            if (!newResults) {
                throw new Error('Không tìm thấy danh sách coupon.');
            }

            // Bỏ qua kết quả của yêu cầu cũ nếu người dùng đổi bộ lọc liên tục.
            if (currentRequest !== requestNumber) {
                return;
            }

            // Chỉ cập nhật danh sách coupon, không tải lại toàn bộ trang.
            results.innerHTML = newResults.innerHTML;
            const newAction = newDocument.querySelector('.campaign-detail-action');
            const currentAction = document.querySelector('.campaign-detail-action');
            if (newAction && currentAction) currentAction.innerHTML = newAction.innerHTML;

            if (newTitleCount) {
                const titleCount = document.querySelector(
                    '.campaign-offers-section .studio-section-title > span'
                );

                if (titleCount) {
                    titleCount.textContent = newTitleCount.textContent;
                }
            }

            // Đồng bộ URL để có thể sao chép đường dẫn bộ lọc.
            window.history.replaceState({}, '', url.toString());

        } catch (error) {
            if (currentRequest === requestNumber) {
                feedback.textContent = 'Không thể lọc coupon. Vui lòng thử lại.';
                feedback.hidden = false;
            }
        } finally {
            if (currentRequest === requestNumber) {
                results.classList.remove('opacity-50');
                results.removeAttribute('aria-busy');
            }
        }
    }

    // Tự lọc khi người dùng thay đổi một trong hai lựa chọn.
    categorySelect.addEventListener('change', filterCoupons);
    channelSelect.addEventListener('change', filterCoupons);
    platformSelect.addEventListener('change', filterCoupons);
    form.addEventListener('submit', function (event) { event.preventDefault(); filterCoupons(); });

    // Đặt lại cả hai bộ lọc mà không reload trang.
    resetButton.addEventListener('click', function (event) {
        event.preventDefault();
        categorySelect.value = '0';
        channelSelect.value = 'ALL';
        platformSelect.value = '0';

        filterCoupons();
    });
});

(function ($) {
    'use strict';

    var $form = $('#coupon-search-form');
    if (!$form.length) return;

    var $results = $('#coupon-results');
    var $empty = $('#coupon-empty');
    var $status = $('#coupon-search-status');
    var $pagination = $('#coupon-pagination');
    var $page = $('#coupon-page');
    var navigating = false;
    var lastRequest = null;
    if (window.location.hash === '#keyword') document.getElementById('keyword').focus();

    function render(response) {
        var items = response.data || [];
        $results.html(response.cardsHtml || '');
        $empty.toggleClass('d-none', items.length > 0);
    }

    function buildQuery(page) {
        var data = $form.serializeArray().filter(function (field) { return field.name !== 'page'; });
        data.push({ name: 'page', value: String(page) });
        return $.param(data);
    }

    function paginationLink(page, label, active, disabled, aria) {
        var $li = $('<li>').addClass('page-item');
        if (active) $li.addClass('active');
        if (disabled) $li.addClass('disabled');
        var $a = $('<a>').addClass('page-link').attr('href', $form.attr('action') + '?' + buildQuery(page)).attr('data-page', page).text(label);
        if (active) $a.attr('aria-current', 'page');
        if (aria) $a.attr('aria-label', aria);
        return $li.append($a);
    }

    function renderPagination(p) {
        var current = Number(p.page) || 1;
        var totalPages = Number(p.totalPages) || 1;
        if (totalPages <= 1) {
            $pagination.addClass('d-none').empty();
            return;
        }
        $pagination.removeClass('d-none');
        var $ul = $('<ul>').addClass('pagination justify-content-center flex-wrap mt-3');
        $ul.append(paginationLink(Math.max(1, current - 1), 'Trước', false, current === 1, 'Trang trước'));
        var start = Math.max(1, Math.min(current - 2, totalPages - 4));
        var end = Math.min(totalPages, start + 4);
        for (var i = start; i <= end; i++) {
            $ul.append(paginationLink(i, i, i === current, false, ''));
        }
        $ul.append(paginationLink(Math.min(totalPages, current + 1), 'Sau', false, current >= totalPages, 'Trang sau'));
        $pagination.empty().append($ul);
    }

    $pagination.on('click', 'a[data-page]', function (event) {
        if ($(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) {
            event.preventDefault();
            return;
        }
        event.preventDefault();
        $page.val($(this).data('page'));
        navigating = true;
        $form.trigger('submit');
    });

    $form.on('submit', function (event) {
        event.preventDefault();
        if (!navigating) $page.val(1);
        navigating = false;
        var query = $form.serialize();
        if (lastRequest) lastRequest.abort();
        $status.text('Đang tìm coupon...');
        $results.attr('aria-busy', 'true');

        lastRequest = window.CouponHub.ajax({
            url: $form.data('api-url'),
            method: 'GET',
            data: query
        }).done(function (response) {
            if (!response || response.success !== true) {
                $status.text('Không thể tải danh sách coupon.');
                return;
            }
            render(response);
            var p = response.pagination || { page: 1, totalPages: 1, total: (response.data || []).length };
            $page.val(p.page);
            renderPagination(p);
            $status.text(p.total + ' ưu đãi phù hợp · Trang ' + p.page + '/' + p.totalPages);
            $('[data-community-total]').text(p.total);
            var filters = response.filters || {};
            var count = ['merchant_id', 'discount_type', 'usage_channel', 'ending_soon'].filter(function (key) { return Boolean(filters[key]); }).length;
            $form.find('.community-filter-count').text(count).prop('hidden', count === 0);
            var browserUrl = $form.attr('action') + '?' + buildQuery(p.page);
            if (window.location.href !== new URL(browserUrl, window.location.href).href) {
                window.history.pushState({}, '', browserUrl);
            }
            if (Number(p.page) > 1) {
                document.getElementById('coupon-search-status').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') return;
            var response = xhr.responseJSON || {};
            $status.text(response.message || 'Có lỗi khi tìm coupon. Bạn có thể tải lại trang để dùng tìm kiếm không AJAX.');
        }).always(function () {
            $results.removeAttr('aria-busy');
        });
    });

    // Danh mục và sắp xếp áp dụng ngay; bộ lọc phụ có nút Áp dụng rõ ràng.
    $form.on('change', '[name="category_id"], #sort', function () { $form.trigger('submit'); });

    // Restore the server URL/state when the user navigates back or forward.
    $(window).on('popstate', function () { window.location.reload(); });
})(jQuery);

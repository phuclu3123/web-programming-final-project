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

    function escapeHtml(value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function money(value) {
        if (value === null || value === '' || typeof value === 'undefined') return '—';
        return new Intl.NumberFormat('vi-VN').format(Number(value)) + ' ₫';
    }

    function discount(c) {
        switch (c.discountType) {
            case 'PERCENTAGE': return 'Giảm ' + Number(c.discountValue) + '%';
            case 'FIXED_AMOUNT': return 'Giảm ' + money(c.discountValue);
            case 'FREE_SHIPPING': return 'Miễn phí vận chuyển';
            case 'BUY_X_GET_Y': return 'Mua X tặng Y';
            case 'GIFT': return 'Quà tặng';
            case 'CASHBACK': return 'Hoàn tiền ' + (c.discountValue == null ? '' : Number(c.discountValue));
            default: return 'Ưu đãi';
        }
    }

    function categoryClass(name) {
        var key = String(name || '').trim().toLowerCase();
        var map = {
            'mua sắm': 'badge-category-shopping',
            'ăn uống': 'badge-category-food',
            'du lịch': 'badge-category-travel',
            'công nghệ': 'badge-category-tech',
            'giải trí': 'badge-category-entertainment',
            'làm đẹp': 'badge-category-beauty',
            'dịch vụ': 'badge-category-service',
            'thời trang': 'badge-category-fashion'
        };
        return map[key] || 'badge-category-default';
    }

    function usageInfo(value) {
        switch (String(value || '').toUpperCase()) {
            case 'ONLINE': return {label: 'Online', css: 'badge-usage-online'};
            case 'OFFLINE': return {label: 'Offline', css: 'badge-usage-offline'};
            case 'BOTH': return {label: 'Online + Offline', css: 'badge-usage-both'};
            default: return {label: 'Không rõ', css: 'badge-usage-default'};
        }
    }

    function render(items) {
        if (!items.length) {
            $results.empty();
            $empty.removeClass('d-none');
            return;
        }
        $empty.addClass('d-none');

        var html = items.map(function (c) {
            var official = c.isOfficial ? '<span class="badge badge-primary">Official Deal</span>' : '';
            var code = c.code ? '<div class="coupon-code mb-3"><code>' + escapeHtml(c.code) + '</code></div>' : '';
            var trust = c.trustScore === null ? 'Chưa đủ dữ liệu' : escapeHtml(c.trustScore) + '%';
            var usage = usageInfo(c.usageChannel);
            var badges = '<span class="badge-soft ' + categoryClass(c.categoryName) + '">' + escapeHtml(c.categoryName || 'Khác') + '</span>' +
                '<span class="badge-soft ' + usage.css + '">' + escapeHtml(usage.label) + '</span>';

            return '<div class="col-md-6 col-lg-4 mb-4">' +
                '<div class="card coupon-card h-100 shadow-sm"><div class="card-body d-flex flex-column">' +
                '<div class="mb-2 d-flex flex-wrap align-items-center" style="gap:.35rem">' + official + badges + '</div>' +
                '<h2 class="h5 mb-1">' + escapeHtml(c.title) + '</h2>' +
                '<div class="text-muted small mb-3">' + escapeHtml(c.merchantName) + '</div>' +
                '<div class="coupon-discount mb-2">' + escapeHtml(discount(c)) + '</div>' + code +
                '<div class="small text-muted mt-auto">Trust: ' + trust + ' · ' + c.favoriteCount + ' lượt lưu<br>Hết hạn: ' + escapeHtml(c.endsAtDisplay || '') + '</div>' +
                '<a class="btn btn-outline-primary btn-sm mt-3" href="' + escapeHtml(c.url) + '">Xem chi tiết</a>' +
                '</div></div></div>';
        }).join('');
        $results.html(html);
    }

    function buildQuery(page) {
        var data = $form.serializeArray().filter(function (field) { return field.name !== 'page'; });
        data.push({name: 'page', value: String(page)});
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
        $status.text('Đang tìm coupon...');
        var query = $form.serialize();
        if (lastRequest) lastRequest.abort();

        lastRequest = window.CouponHub.ajax({
            url: $form.data('api-url'),
            method: 'GET',
            data: query
        }).done(function (response) {
            if (!response || response.success !== true) {
                $status.text('Không thể tải danh sách coupon.');
                return;
            }
            render(response.data || []);
            var p = response.pagination || {page: 1, totalPages: 1, total: (response.data || []).length};
            $page.val(p.page);
            renderPagination(p);
            $status.text('Tìm thấy ' + p.total + ' coupon · Trang ' + p.page + '/' + p.totalPages);
            var browserUrl = $form.attr('action') + '?' + buildQuery(p.page);
            if (window.location.href !== new URL(browserUrl, window.location.href).href) {
                window.history.pushState({}, '', browserUrl);
            }
            if (Number(p.page) > 1) {
                document.getElementById('coupon-search-status').scrollIntoView({behavior: 'smooth', block: 'start'});
            }
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') return;
            var response = xhr.responseJSON || {};
            $status.text(response.message || 'Có lỗi khi tìm coupon. Bạn có thể tải lại trang để dùng tìm kiếm không AJAX.');
        });
    });

    // Restore the server URL/state when the user navigates back or forward.
    $(window).on('popstate', function () { window.location.reload(); });
})(jQuery);

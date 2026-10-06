(function ($) {
    'use strict';

    var $form = $('#coupon-search-form');
    if (!$form.length) return;

    var $results = $('#coupon-results');
    var $empty = $('#coupon-empty');
    var $status = $('#coupon-search-status');

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

    $form.on('submit', function (event) {
        event.preventDefault();
        $status.text('Đang tìm coupon...');
        var query = $form.serialize();

        window.CouponHub.ajax({
            url: $form.data('api-url'),
            method: 'GET',
            data: query
        }).done(function (response) {
            if (!response || response.success !== true) {
                $status.text('Không thể tải danh sách coupon.');
                return;
            }
            render(response.data || []);
            $status.text('Tìm thấy ' + (response.data || []).length + ' coupon trong trang hiện tại.');
            var browserUrl = $form.attr('action') + (query ? '?' + query : '');
            window.history.replaceState({}, '', browserUrl);
        }).fail(function (xhr) {
            var response = xhr.responseJSON || {};
            $status.text(response.message || 'Có lỗi khi tìm coupon. Bạn có thể tải lại trang để dùng tìm kiếm không AJAX.');
        });
    });
})(jQuery);

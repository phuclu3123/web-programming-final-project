(function ($) {
    'use strict';

    window.CouponHub = window.CouponHub || {};

    window.CouponHub.csrfToken = function () {
        return $('meta[name="csrf-token"]').attr('content') || '';
    };

    window.CouponHub.ajax = function (options) {
        var defaults = {
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        options = $.extend(true, {}, defaults, options || {});
        return $.ajax(options);
    };

    window.CouponHub.scheduleAlert = function ($alert) {
        if (!$alert || !$alert.length) return;
        var timeout = parseInt($alert.attr('data-timeout') || '12000', 10);
        if (!Number.isFinite(timeout) || timeout <= 0) return;

        window.setTimeout(function () {
            if (!$alert.closest('html').length) return;
            var element = $alert[0];
            if (window.bootstrap && element) window.bootstrap.Alert.getOrCreateInstance(element).close();
            else $alert.remove();
        }, timeout);
    };

    window.CouponHub.showMessage = function ($container, type, message, timeout) {
        type = ['success', 'danger', 'warning', 'info'].indexOf(type) !== -1 ? type : 'info';
        timeout = timeout || (type === 'danger' ? 20000 : (type === 'warning' ? 15000 : 12000));

        var $alert = $('<div>', {
            'class': 'alert alert-' + type + ' alert-dismissible fade show auto-dismiss-alert',
            'role': 'alert',
            'data-timeout': timeout
        });
        $alert.append(document.createTextNode(message));
        $alert.append(
            $('<button>', {
                type: 'button',
                'class': 'btn-close',
                'data-bs-dismiss': 'alert',
                'aria-label': 'Đóng'
            })
        );

        $container.empty().append($alert);
        window.CouponHub.scheduleAlert($alert);
    };

    $(function () {
        $('.auto-dismiss-alert').each(function () {
            window.CouponHub.scheduleAlert($(this));
        });

        $(document).on('click', '.password-toggle', function () {
            var $button = $(this);
            var selector = String($button.attr('data-target') || '');
            var $input = $(selector);
            if (!$input.length) return;

            var showing = $input.attr('type') === 'text';
            $input.attr('type', showing ? 'password' : 'text');
            $button.attr('aria-pressed', showing ? 'false' : 'true');
            $button.attr('aria-label', showing ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
            $button.attr('title', showing ? 'Hiện mật khẩu' : 'Ẩn mật khẩu');
            $button.toggleClass('is-visible', !showing);

            var $symbol = $button.find('.password-eye-symbol');
            if ($symbol.length) {
                $symbol.toggleClass('fa-eye', showing);
                $symbol.toggleClass('fa-eye-slash', !showing);
            }
        });

        var $backToTop = $('#back-to-top');
        var toggleBackToTop = function () {
            if (!$backToTop.length) return;
            if ($(window).scrollTop() > 320) {
                $backToTop.addClass('is-visible');
            } else {
                $backToTop.removeClass('is-visible');
            }
        };

        toggleBackToTop();
        $(window).on('scroll.backToTop', toggleBackToTop);

        $backToTop.on('click', function () {
            $('html, body').stop(true).animate({ scrollTop: 0 }, 350);
        });
    });
})(jQuery);

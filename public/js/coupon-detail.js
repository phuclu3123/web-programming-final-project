(function ($) {
    'use strict';

    $(function () {
    var $page = $('#coupon-detail-page');
    if (!$page.length) return;

    var token = window.CouponHub.csrfToken();
    var $message = $('#interaction-message');

    function request(url, data) {
        data = $.extend({}, data || {}, {_token: token});
        return window.CouponHub.ajax({
            url: url,
            method: 'POST',
            data: data
        });
    }

    function errorMessage(xhr, fallback) {
        var response = xhr.responseJSON || {};
        return response.message || fallback;
    }

    function updateStats(stats) {
        if (!stats) return;
        $('#trust-score').text(stats.trustScore === null ? '—' : Number(stats.trustScore) + '%');
        $('#total-votes').text(stats.totalVotes || 0);
        $('#working-count').text(stats.workingCount || 0);
        $('#not-working-count').text(stats.notWorkingCount || 0);
        $('#condition-changed-count').text(stats.conditionChangedCount || 0);
        $('#favorite-count').text(stats.favoriteCount || 0);
        $('#qualified-favorite-count').text(stats.qualifiedFavorites || 0);
    }

    $('#favorite-btn').on('click', function () {
        var $btn = $(this);
        var nextSaved = $btn.attr('data-saved') !== '1';
        $btn.prop('disabled', true);

        request($page.data('favorite-url'), {saved: nextSaved ? 1 : 0})
            .done(function (response) {
                var saved = !!response.data.saved;
                $btn.attr('data-saved', saved ? '1' : '0');
                $btn.toggleClass('btn-warning', saved).toggleClass('btn-outline-warning', !saved);
                $btn.text(saved ? '★ Đã lưu' : '☆ Lưu coupon');
                updateStats(response.data.stats);
                window.CouponHub.showMessage($message, 'success', response.message || 'Đã cập nhật.', 10000);
            })
            .fail(function (xhr) {
                window.CouponHub.showMessage($message, 'danger', errorMessage(xhr, 'Không thể cập nhật coupon đã lưu.'), 20000);
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });

    $('.vote-btn').on('click', function () {
        var $btn = $(this);
        var vote = $btn.data('vote');
        $('.vote-btn').prop('disabled', true);

        request($page.data('vote-url'), {vote: vote})
            .done(function (response) {
                $('.vote-btn').removeClass('active');
                $('.vote-btn[data-vote="' + response.data.vote + '"]').addClass('active');
                updateStats(response.data.stats);
                window.CouponHub.showMessage($message, 'success', response.message || 'Đã cập nhật xác nhận.', 10000);
            })
            .fail(function (xhr) {
                window.CouponHub.showMessage($message, 'danger', errorMessage(xhr, 'Không thể cập nhật xác nhận.'), 20000);
            })
            .always(function () {
                $('.vote-btn').prop('disabled', false);
            });
    });

    var $commentBody = $('#comment-body');
    $commentBody.on('input', function () {
        $('#comment-char-count').text(String($commentBody.val() || '').length);
    });

    $('#comment-form').on('submit', function (event) {
        event.preventDefault();
        var body = String($commentBody.val() || '').trim();
        if (!body) {
            window.CouponHub.showMessage($message, 'warning', 'Vui lòng nhập nội dung bình luận.', 12000);
            return;
        }

        var $form = $(this);
        var $submit = $form.find('button[type="submit"]');
        $submit.prop('disabled', true);

        request($page.data('comment-url') || $form.attr('action'), {body: body})
            .done(function (response) {
                var c = response.data;
                $('#comment-empty').remove();

                var $item = $('<div>', {'class': 'comment-item', 'data-comment-id': c.id});
                var $header = $('<div>', {'class': 'd-flex justify-content-between'});
                $header.append($('<strong>').text(c.fullName));
                $header.append($('<span>', {'class': 'small text-muted'}).text(c.createdAtDisplay));
                $item.append($header);
                $item.append($('<div>', {'class': 'comment-body mt-1'}).text(c.body));
                $('#comment-list').prepend($item);

                var current = parseInt($('#comment-count').text(), 10) || 0;
                $('#comment-count').text((current + 1) + ' bình luận');
                $commentBody.val('').trigger('input');
                window.CouponHub.showMessage($message, 'success', response.message || 'Đã gửi bình luận.', 10000);
            })
            .fail(function (xhr) {
                window.CouponHub.showMessage($message, 'danger', errorMessage(xhr, 'Không thể gửi bình luận.'), 20000);
            })
            .always(function () {
                $submit.prop('disabled', false);
            });
    });

    $('#report-form').on('submit', function (event) {
        event.preventDefault();
        var $form = $(this);
        var reason = $('#report-reason').val();
        var details = String($('#report-details').val() || '').trim();
        if (reason === 'OTHER' && !details) {
            window.CouponHub.showMessage($message, 'warning', 'Vui lòng mô tả lý do khi chọn “Khác”.', 15000);
            return;
        }

        var $submit = $form.find('button[type="submit"]');
        $submit.prop('disabled', true);
        request($page.data('report-url') || $form.attr('action'), {reason: reason, details: details})
            .done(function (response) {
                $('#reportCouponModal').modal('hide');
                $form[0].reset();
                window.CouponHub.showMessage($message, 'success', response.message || 'Đã gửi báo cáo.', 12000);
            })
            .fail(function (xhr) {
                $('#reportCouponModal').modal('hide');
                window.CouponHub.showMessage($message, 'danger', errorMessage(xhr, 'Không thể gửi báo cáo.'), 20000);
            })
            .always(function () {
                $submit.prop('disabled', false);
            });
    });

    $('#copy-coupon-code').on('click', function () {
        var code = String($(this).data('code') || '');
        if (!code) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(function () {
                window.CouponHub.showMessage($message, 'success', 'Đã sao chép mã ' + code + '.', 8000);
            }).catch(function () {
                window.CouponHub.showMessage($message, 'warning', 'Không thể tự sao chép. Hãy chọn mã và sao chép thủ công.', 12000);
            });
        } else {
            window.CouponHub.showMessage($message, 'info', 'Hãy chọn mã và sao chép thủ công.', 10000);
        }
    });
    });
})(jQuery);

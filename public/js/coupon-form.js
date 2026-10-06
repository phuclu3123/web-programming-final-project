(function ($) {
    'use strict';

    var $form = $('#coupon-create-form');
    if (!$form.length) return;

    var $type = $('#discount_type');
    var $valueGroup = $('#discount-value-group');
    var $value = $('#discount_value');
    var $valueLabel = $('#discount-value-label');
    var $cashbackGroup = $('#cashback-unit-group');
    var $cashback = $('#cashback_unit');
    var $start = $('#starts_at');
    var $end = $('#ends_at');
    var $startFeedback = $('#starts-at-feedback');
    var $endFeedback = $('#ends-at-feedback');

    function updateDiscountFields() {
        var type = $type.val();
        var needsValue = ['PERCENTAGE', 'FIXED_AMOUNT', 'CASHBACK'].indexOf(type) !== -1;
        var isCashback = type === 'CASHBACK';

        $valueGroup.toggle(needsValue);
        $value.prop('disabled', !needsValue);
        $cashbackGroup.toggle(isCashback);
        $cashback.prop('disabled', !isCashback);

        if (!needsValue) {
            $value.val('');
            $value.removeAttr('max');
            return;
        }

        if (type === 'PERCENTAGE') {
            $valueLabel.text('Phần trăm giảm (%)');
            $value.attr({max: '100', step: '0.01'});
        } else if (type === 'FIXED_AMOUNT') {
            $valueLabel.text('Số tiền giảm (VND)');
            $value.removeAttr('max').attr('step', '1000');
        } else {
            var unit = $cashback.val();
            if (unit === 'PERCENTAGE') {
                $valueLabel.text('Phần trăm hoàn tiền (%)');
                $value.attr({max: '100', step: '0.01'});
            } else {
                $valueLabel.text('Số tiền hoàn (VND)');
                $value.removeAttr('max').attr('step', '1000');
            }
        }
    }

    function parseLocal(value) {
        if (!value) return null;
        var date = new Date(value);
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function viDate(date) {
        return date.toLocaleString('vi-VN', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit', hour12: false
        });
    }

    function updateDateValidation() {
        var now = new Date();
        var start = parseLocal($start.val());
        var end = parseLocal($end.val());

        $start[0].setCustomValidity('');
        $end[0].setCustomValidity('');
        $startFeedback.removeClass('is-warning is-valid-note is-invalid-note').text('');
        $endFeedback.removeClass('is-warning is-valid-note is-invalid-note').text('');

        if (start) {
            if (start.getTime() < now.getTime()) {
                $startFeedback.addClass('is-warning').text('Đã chọn: ' + viDate(start) + '. Coupon đã bắt đầu; sau khi được duyệt sẽ hiển thị ngay nếu còn hạn.');
            } else {
                $startFeedback.addClass('is-valid-note').text('Đã chọn: ' + viDate(start) + '.');
            }
        }

        if (end) {
            if (end.getTime() <= now.getTime()) {
                $end[0].setCustomValidity('Coupon đã hết hạn. Vui lòng chọn thời gian kết thúc trong tương lai.');
                $endFeedback.addClass('is-invalid-note').text('Coupon đã hết hạn. Kết thúc phải ở tương lai.');
            } else if (start && end.getTime() <= start.getTime()) {
                $end[0].setCustomValidity('Thời gian kết thúc phải sau thời gian bắt đầu.');
                $endFeedback.addClass('is-invalid-note').text('Thời gian kết thúc phải sau thời gian bắt đầu.');
            } else {
                $endFeedback.addClass('is-valid-note').text('Đã chọn: ' + viDate(end) + '.');
            }
        }
    }

    $type.on('change', updateDiscountFields);
    $cashback.on('change', updateDiscountFields);
    $start.on('change input', updateDateValidation);
    $end.on('change input', updateDateValidation);

    $form.on('submit', function (event) {
        updateDiscountFields();
        updateDateValidation();
        if (!$form[0].checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            $form.addClass('was-validated');
        }
    });

    updateDiscountFields();
    updateDateValidation();
})(jQuery);

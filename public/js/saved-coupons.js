(function ($) {
    'use strict';
    $(function () {
        var $dialog = $('#savedRemoveModal');
        var pendingForm = null;

        $('.saved-remove-form').on('submit', function (event) {
            // Without JavaScript the form still POSTs with CSRF and works.
            if (this.dataset.confirmed === '1') return;
            event.preventDefault();
            pendingForm = this;
            $('#savedRemoveName').text($(this).attr('data-title') || 'coupon này');
            window.bootstrap.Modal.getOrCreateInstance($dialog[0]).show();
        });

        $('#savedConfirmRemove').on('click', function () {
            if (!pendingForm) return;
            $(this).prop('disabled', true);
            pendingForm.dataset.confirmed = '1';
            pendingForm.submit();
        });

        $dialog.on('hidden.bs.modal', function () {
            pendingForm = null;
            $('#savedConfirmRemove').prop('disabled', false);
        });
    });
})(jQuery);

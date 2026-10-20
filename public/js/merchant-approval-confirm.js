(function ($) {
    'use strict';

    $(function () {
        var $modal = $('#merchantDuplicateApprovalModal');
        var $checkbox = $('#duplicateApprovalAck');
        var $confirm = $('#duplicateApprovalConfirm');
        var pendingForm = null;
        var pendingSubmitter = null;

        // Intercept the form SUBMIT, including keyboard submissions, not only clicks.
        $(document).on('submit', '.merchant-review-form', function (event) {
            var form = this;
            var nativeEvent = event.originalEvent;
            var submitter = nativeEvent && nativeEvent.submitter ? nativeEvent.submitter : null;
            var action = submitter && submitter.name === 'action' ? submitter.value : '';

            if (action !== 'APPROVE' || form.getAttribute('data-has-duplicate') !== '1') {
                return; // Non-duplicate approvals and rejections retain the normal flow.
            }

            if (form.querySelector('[name="duplicate_confirmed"]').value === '1') {
                return; // Second, explicitly confirmed submission.
            }

            event.preventDefault();

            var note = form.querySelector('[name="note"]');
            var warning = form.querySelector('.merchant-note-warning');
            if (note && !note.value.trim()) {
                if (warning) warning.classList.remove('d-none');
                note.focus();
                return;
            }
            if (warning) warning.classList.add('d-none');

            pendingForm = form;
            pendingSubmitter = submitter;
            $checkbox.prop('checked', false);
            $confirm.prop('disabled', true);

            // Write attacker-controlled strings only via .text(), never via HTML.
            $('#duplicateApprovalRequest').text('#' + (form.dataset.requestId || ''));
            $('#duplicateApprovalUser').text(form.dataset.userName || '');
            $('#duplicateApprovalShop').text(form.dataset.merchantName || '');
            $('#duplicateApprovalEvidence').text(form.dataset.evidence || '');
            $('#duplicateApprovalCount').text('Đã có ' + (form.dataset.duplicateUsers || '1') + ' User khác nộp bằng chứng này trước đó cho cùng Shop.');
            window.bootstrap.Modal.getOrCreateInstance($modal[0]).show();
        });

        $checkbox.on('change', function () {
            $confirm.prop('disabled', !this.checked);
        });

        $confirm.on('click', function () {
            if (!pendingForm || !$checkbox.prop('checked') || !pendingSubmitter) return;
            var confirmedForm = pendingForm;
            var approvedBy = pendingSubmitter;
            confirmedForm.querySelector('[name="duplicate_confirmed"]').value = '1';
            $confirm.prop('disabled', true);
            pendingForm = null;
            pendingSubmitter = null;
            window.bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
            // requestSubmit preserves the approve button name/value and normal validation.
            confirmedForm.requestSubmit(approvedBy);
        });

        $modal.on('hidden.bs.modal', function () {
            if (pendingForm) {
                // Cancel/close always resets the confirmation. A successful POST navigates away.
                pendingForm.querySelector('[name="duplicate_confirmed"]').value = '0';
            }
            pendingForm = null;
            pendingSubmitter = null;
            $checkbox.prop('checked', false);
            $confirm.prop('disabled', true);
        });
    });
})(jQuery);

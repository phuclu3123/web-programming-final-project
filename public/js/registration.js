(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('registration-form');
        if (!form) return;

        var accountOptions = Array.prototype.slice.call(form.querySelectorAll('input[name="account_type"]'));
        var merchantBlock = document.getElementById('registration-merchant-fields');
        var merchantMode = document.getElementById('merchant_mode');
        var newBlock = document.getElementById('register-new-merchant');
        var claimBlock = document.getElementById('register-claim-merchant');
        var merchantName = document.getElementById('merchant_name');
        var merchantId = document.getElementById('merchant_id');
        var submitLabel = document.querySelector('#registration-submit span');

        function sync() {
            var selected = form.querySelector('input[name="account_type"]:checked');
            var isMerchant = !!selected && selected.value === 'merchant';
            var isNew = merchantMode.value === 'new';

            merchantBlock.classList.toggle('d-none', !isMerchant);
            newBlock.classList.toggle('d-none', !isNew);
            claimBlock.classList.toggle('d-none', isNew);

            merchantName.required = isMerchant && isNew;
            merchantId.required = isMerchant && !isNew;
            merchantMode.disabled = !isMerchant;
            if (submitLabel) submitLabel.textContent = isMerchant ? 'Đăng ký và gửi yêu cầu Merchant' : 'Đăng ký người dùng';

            accountOptions.forEach(function (option) {
                var card = option.closest('.account-type-card');
                if (card) card.classList.toggle('is-selected', option.checked);
            });
        }

        accountOptions.forEach(function (option) { option.addEventListener('change', sync); });
        merchantMode.addEventListener('change', sync);
        sync();
    });
})();

// Đổi nhóm trường theo loại yêu cầu shop, thay cho handler inline trong HTML.
'use strict';

document.addEventListener('DOMContentLoaded', function () {
    var mode = document.getElementById('merchant-apply-mode'),
        claim = document.getElementById('merchant-claim-block'),
        fresh = document.getElementById('merchant-new-block');
    if (!mode) return;

    function sync() {
        var isNew = mode.value === 'new';
        claim.classList.toggle('d-none', isNew);
        fresh.classList.toggle('d-none', !isNew);
    }
    mode.addEventListener('change', sync);
    sync();
});

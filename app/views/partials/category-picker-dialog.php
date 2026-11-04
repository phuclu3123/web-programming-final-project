<?php
/** Shared category picker dialog. Only $categoryPickerDialogId differs per page. */
?>
<dialog id="<?= e($categoryPickerDialogId) ?>" class="couponhub-category-dialog"
    aria-labelledby="<?= e($categoryPickerDialogId) ?>-title">
    <div class="couponhub-category-dialog-header">
        <div class="couponhub-category-dialog-heading">
            <span class="couponhub-category-dialog-eyebrow">KHÁM PHÁ DANH MỤC</span>
            <h2 id="<?= e($categoryPickerDialogId) ?>-title">Tất cả danh mục</h2>
            <p>Chọn một danh mục để xem các ưu đãi phù hợp.</p>
        </div>
        <button type="button" class="couponhub-category-close" data-category-close aria-label="Đóng danh mục">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <div class="couponhub-category-dialog-search">
        <label class="visually-hidden" for="<?= e($categoryPickerDialogId) ?>-search">Tìm danh mục</label>
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input id="<?= e($categoryPickerDialogId) ?>-search" type="search" autocomplete="off"
            maxlength="100" placeholder="Tìm danh mục..." data-category-dialog-search>
    </div>
    <p class="couponhub-category-dialog-count" data-category-dialog-status role="status" aria-live="polite"></p>
    <div class="couponhub-category-dialog-options" data-category-dialog-options role="group"
        aria-label="Danh sách danh mục"></div>
    <p class="couponhub-category-dialog-empty" data-category-dialog-empty hidden>
        Không tìm thấy danh mục phù hợp. Hãy thử từ khóa khác.
    </p>
    <div class="couponhub-category-dialog-footer">Chọn một danh mục để áp dụng bộ lọc.</div>
</dialog>

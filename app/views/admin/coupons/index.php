<?php
// Phân biệt duyệt, hết hạn, thu hồi và xóa; mỗi quyết định gửi tới action riêng.

use App\Core\Session; ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1">Quản lý coupon</h1>
        <p class="text-muted mb-0">Kiểm duyệt, sửa nội dung, buộc hết hạn hoặc xóa mềm coupon.</p>
    </div>
    <a class="btn btn-outline-secondary" href="<?= e(url('/admin')) ?>">Admin Dashboard</a>
</div>
<form method="get" action="<?= e(url('/admin/coupons')) ?>" class="row g-2 align-items-end mb-4">
    <div class="col-sm-6 col-md-4"><label class="form-label" for="coupon-status">Trạng thái hiệu lực</label>
        <select id="coupon-status" name="status" class="form-select">
            <option value="">Tất cả trạng thái</option>
            <?php foreach ($statuses as $entry): ?>
                <option value="<?= e($entry) ?>" <?= $status === $entry ? 'selected' : '' ?>><?= e($entry) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-primary">Lọc</button></div>
</form>
<?php if ($coupons === []): ?>
    <div class="alert alert-info">Không tìm thấy coupon ở trạng thái này.</div>
<?php else: ?>
 <div data-bulk-scope>
    <form method="post" action="<?= e(url('/admin/coupons/moderate-many')) ?>" class="card mb-4" data-bulk-form><div class="card-body">
        <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>"><input type="hidden" name="selection" value="[]">
        <div class="d-flex gap-3 flex-wrap align-items-center mb-3"><label class="form-check d-flex gap-2 align-items-center mb-0"><input class="form-check-input m-0" type="checkbox" data-bulk-all>Chọn tất cả coupon chờ duyệt trong trang</label><span class="small text-muted" role="status" aria-live="polite" data-bulk-count>Đã chọn 0 mục</span></div>
        <div class="row g-2 align-items-end">
            <div class="col-md-3"><label class="form-label" for="coupon-bulk-action">Thao tác</label><select class="form-select" name="action" id="coupon-bulk-action"><option value="APPROVE">Duyệt coupon</option><option value="REJECT">Từ chối coupon</option></select></div>
            <div class="col-md-6"><label class="form-label" for="coupon-bulk-note">Ghi chú chung (từ chối: tối thiểu 10 ký tự)</label><input class="form-control" id="coupon-bulk-note" name="note" maxlength="1000"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100" type="submit" data-bulk-submit disabled>Áp dụng cho mục đã chọn</button></div>
        </div>
        <div class="alert mt-3 mb-0" data-bulk-message role="alert" tabindex="-1" hidden></div>
    </div></form>
    <?php foreach ($coupons as $coupon): $id = (int)$coupon['id']; ?>
        <section class="card shadow-sm mb-3" aria-label="Coupon <?= $id ?>" data-bulk-row="<?= $id ?>">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <?php if($coupon['moderation_status']==='PENDING' && !in_array($coupon['publication_status'],['ARCHIVED','EXPIRED'],true)): ?><label class="form-check d-flex gap-2 align-items-center mb-2"><input class="form-check-input m-0" type="checkbox" data-bulk-item data-id="<?= $id ?>" data-fingerprint="<?= e(\App\Core\BulkSelection::couponFingerprint($coupon)) ?>">Chọn coupon #<?= $id ?></label><?php endif; ?>
                        <div class="small text-muted mb-1">#<?= $id ?> · <?= e((string)$coupon['source']) ?> ·
                            <?= e((string)$coupon['merchant_name']) ?></div>
                        <h2 class="h5 mb-1"><?= e((string)$coupon['title']) ?></h2>
                        <span class="small text-muted"><?= e((string)$coupon['category_name']) ?> ·
                            <?= e(format_db_datetime((string)$coupon['ends_at'])) ?></span>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge bg-secondary"><?= e((string)$coupon['admin_status']) ?></span>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/coupons/' . $id . '/edit')) ?>">Sửa
                            nội
                            dung</a>
                    </div>
                </div>
                <div class="small mb-2" data-bulk-result role="status"></div>
                <?php if ((string)$coupon['moderation_status'] === 'PENDING'): ?>
                    <form method="post" action="<?= e(url('/admin/coupons/' . $id . '/moderate')) ?>" class="border-top pt-3 mb-3">
                        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                        <label for="moderate-note-<?= $id ?>" class="form-label">Ghi chú kiểm duyệt (bắt buộc khi từ chối)</label>
                        <textarea id="moderate-note-<?= $id ?>" name="note" rows="2" maxlength="1000"
                            class="form-control mb-2"></textarea>
                        <button name="action" value="APPROVE" class="btn btn-sm btn-success" type="submit">Duyệt</button>
                        <button name="action" value="REJECT" class="btn btn-sm btn-outline-danger" type="submit">Từ chối</button>
                    </form>
                <?php endif; ?>
                <?php if ($coupon['moderation_status'] === 'APPROVED' && $coupon['publication_status'] !== 'ARCHIVED'): ?>
                    <form method="post" action="<?= e(url('/admin/coupons/' . $id . '/revoke')) ?>" class="border-top pt-3 mb-3">
                        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                        <label class="form-label" for="revoke-reason-<?= $id ?>">Lý do thu hồi (10–500 ký tự)</label>
                        <div class="d-flex flex-wrap gap-2">
                            <input id="revoke-reason-<?= $id ?>" name="reason" class="form-control" minlength="10" maxlength="500"
                                required>
                            <button type="submit" class="btn btn-danger">Thu hồi coupon</button>
                        </div>
                        <small class="text-muted">Ngừng phát hành ngay, giữ lịch sử và ngày hiệu lực; Merchant không thể bật
                            lại.</small>
                    </form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/admin/coupons/' . $id . '/expire')) ?>" class="border-top pt-3 mb-2"
                    data-confirm-submit="Buộc hết hạn coupon #<?= $id ?>?">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <label class="form-label" for="expire-reason-<?= $id ?>">Lý do buộc hết hạn (10–500 ký tự)</label>
                    <div class="d-flex flex-wrap gap-2">
                        <input id="expire-reason-<?= $id ?>" name="reason" class="form-control flex-grow-1 csp-max-w-640"
                            minlength="10" maxlength="500" required placeholder="Ví dụ: Merchant thông báo ngừng ưu đãi">
                        <button type="submit" class="btn btn-outline-warning"
                            <?= (string)$coupon['moderation_status'] !== 'APPROVED' || (string)$coupon['publication_status'] !== 'ENABLED' ? 'disabled' : '' ?>>Buộc
                            hết hạn</button>
                    </div>
                </form>
                <form method="post" action="<?= e(url('/admin/coupons/' . $id . '/delete')) ?>" class="pt-2"
                    data-confirm-submit="Xóa mềm coupon #<?= $id ?>? Thao tác này không hiển thị coupon nữa.">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <label class="form-label" for="delete-reason-<?= $id ?>">Lý do xóa (10–500 ký tự)</label>
                    <div class="d-flex flex-wrap gap-2">
                        <input id="delete-reason-<?= $id ?>" name="reason" class="form-control flex-grow-1 csp-max-w-640"
                            minlength="10" maxlength="500" required placeholder="Ví dụ: Coupon giả hoặc vi phạm quy định">
                        <button type="submit" class="btn btn-outline-danger">Xóa mềm</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endforeach; ?>
 </div>
<?php endif; ?>
<nav class="d-flex align-items-center gap-3 my-4" aria-label="Phân trang coupon">
    <span class="text-muted"><?= (int)$total ?> coupon · Trang <?= (int)$page ?>/<?= (int)$pages ?></span>
    <?php if ($page > 1): ?><a class="btn btn-outline-secondary"
            href="<?= e(url('/admin/coupons?' . http_build_query(['status' => $status, 'page' => $page - 1]))) ?>">Trang
            trước</a><?php endif; ?>
    <?php if ($page < $pages): ?><a class="btn btn-outline-primary"
            href="<?= e(url('/admin/coupons?' . http_build_query(['status' => $status, 'page' => $page + 1]))) ?>">Trang
            sau</a><?php endif; ?>
</nav>
<script src="<?= e(url('/assets/js/csp/page-actions.js')) ?>" defer></script>
<script src="<?= e(url('/assets/js/admin-bulk.js')) ?>" defer></script>

<?php

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
    <?php foreach ($coupons as $coupon): $id = (int)$coupon['id']; ?>
        <section class="card shadow-sm mb-3" aria-label="Coupon <?= $id ?>">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
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
                <form method="post" action="<?= e(url('/admin/coupons/' . $id . '/expire')) ?>" class="border-top pt-3 mb-2"
                    onsubmit="return confirm('Buộc hết hạn coupon #<?= $id ?>?');">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <label class="form-label" for="expire-reason-<?= $id ?>">Lý do buộc hết hạn (10–500 ký tự)</label>
                    <div class="d-flex flex-wrap gap-2">
                        <input id="expire-reason-<?= $id ?>" name="reason" class="form-control flex-grow-1"
                            style="max-width:640px" minlength="10" maxlength="500" required
                            placeholder="Ví dụ: Merchant thông báo ngừng ưu đãi">
                        <button type="submit" class="btn btn-outline-warning"
                            <?= (string)$coupon['moderation_status'] !== 'APPROVED' || (string)$coupon['publication_status'] !== 'ENABLED' ? 'disabled' : '' ?>>Buộc
                            hết hạn</button>
                    </div>
                </form>
                <form method="post" action="<?= e(url('/admin/coupons/' . $id . '/delete')) ?>" class="pt-2"
                    onsubmit="return confirm('Xóa mềm coupon #<?= $id ?>? Thao tác này không hiển thị coupon nữa.');">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <label class="form-label" for="delete-reason-<?= $id ?>">Lý do xóa (10–500 ký tự)</label>
                    <div class="d-flex flex-wrap gap-2">
                        <input id="delete-reason-<?= $id ?>" name="reason" class="form-control flex-grow-1"
                            style="max-width:640px" minlength="10" maxlength="500" required
                            placeholder="Ví dụ: Coupon giả hoặc vi phạm quy định">
                        <button type="submit" class="btn btn-outline-danger">Xóa mềm</button>
                    </div>
                </form>
            </div>
        </section>
    <?php endforeach; ?>
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
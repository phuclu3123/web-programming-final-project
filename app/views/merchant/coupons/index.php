<?php

use App\Core\Session; ?>
<div class="merchant-coupon-page">
<div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Coupon của cửa hàng</h1>
        <p class="text-muted mb-0">Theo dõi trạng thái duyệt và phản hồi từ quản trị viên.</p>
    </div><?php if ($shops !== []): ?><a class="btn btn-primary" href="<?= e(url('/merchant/coupons/create')) ?>"><i
            class="fa-solid fa-plus me-1"></i>Tạo coupon</a><?php endif; ?>
</div>
<?php if ($shops === []): ?><div class="alert alert-info">Bạn chưa có cửa hàng đang được phép đăng mã. Xem <a href="<?= e(url('/merchant/apply')) ?>">quyền quản lý cửa hàng</a> hoặc liên hệ quản trị viên.</div><?php endif; ?>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="thead-light">
                <tr>
                    <th>Coupon</th>
                    <th>Merchant</th>
                    <th>Thời hạn</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($coupons === []): ?><tr>
                        <td colspan="5" class="text-center text-muted py-4">Chưa có coupon. Khi cửa hàng được duyệt, bạn có thể tạo ưu đãi đầu tiên tại đây.</td>
                    </tr><?php endif; ?>
                <?php foreach ($coupons as $c):
                    $revoked = $c['publication_status'] === 'ARCHIVED';
                    $canEdit = !$revoked && $c['publication_status'] !== 'EXPIRED' && $c['deleted_at'] === null && $c['merchant_status'] === 'APPROVED';
                    ?><tr data-merchant-coupon="<?= (int)$c['id'] ?>">
                        <td><strong><?= e((string)$c['title']) ?></strong>
                            <div class="small text-muted"><?= e((string)($c['code'] ?? 'Không có mã')) ?></div>
                        </td>
                        <td data-label="Cửa hàng"><?= e((string)$c['merchant_name']) ?></td>
                        <td data-label="Thời hạn"><?= e(format_db_datetime((string)$c['starts_at'])) ?><br><span class="text-muted">→
                                <?= e(format_db_datetime((string)$c['ends_at'])) ?></span></td>
                        <td data-label="Trạng thái"><span
                                class="badge bg-<?= e(coupon_status_class((string)$c['effective_status'])) ?>"><?= e($revoked ? 'Bị thu hồi' : coupon_status_label((string)$c['effective_status'])) ?></span>
                            <?php if ($c['merchant_status'] === 'LOCKED'): ?><div class="small text-muted mt-2">Cửa hàng đang bị khóa.</div><?php endif; ?>
                            <?php if ($revoked || $c['moderation_status'] === 'REJECTED'): ?><div class="merchant-review-note small mt-2"><strong><?= $revoked ? 'Lý do thu hồi:' : 'Phản hồi kiểm duyệt:' ?></strong>
                                <?= e((string)($c['review_note'] ?: 'Chưa có ghi chú. Vui lòng liên hệ quản trị viên.')) ?>
                                <?php if ($c['reviewed_at']): ?><div class="text-muted mt-1"><?= e(format_db_datetime((string)$c['reviewed_at'])) ?></div><?php endif; ?>
                            </div><?php endif; ?>
                        </td>
                        <td class="text-end merchant-coupon-actions"><?php if ($canEdit): ?><a class="btn btn-sm btn-outline-primary"
                                href="<?= e(url('/merchant/coupons/' . (int)$c['id'] . '/edit')) ?>">Sửa</a><?php if ((string)$c['publication_status'] !== 'DISABLED'): ?>
                                <form class="d-inline" method="post" data-confirm-submit="Ngừng coupon này?"
                                    action="<?= e(url('/merchant/coupons/' . (int)$c['id'] . '/disable')) ?>"><input
                                        type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><button
                                        class="btn btn-sm btn-outline-danger">Ngừng</button></form><?php endif; ?>
                            <?php else: ?><span class="small text-muted">Không thể chỉnh sửa<?= $revoked ? ' mã đã thu hồi' : ' ở trạng thái này' ?>.</span><?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<script src="<?= e(url('/assets/js/csp/page-actions.js')) ?>" defer></script>

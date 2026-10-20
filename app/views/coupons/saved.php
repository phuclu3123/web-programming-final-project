<?php

/** @var array<string,mixed> $saved */
/** @var array<string,mixed> $filters */
$items = $saved['items'] ?? [];
$page = (int) ($saved['page'] ?? 1);
$pages = (int) ($saved['pages'] ?? 1);
$total = (int) ($saved['total'] ?? 0);
$makePageLink = static function (int $next) use ($filters): string {
    $query = [
        'q' => (string) $filters['keyword'],
        'category_id' => (int) $filters['category_id'] ?: null,
        'status' => (string) $filters['status'],
        'sort' => (string) $filters['sort'],
        'page' => $next,
    ];
    return url('/saved-coupons') . '?' . http_build_query(array_filter($query, static fn($value) => $value !== null && $value !== ''));
};
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
    <div>
        <h1 class="h3 mb-1"><i class="fa-regular fa-bookmark text-primary me-2" aria-hidden="true"></i>Coupon đã lưu
        </h1>
        <p class="text-muted mb-0">Lưu coupon để dễ tìm lại. Bạn đang lưu <strong><?= (int) $totalSaved ?></strong>
            coupon.</p>
    </div>
    <a class="btn btn-primary mt-3 mt-md-0" href="<?= e(url('/coupons')) ?>"><i
            class="fa-solid fa-magnifying-glass me-1" aria-hidden="true"></i>Tìm thêm coupon</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= e(url('/saved-coupons')) ?>" aria-label="Bộ lọc coupon đã lưu">
            <div class="row g-3">
                <div class="mb-3 col-md-6 col-lg-4">
                    <label for="saved-keyword">Từ khóa</label>
                    <input class="form-control" id="saved-keyword" type="search" name="q" maxlength="100"
                        value="<?= e((string) $filters['keyword']) ?>" placeholder="Tên coupon, mã hoặc Merchant...">
                </div>
                <div class="mb-3 col-md-6 col-lg-3">
                    <label for="saved-category">Danh mục</label>
                    <select class="form-control" id="saved-category" name="category_id">
                        <option value="">Tất cả</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= (int) $category['id'] ?>"
                                <?= (int) $filters['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                                <?= e((string) $category['name']) ?> (<?= (int) $category['saved_count'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3 col-md-6 col-lg-3">
                    <label for="saved-status">Trạng thái</label>
                    <select class="form-control" id="saved-status" name="status">
                        <?php foreach (['ALL' => 'Tất cả', 'ACTIVE' => 'Đang hoạt động', 'SCHEDULED' => 'Sắp diễn ra', 'UNAVAILABLE' => 'Hết hạn / Không khả dụng'] as $status => $label): ?>
                            <option value="<?= e($status) ?>"
                                <?= (string) $filters['status'] === $status ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3 col-md-6 col-lg-2">
                    <label for="saved-sort">Sắp xếp</label>
                    <select class="form-control" id="saved-sort" name="sort">
                        <option value="RECENT" <?= $filters['sort'] === 'RECENT' ? 'selected' : '' ?>>Mới lưu</option>
                        <option value="EXPIRING" <?= $filters['sort'] === 'EXPIRING' ? 'selected' : '' ?>>Sắp hết hạn
                        </option>
                    </select>
                </div>
            </div>
            <div class="d-flex justify-content-end flex-wrap" style="gap:.5rem">
                <a class="btn btn-outline-secondary" href="<?= e(url('/saved-coupons')) ?>">Xóa lọc</a>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter me-1"
                        aria-hidden="true"></i>Áp dụng</button>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted small mb-0">Tìm thấy <strong><?= $total ?></strong>
        coupon<?= $pages > 1 ? ' · Trang ' . $page . '/' . $pages : '' ?></p>
</div>

<?php if ($items === []): ?>
    <div class="card shadow-sm text-center">
        <div class="card-body py-5">
            <i class="fa-regular fa-bookmark fa-3x text-muted mb-3" aria-hidden="true"></i>
            <h2 class="h5"><?= (int) $totalSaved === 0 ? 'Bạn chưa lưu coupon nào' : 'Không có coupon phù hợp với bộ lọc' ?>
            </h2>
            <p class="text-muted">
                <?= (int) $totalSaved === 0 ? 'Bạn có thể lưu các ưu đãi quan tâm để xem lại sau.' : 'Hãy đổi bộ lọc hoặc xóa lọc để xem các coupon khác.' ?>
            </p>
            <a class="btn btn-outline-primary"
                href="<?= e((int) $totalSaved === 0 ? url('/coupons') : url('/saved-coupons')) ?>"><?= (int) $totalSaved === 0 ? 'Khám phá coupon' : 'Xem tất cả coupon đã lưu' ?></a>
        </div>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($items as $item): ?>
            <?php
            $currentStatus = (string) $item['effective_status'];
            $isAvailable = $currentStatus === 'ACTIVE';
            $isQualified = (int) $item['qualified_favorite'] === 1;
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <article class="card shadow-sm h-100 coupon-card saved-coupon-card">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-start mb-2" style="gap:.5rem">
                            <span
                                class="badge bg-<?= e(coupon_status_class($currentStatus)) ?> saved-status-badge"><?= e(coupon_status_label($currentStatus)) ?></span>
                            <span class="small text-muted text-end">#<?= (int) $item['id'] ?></span>
                        </div>
                        <div class="mb-2">
                            <span
                                class="badge-soft <?= e(category_badge_class((string) $item['category_name'])) ?>"><?= e((string) $item['category_name']) ?></span>
                            <span
                                class="badge-soft <?= e(usage_badge_class((string) $item['usage_channel'])) ?>"><?= e(usage_channel_label((string) $item['usage_channel'])) ?></span>
                        </div>
                        <h2 class="h5 mb-1 saved-coupon-title"><?= e((string) $item['title']) ?></h2>
                        <div class="text-muted small mb-3"><?= e((string) $item['merchant_name']) ?></div>
                        <div class="coupon-discount mb-2"><?= e(format_discount($item)) ?></div>
                        <?php if ($isAvailable && !empty($item['code'])): ?>
                            <div class="coupon-code mb-2"><code><?= e((string) $item['code']) ?></code></div>
                        <?php endif; ?>
                        <div class="small text-muted mt-auto">
                            <div><i class="fa-regular fa-calendar me-1" aria-hidden="true"></i>Hết hạn:
                                <?= e(format_db_datetime((string) $item['ends_at'])) ?></div>
                            <div><i class="fa-regular fa-bookmark me-1" aria-hidden="true"></i>Đã lưu:
                                <?= e(format_db_datetime((string) $item['saved_at'])) ?></div>
                            <?php if (!$isQualified): ?>
                                <div class="saved-eligibility-note mt-2"><i class="fa-solid fa-circle-info me-1"
                                        aria-hidden="true"></i>Lượt lưu này không tính vào xếp hạng hoặc CouponPoint.</div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex mt-3 saved-card-actions" style="gap:.5rem">
                            <?php if ($isAvailable): ?>
                                <a class="btn btn-outline-primary btn-sm flex-fill"
                                    href="<?= e(url('/coupons/' . (int) $item['id'])) ?>">Xem chi tiết</a>
                            <?php else: ?>
                                <span class="btn btn-light btn-sm flex-fill disabled" aria-disabled="true"
                                    title="Coupon không còn hiển thị công khai">Không khả dụng</span>
                            <?php endif; ?>
                            <form method="post" action="<?= e(url('/saved-coupons/' . (int) $item['id'] . '/remove')) ?>"
                                class="mb-0 saved-remove-form" data-title="<?= e((string) $item['title']) ?>">
                                <input type="hidden" name="_token" value="<?= e(\App\Core\Session::csrfToken()) ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Bỏ lưu coupon"
                                    aria-label="Bỏ lưu <?= e((string) $item['title']) ?>"><i
                                        class="fa-regular fa-trash-can me-1" aria-hidden="true"></i>Bỏ lưu</button>
                            </form>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
        <nav aria-label="Phân trang coupon đã lưu" class="mt-2">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link"
                        href="<?= e($makePageLink(max(1, $page - 1))) ?>" aria-label="Trang trước">Trước</a></li>
                <?php for ($p = max(1, $page - 2); $p <= min($pages, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link"
                            href="<?= e($makePageLink($p)) ?>" <?= $p === $page ? 'aria-current="page"' : '' ?>><?= $p ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link"
                        href="<?= e($makePageLink(min($pages, $page + 1))) ?>" aria-label="Trang sau">Sau</a></li>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>

<div class="modal fade" id="savedRemoveModal" tabindex="-1" role="dialog" aria-labelledby="savedRemoveTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="savedRemoveTitle">Xác nhận bỏ lưu coupon</h2><button type="button"
                    class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">Bạn muốn bỏ lưu <strong id="savedRemoveName"></strong>? Hành động này không xóa
                coupon khỏi hệ thống.</div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Quay
                    lại</button><button type="button" class="btn btn-danger" id="savedConfirmRemove">Xác nhận bỏ
                    lưu</button></div>
        </div>
    </div>
</div>
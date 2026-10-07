<?php use App\Core\Auth; ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-4">
    <div>
        <h1 class="h3 mb-1">Tìm coupon</h1>
        <p class="text-muted mb-0">Tìm kiếm và lọc các coupon đang hoạt động.</p>
    </div>
    <?php if (in_array(Auth::role(), ['USER', 'MERCHANT'], true)): ?>
        <a class="btn btn-primary mt-3 mt-lg-0" href="<?= e(url('/coupons/create')) ?>">+ Chia sẻ coupon</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form id="coupon-search-form" method="get" action="<?= e(url('/coupons')) ?>" data-api-url="<?= e(url('/api/coupons/search')) ?>">
            <div class="form-row">
                <div class="form-group col-lg-4">
                    <label for="keyword">Từ khóa</label>
                    <input class="form-control" id="keyword" name="keyword" value="<?= e((string) ($filters['keyword'] ?? '')) ?>" placeholder="Tên coupon, mã, Merchant...">
                </div>
                <div class="form-group col-md-6 col-lg-2">
                    <label for="category_id">Danh mục</label>
                    <select class="form-control" id="category_id" name="category_id">
                        <option value="">Tất cả</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category->id ?>" <?= (int) ($filters['category_id'] ?? 0) === $category->id ? 'selected' : '' ?>><?= e($category->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6 col-lg-2">
                    <label for="merchant_id">Merchant</label>
                    <select class="form-control" id="merchant_id" name="merchant_id">
                        <option value="">Tất cả</option>
                        <?php foreach ($merchants as $merchant): ?>
                            <option value="<?= $merchant->id ?>" <?= (int) ($filters['merchant_id'] ?? 0) === $merchant->id ? 'selected' : '' ?>><?= e($merchant->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6 col-lg-2">
                    <label for="discount_type">Loại giảm</label>
                    <select class="form-control" id="discount_type" name="discount_type">
                        <option value="">Tất cả</option>
                        <?php foreach (['PERCENTAGE'=>'Phần trăm','FIXED_AMOUNT'=>'Số tiền','FREE_SHIPPING'=>'Free ship','BUY_X_GET_Y'=>'Mua X tặng Y','GIFT'=>'Quà tặng','CASHBACK'=>'Hoàn tiền'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($filters['discount_type'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-md-6 col-lg-2">
                    <label for="usage_channel">Kênh dùng</label>
                    <select class="form-control" id="usage_channel" name="usage_channel">
                        <option value="">Tất cả</option>
                        <?php foreach (['ONLINE'=>'Online','OFFLINE'=>'Offline','BOTH'=>'Online + Offline'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= ($filters['usage_channel'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row align-items-end">
                <div class="form-group col-md-4">
                    <label for="sort">Sắp xếp</label>
                    <select class="form-control" id="sort" name="sort">
                        <option value="NEWEST" <?= $filters['sort'] === 'NEWEST' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="POPULAR" <?= $filters['sort'] === 'POPULAR' ? 'selected' : '' ?>>Phổ biến</option>
                        <option value="ENDING_SOON" <?= $filters['sort'] === 'ENDING_SOON' ? 'selected' : '' ?>>Sắp hết hạn</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="ending_soon" name="ending_soon" value="1" <?= $filters['ending_soon'] ? 'checked' : '' ?>>
                        <label class="custom-control-label" for="ending_soon">Chỉ coupon hết hạn trong 3 ngày</label>
                    </div>
                </div>
                <div class="form-group col-md-4 text-md-right">
                    <a class="btn btn-outline-secondary" href="<?= e(url('/coupons')) ?>">Xóa lọc</a>
                    <button class="btn btn-primary" type="submit">Tìm kiếm</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="coupon-search-status" class="small text-muted mb-3" aria-live="polite"></div>
<div id="coupon-results" class="row">
    <?php foreach ($coupons as $coupon): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card coupon-card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <div class="mb-2 d-flex flex-wrap align-items-center" style="gap:.35rem">
                        <?php if ((int) $coupon['is_official'] === 1): ?><span class="badge badge-primary">Official Deal</span><?php endif; ?>
                        <span class="badge-soft <?= e(category_badge_class((string) ($coupon['category_name'] ?? ''))) ?>"><?= e((string) ($coupon['category_name'] ?? 'Khác')) ?></span>
                        <span class="badge-soft <?= e(usage_badge_class((string) $coupon['usage_channel'])) ?>"><?= e(usage_channel_label((string) $coupon['usage_channel'])) ?></span>
                    </div>
                    <h2 class="h5 mb-1"><?= e((string) $coupon['title']) ?></h2>
                    <div class="text-muted small mb-3"><?= e((string) $coupon['merchant_name']) ?></div>
                    <div class="coupon-discount mb-2"><?= e(format_discount($coupon)) ?></div>
                    <?php if (!empty($coupon['code'])): ?><div class="coupon-code mb-3"><code><?= e((string) $coupon['code']) ?></code></div><?php endif; ?>
                    <div class="small text-muted mt-auto">
                        Trust: <?= $coupon['trust_score'] === null ? 'Chưa đủ dữ liệu' : e((string) $coupon['trust_score']) . '%' ?> · <?= (int) $coupon['favorite_count'] ?> lượt lưu<br>
                        Hết hạn: <?= e(format_db_datetime((string) $coupon['ends_at'])) ?>
                    </div>
                    <a class="btn btn-outline-primary btn-sm mt-3" href="<?= e(url('/coupons/' . (int) $coupon['id'])) ?>">Xem chi tiết</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div id="coupon-empty" class="text-center text-muted py-5 <?= $coupons !== [] ? 'd-none' : '' ?>">
    Chưa có coupon phù hợp với bộ lọc hiện tại.
</div>



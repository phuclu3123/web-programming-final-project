<?php

use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1">Chia sẻ coupon</h1>
                <p class="text-muted mb-0">Coupon cộng đồng sẽ ở trạng thái PENDING cho đến khi Admin duyệt.</p>
            </div>
            <a href="<?= e(url('/my-coupons')) ?>" class="btn btn-outline-secondary">Coupon của tôi</a>
        </div>

        <?php if (!empty($errors['auth'])): ?>
            <div class="alert alert-danger auto-dismiss-alert" data-timeout="20000"><?= e($errors['auth']) ?></div>
        <?php endif; ?>
        <?php if ($merchants === []): ?>
            <div class="alert alert-warning">
                Chưa có Merchant nào để chọn. Trong môi trường phát triển, hãy chạy
                <code>php scripts/seed_demo_merchants.php</code> một lần.
            </div>
        <?php endif; ?>

        <form id="coupon-create-form" method="post" action="<?= e(url('/coupons')) ?>" class="card shadow-sm">
            <div class="card-body p-4">
                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="merchant_id">Merchant <span class="text-danger">*</span></label>
                        <select class="form-control <?= isset($errors['merchant_id']) ? 'is-invalid' : '' ?>"
                            id="merchant_id" name="merchant_id" required>
                            <option value="">-- Chọn Merchant --</option>
                            <?php foreach ($merchants as $merchant): ?>
                                <option value="<?= $merchant->id ?>"
                                    <?= (int) ($old['merchant_id'] ?? 0) === $merchant->id ? 'selected' : '' ?>>
                                    <?= e($merchant->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['merchant_id'])): ?><div class="invalid-feedback">
                                <?= e($errors['merchant_id']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="category_id">Danh mục <span class="text-danger">*</span></label>
                        <select class="form-control <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>"
                            id="category_id" name="category_id" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category->id ?>"
                                    <?= (int) ($old['category_id'] ?? 0) === $category->id ? 'selected' : '' ?>>
                                    <?= e($category->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback">
                                <?= e($errors['category_id']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="title">Tên coupon <span class="text-danger">*</span></label>
                    <input class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>" id="title"
                        name="title" maxlength="200" value="<?= e((string) ($old['title'] ?? '')) ?>" required>
                    <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= e($errors['title']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="code">Mã coupon</label>
                        <input class="form-control <?= isset($errors['code']) ? 'is-invalid' : '' ?>" id="code"
                            name="code" maxlength="100" value="<?= e((string) ($old['code'] ?? '')) ?>"
                            placeholder="VD: SALE50">
                        <?php if (isset($errors['code'])): ?><div class="invalid-feedback"><?= e($errors['code']) ?>
                            </div><?php endif; ?>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="promotion_url">Promotion URL</label>
                        <input type="url"
                            class="form-control <?= isset($errors['promotion_url']) ? 'is-invalid' : '' ?>"
                            id="promotion_url" name="promotion_url"
                            value="<?= e((string) ($old['promotion_url'] ?? '')) ?>" placeholder="https://...">
                        <?php if (isset($errors['promotion_url'])): ?><div class="invalid-feedback">
                                <?= e($errors['promotion_url']) ?></div><?php endif; ?>
                        <small class="form-text text-muted">Cần ít nhất mã coupon hoặc URL.</small>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Mô tả</label>
                    <textarea class="form-control" id="description" name="description"
                        rows="3"><?= e((string) ($old['description'] ?? '')) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="terms">Điều kiện sử dụng <span class="text-danger">*</span></label>
                    <textarea class="form-control <?= isset($errors['terms']) ? 'is-invalid' : '' ?>" id="terms"
                        name="terms" rows="4" required><?= e((string) ($old['terms'] ?? '')) ?></textarea>
                    <?php if (isset($errors['terms'])): ?><div class="invalid-feedback"><?= e($errors['terms']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="discount_type">Loại giảm <span class="text-danger">*</span></label>
                        <select class="form-control <?= isset($errors['discount_type']) ? 'is-invalid' : '' ?>"
                            id="discount_type" name="discount_type" required>
                            <?php foreach (['PERCENTAGE' => 'Phần trăm', 'FIXED_AMOUNT' => 'Số tiền', 'FREE_SHIPPING' => 'Free shipping', 'BUY_X_GET_Y' => 'Mua X tặng Y', 'GIFT' => 'Quà tặng', 'CASHBACK' => 'Hoàn tiền'] as $value => $label): ?>
                                <option value="<?= e($value) ?>"
                                    <?= ($old['discount_type'] ?? 'PERCENTAGE') === $value ? 'selected' : '' ?>>
                                    <?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['discount_type'])): ?><div class="invalid-feedback">
                                <?= e($errors['discount_type']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group col-md-4" id="discount-value-group">
                        <label for="discount_value" id="discount-value-label">Giá trị giảm</label>
                        <input type="number" step="0.01" min="0"
                            class="form-control <?= isset($errors['discount_value']) ? 'is-invalid' : '' ?>"
                            id="discount_value" name="discount_value"
                            value="<?= e((string) ($old['discount_value'] ?? '')) ?>">
                        <?php if (isset($errors['discount_value'])): ?><div class="invalid-feedback">
                                <?= e($errors['discount_value']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-group col-md-4" id="cashback-unit-group">
                        <label for="cashback_unit">Đơn vị hoàn tiền</label>
                        <select class="form-control <?= isset($errors['cashback_unit']) ? 'is-invalid' : '' ?>"
                            id="cashback_unit" name="cashback_unit">
                            <option value="PERCENTAGE"
                                <?= ($old['cashback_unit'] ?? 'PERCENTAGE') === 'PERCENTAGE' ? 'selected' : '' ?>>Phần
                                trăm</option>
                            <option value="FIXED_AMOUNT"
                                <?= ($old['cashback_unit'] ?? '') === 'FIXED_AMOUNT' ? 'selected' : '' ?>>Số tiền
                            </option>
                        </select>
                        <?php if (isset($errors['cashback_unit'])): ?><div class="invalid-feedback">
                                <?= e($errors['cashback_unit']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="min_order_amount">Đơn tối thiểu (VND)</label>
                        <input type="number" min="0" step="1000"
                            class="form-control <?= isset($errors['min_order_amount']) ? 'is-invalid' : '' ?>"
                            id="min_order_amount" name="min_order_amount"
                            value="<?= e((string) ($old['min_order_amount'] ?? '0')) ?>">
                        <?php if (isset($errors['min_order_amount'])): ?><div class="invalid-feedback">
                                <?= e($errors['min_order_amount']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="max_discount_amount">Giảm tối đa (VND)</label>
                        <input type="number" min="0" step="1000"
                            class="form-control <?= isset($errors['max_discount_amount']) ? 'is-invalid' : '' ?>"
                            id="max_discount_amount" name="max_discount_amount"
                            value="<?= e((string) ($old['max_discount_amount'] ?? '')) ?>">
                        <?php if (isset($errors['max_discount_amount'])): ?><div class="invalid-feedback">
                                <?= e($errors['max_discount_amount']) ?></div><?php endif; ?>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="usage_channel">Hình thức sử dụng <span class="text-danger">*</span></label>
                        <select class="form-control <?= isset($errors['usage_channel']) ? 'is-invalid' : '' ?>"
                            id="usage_channel" name="usage_channel" required>
                            <option value="ONLINE"
                                <?= ($old['usage_channel'] ?? 'ONLINE') === 'ONLINE' ? 'selected' : '' ?>>Online
                            </option>
                            <option value="OFFLINE"
                                <?= ($old['usage_channel'] ?? '') === 'OFFLINE' ? 'selected' : '' ?>>Offline</option>
                            <option value="BOTH" <?= ($old['usage_channel'] ?? '') === 'BOTH' ? 'selected' : '' ?>>
                                Online + Offline</option>
                        </select>
                        <?php if (isset($errors['usage_channel'])): ?><div class="invalid-feedback">
                                <?= e($errors['usage_channel']) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="starts_at">Bắt đầu <span class="text-danger">*</span></label>
                        <input type="datetime-local"
                            class="form-control <?= isset($errors['starts_at']) ? 'is-invalid' : '' ?>" id="starts_at"
                            name="starts_at" value="<?= e((string) ($old['starts_at'] ?? '')) ?>" required>
                        <?php if (isset($errors['starts_at'])): ?><div class="invalid-feedback">
                                <?= e($errors['starts_at']) ?></div><?php endif; ?>
                        <div id="starts-at-feedback" class="small datetime-feedback mt-1" aria-live="polite"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="ends_at">Kết thúc <span class="text-danger">*</span></label>
                        <input type="datetime-local"
                            class="form-control <?= isset($errors['ends_at']) ? 'is-invalid' : '' ?>" id="ends_at"
                            name="ends_at" value="<?= e((string) ($old['ends_at'] ?? '')) ?>" required>
                        <?php if (isset($errors['ends_at'])): ?><div class="invalid-feedback">
                                <?= e($errors['ends_at']) ?></div><?php endif; ?>
                        <div id="ends-at-feedback" class="small datetime-feedback mt-1" aria-live="polite"></div>
                    </div>
                </div>

                <div class="alert alert-light border small mb-4">
                    <strong>Quy tắc thời hạn:</strong> ngày bắt đầu có thể ở quá khứ nếu coupon đang chạy; ngày kết thúc
                    phải ở tương lai và phải sau ngày bắt đầu. Hệ thống lưu UTC nhưng hiển thị theo giờ Việt Nam.
                </div>

                <button type="submit" class="btn btn-primary" <?= $merchants === [] ? 'disabled' : '' ?>>Gửi coupon để
                    kiểm duyệt</button>
                <a class="btn btn-link" href="<?= e(url('/coupons')) ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>
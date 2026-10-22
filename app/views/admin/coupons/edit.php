<?php
use App\Core\Session;
$value = static fn(string $field): string => (string)($old[$field] ?? $coupon[$field] ?? '');
$invalid = static fn(string $field): string => isset($errors[$field]) ? ' is-invalid' : '';
?>
<div class="row justify-content-center"><div class="col-xl-9">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h3 mb-0">Sửa Coupon #<?= (int)$coupon['id'] ?></h1>
        <a href="<?= e(url('/admin/coupons')) ?>" class="btn btn-outline-secondary">Quay lại</a>
    </div>
    <div class="alert alert-warning">Trạng thái duyệt được giữ nguyên. Xác minh cộng đồng chỉ được đặt lại khi điều kiện sử dụng thay đổi; sửa tiêu đề hoặc danh mục vẫn giữ xác minh hiện có.</div>
    <?php if ($errors !== []): ?><div class="alert alert-danger" role="alert">Có lỗi dữ liệu. Vui lòng sửa các trường đánh dấu.</div><?php endif; ?>
    <form id="coupon-create-form" method="post" action="<?= e(url('/admin/coupons/'.(int)$coupon['id'].'/edit')) ?>" class="card shadow-sm">
        <div class="card-body p-4">
            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label class="form-label">Merchant (không thể chuyển)</label>
                    <input class="form-control" value="<?= e((string)$coupon['merchant_name']) ?>" readonly></div>
                <div class="col-md-6"><label for="coupon-category" class="form-label">Danh mục *</label>
                    <select id="coupon-category" name="category_id" required class="form-select<?= $invalid('category_id') ?>">
                        <option value="">Chọn danh mục</option>
                        <?php foreach ($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)$value('category_id')===(int)$cat['id'] ? 'selected' : '' ?>><?= e((string)$cat['name']) ?></option><?php endforeach; ?>
                    </select><?php if(isset($errors['category_id'])):?><div class="invalid-feedback"><?= e($errors['category_id']) ?></div><?php endif; ?>
                </div>
            </div>
            <div class="mb-3"><label for="coupon-title" class="form-label">Tên coupon *</label>
                <input id="coupon-title" name="title" class="form-control<?= $invalid('title') ?>" value="<?= e($value('title')) ?>" required maxlength="200">
                <?php if(isset($errors['title'])):?><div class="invalid-feedback"><?= e($errors['title']) ?></div><?php endif; ?>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="coupon-code" class="form-label">Mã coupon</label>
                    <input id="coupon-code" name="code" maxlength="100" class="form-control<?= $invalid('code') ?>" value="<?= e($value('code')) ?>">
                    <?php if(isset($errors['code'])):?><div class="invalid-feedback"><?= e($errors['code']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6"><label for="coupon-url" class="form-label">Promotion URL</label>
                    <input id="coupon-url" type="url" name="promotion_url" class="form-control<?= $invalid('promotion_url') ?>" value="<?= e($value('promotion_url')) ?>">
                    <?php if(isset($errors['promotion_url'])):?><div class="invalid-feedback"><?= e($errors['promotion_url']) ?></div><?php endif; ?>
                    <div class="form-text">Cần mã coupon hoặc Promotion URL.</div>
                </div>
            </div>
            <div class="mb-3"><label for="coupon-description" class="form-label">Mô tả</label>
                <textarea id="coupon-description" name="description" class="form-control" rows="3"><?= e($value('description')) ?></textarea></div>
            <div class="mb-3"><label for="coupon-terms" class="form-label">Điều kiện sử dụng *</label>
                <textarea id="coupon-terms" name="terms" class="form-control<?= $invalid('terms') ?>" rows="4" required><?= e($value('terms')) ?></textarea>
                <?php if(isset($errors['terms'])):?><div class="invalid-feedback"><?= e($errors['terms']) ?></div><?php endif; ?>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label for="discount_type" class="form-label">Loại giảm</label>
                    <select class="form-select<?= $invalid('discount_type') ?>" id="discount_type" name="discount_type" required>
                        <?php foreach(['PERCENTAGE'=>'Phần trăm','FIXED_AMOUNT'=>'Số tiền','FREE_SHIPPING'=>'Free Shipping','BUY_X_GET_Y'=>'Mua X tặng Y','GIFT'=>'Quà tặng','CASHBACK'=>'Hoàn tiền'] as $key=>$label): ?>
                            <option value="<?= e($key) ?>" <?= $value('discount_type')===$key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-md-4" id="discount-value-group"><label id="discount-value-label" for="discount_value" class="form-label">Giá trị giảm</label>
                    <input id="discount_value" name="discount_value" type="number" min="0" step="0.01" class="form-control<?= $invalid('discount_value') ?>" value="<?= e($value('discount_value')) ?>">
                    <?php if(isset($errors['discount_value'])):?><div class="invalid-feedback"><?= e($errors['discount_value']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4" id="cashback-unit-group"><label for="cashback_unit" class="form-label">Đơn vị hoàn</label>
                    <select id="cashback_unit" name="cashback_unit" class="form-select">
                        <?php foreach(['PERCENTAGE'=>'Phần trăm','FIXED_AMOUNT'=>'Số tiền'] as $key=>$label): ?>
                            <option value="<?= e($key) ?>" <?= $value('cashback_unit')===$key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4"><label for="min-order" class="form-label">Đơn tối thiểu (VND)</label>
                    <input id="min-order" name="min_order_amount" type="number" min="0" step="0.01" class="form-control<?= $invalid('min_order_amount') ?>" value="<?= e($value('min_order_amount')) ?>">
                    <?php if(isset($errors['min_order_amount'])):?><div class="invalid-feedback"><?= e($errors['min_order_amount']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4"><label for="max-discount" class="form-label">Giảm tối đa (VND)</label>
                    <input id="max-discount" name="max_discount_amount" type="number" min="0" step="0.01" class="form-control<?= $invalid('max_discount_amount') ?>" value="<?= e($value('max_discount_amount')) ?>">
                    <?php if(isset($errors['max_discount_amount'])):?><div class="invalid-feedback"><?= e($errors['max_discount_amount']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-4"><label for="usage_channel" class="form-label">Hình thức</label>
                    <select id="usage_channel" name="usage_channel" class="form-select">
                        <?php foreach(['ONLINE'=>'Online','OFFLINE'=>'Offline','BOTH'=>'Cả hai'] as $key=>$label): ?>
                            <option value="<?= e($key) ?>" <?= $value('usage_channel')===$key ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select></div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="starts_at" class="form-label">Bắt đầu *</label>
                    <input id="starts_at" name="starts_at" type="datetime-local" step="1" class="form-control<?= $invalid('starts_at') ?>" required value="<?= e($value('starts_at')) ?>">
                    <div id="starts-at-feedback" class="small mt-1"></div>
                    <?php if(isset($errors['starts_at'])):?><div class="text-danger small"><?= e($errors['starts_at']) ?></div><?php endif; ?>
                </div>
                <div class="col-md-6"><label for="ends_at" class="form-label">Kết thúc *</label>
                    <input id="ends_at" name="ends_at" type="datetime-local" step="1" class="form-control<?= $invalid('ends_at') ?>" required value="<?= e($value('ends_at')) ?>">
                    <div id="ends-at-feedback" class="small mt-1"></div>
                    <?php if(isset($errors['ends_at'])):?><div class="text-danger small"><?= e($errors['ends_at']) ?></div><?php endif; ?>
                </div>
            </div>
            <button class="btn btn-primary" type="submit">Lưu chỉnh sửa</button>
        </div>
    </form>
</div></div>

<?php
/** @var \App\Models\Merchant[] $merchants */
$slotLabels = [
    'shop'=>'Shop Voucher — giảm giá đơn hàng',
    'shipping'=>'Free Shipping — miễn phí vận chuyển',
    'payment'=>'Payment Voucher — ưu đãi thanh toán (tự phân loại)',
];
?>
<div class="container py-4 py-lg-5" id="coupon-stack-checker">
    <nav aria-label="Đường dẫn" class="small mb-3"><a href="<?= e(url('/coupons')) ?>">Coupon</a> / Kiểm tra kết hợp mã
    </nav>
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <h1 class="h2 fw-bold">Kiểm tra khả năng kết hợp coupon</h1>
            <div class="alert alert-warning" role="note"><strong>Lưu ý:</strong> CouponHub hiện chưa lưu quy tắc cho
                phép cộng dồn hoặc điều kiện thanh toán do Merchant xác nhận. Công cụ chỉ kiểm tra điều kiện cơ bản và
                <strong>ước tính mức tiết kiệm tối đa theo giả định</strong>, không xác nhận chắc chắn nhiều mã có thể
                dùng cùng lúc.</div>
            <form method="get" action="<?= e(url('/tools/stack-checker')) ?>" class="card shadow-sm mb-4"
                aria-label="Kiểm tra kết hợp coupon">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label fw-semibold"
                                for="stack-merchant">Merchant</label>
                            <select id="stack-merchant" name="merchant_id"
                                class="form-select <?= isset($errors['merchant_id']) ? 'is-invalid' : '' ?>" required>
                                <option value="">-- Chọn cửa hàng --</option>
                                <?php foreach ($merchants as $merchant): ?><option value="<?= (int)$merchant->id ?>"
                                    <?= $input['merchant_id'] === (string)$merchant->id ? 'selected' : '' ?>>
                                    <?= e($merchant->name) ?></option><?php endforeach; ?>
                            </select><?php if (isset($errors['merchant_id'])): ?><div class="invalid-feedback">
                                <?= e($errors['merchant_id']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6"><label class="form-label" for="stack-channel">Kênh sử dụng</label><select
                                id="stack-channel" name="channel"
                                class="form-select <?= isset($errors['channel']) ? 'is-invalid' : '' ?>">
                                <option value="ONLINE" <?= $input['channel'] === 'ONLINE' ? 'selected' : '' ?>>Trực
                                    tuyến</option>
                                <option value="OFFLINE" <?= $input['channel'] === 'OFFLINE' ? 'selected' : '' ?>>Tại cửa
                                    hàng</option>
                            </select></div>
                        <div class="col-md-6"><label class="form-label" for="stack-amount">Giá trị đơn (đ)</label><input
                                id="stack-amount" name="order_amount"
                                class="form-control <?= isset($errors['order_amount']) ? 'is-invalid' : '' ?>"
                                type="number" min="1" max="1000000000" step="1" value="<?= e($input['order_amount']) ?>"
                                required><?php if (isset($errors['order_amount'])): ?><div class="invalid-feedback">
                                <?= e($errors['order_amount']) ?></div><?php endif; ?></div>
                        <div class="col-md-6"><label class="form-label" for="stack-fee">Phí vận chuyển (đ)</label><input
                                id="stack-fee" name="shipping_fee"
                                class="form-control <?= isset($errors['shipping_fee']) ? 'is-invalid' : '' ?>"
                                type="number" min="0" max="100000000" step="1"
                                value="<?= e($input['shipping_fee']) ?>"><?php if (isset($errors['shipping_fee'])): ?>
                            <div class="invalid-feedback"><?= e($errors['shipping_fee']) ?></div><?php endif; ?></div>
                        <?php foreach ($slotLabels as $slot => $label): ?><div class="col-12"><label
                                class="form-label fw-semibold"
                                for="stack-<?= e($slot) ?>"><?= e($label) ?></label><select id="stack-<?= e($slot) ?>"
                                name="<?= e($slot) ?>"
                                class="form-select <?= isset($errors[$slot]) ? 'is-invalid' : '' ?>">
                                <option value="">-- Không dùng --</option>
                                <?php foreach ($options as $coupon): ?><?php $freeship = $coupon['discount_type'] === 'FREE_SHIPPING'; if (($slot === 'shipping') !== $freeship) continue; ?>
                                <option value="<?= (int)$coupon['id'] ?>"
                                    <?= $input[$slot] === (string)$coupon['id'] ? 'selected' : '' ?>>
                                    #<?= (int)$coupon['id'] ?> — <?= e((string)$coupon['title']) ?></option>
                                <?php endforeach; ?>
                            </select><?php if (isset($errors[$slot])): ?><div class="invalid-feedback">
                                <?= e($errors[$slot]) ?></div><?php endif; ?></div><?php endforeach; ?>
                        <div class="col-12 small text-muted">Chỉ lấy coupon ACTIVE cùng Merchant và kênh. Chọn Merchant,
                            bấm “Tải danh sách coupon”, sau đó chọn các mã rồi kiểm tra. Nhãn Payment Voucher do bạn tự
                            chọn; hệ thống chưa có thông tin phương thức thanh toán để xác thực.</div>
                        <div class="col-12 d-flex gap-2 flex-wrap"><button class="btn btn-outline-secondary"
                                type="submit">Tải danh sách coupon</button><button class="btn btn-primary" name="check"
                                value="1" type="submit">Kiểm tra kết hợp</button><a class="btn btn-outline-secondary"
                                href="<?= e(url('/tools/stack-checker')) ?>">Nhập lại</a></div>
                    </div>
                </div>
            </form>
            <?php if ($submitted && $errors === [] && $outcome !== null): ?><section aria-labelledby="stack-result">
                <h2 class="h4" id="stack-result">Kết quả kiểm tra</h2>
                <div class="alert <?= $outcome['status'] === 'INCOMPATIBLE' ? 'alert-danger' : ($outcome['status'] === 'UNVERIFIED' ? 'alert-warning' : 'alert-info') ?>"
                    role="status">
                    <strong><?= e(match ($outcome['status']) { 'INCOMPATIBLE'=>'Không đạt điều kiện cơ bản', 'UNVERIFIED'=>'Chưa thể xác minh khả năng cộng dồn', default=>'Một coupon đủ điều kiện cơ bản' }) ?></strong>
                    <p class="mb-0"><?= e($outcome['reason']) ?></p>
                </div>
                <?php if ($outcome['details'] !== []): ?><div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Vị trí</th>
                                <th>Coupon</th>
                                <th class="text-end">Ước tính riêng lẻ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($outcome['details'] as $detail): ?><tr>
                                <td><?= e($slotLabels[$detail['slot']]) ?></td>
                                <td><a
                                        href="<?= e(url('/coupons/' . (int)$detail['id'])) ?>"><?= e($detail['title']) ?></a>
                                </td>
                                <td class="text-end"><?= e(money($detail['saving'])) ?></td>
                            </tr><?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p><strong>Trần tiết kiệm lý thuyết:</strong> <?= e(money($outcome['maximum_saving'])) ?>. <strong>Mức
                        thanh toán thấp nhất theo giả định:</strong> <?= e(money($outcome['minimum_payable'])) ?>.</p>
                <p class="small text-muted">Mỗi mã được ước tính độc lập trên giá trị đơn ban đầu. Merchant có thể giới
                    hạn cộng dồn, áp dụng mã sau trên giá đã giảm, hoặc yêu cầu ngân hàng/thẻ cụ thể. Không dùng kết quả
                    này làm mức giảm cam kết.</p><?php endif; ?>
            </section><?php endif; ?>
        </div>
    </div>
</div>
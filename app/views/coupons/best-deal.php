<?php

/** @var \App\Models\Merchant[] $merchants */
$discountNames = [
    'PERCENTAGE' => 'Giảm theo %',
    'FIXED_AMOUNT' => 'Giảm trực tiếp',
    'FREE_SHIPPING' => 'Miễn phí vận chuyển',
];
?>
<div class="container py-4 py-lg-5" id="best-deal-tool">
    <nav aria-label="Đường dẫn" class="small mb-3"><a href="<?= e(url('/coupons')) ?>">Coupon</a> / Công cụ so sánh
    </nav>
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <h1 class="h2 fw-bold mb-2">Tìm coupon tiết kiệm nhất</h1>
            <p class="text-muted">So sánh <strong>một coupon mỗi lần</strong> cho cùng cửa hàng và giá trị đơn hàng. Đây
                là mô phỏng, không phải báo giá của Merchant.</p>

            <form method="get" action="<?= e(url('/tools/best-deal')) ?>" class="card shadow-sm mb-4"
                aria-label="Thông tin đơn hàng">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="bd-merchant" class="form-label fw-semibold">Cửa hàng</label>
                            <select class="form-select <?= isset($errors['merchant_id']) ? 'is-invalid' : '' ?>"
                                id="bd-merchant" name="merchant_id" required>
                                <option value="">-- Chọn Merchant --</option>
                                <?php foreach ($merchants as $merchant): ?>
                                    <option value="<?= (int)$merchant->id ?>"
                                        <?= (int)$merchant->id === (int)$merchantInput ? 'selected' : '' ?>>
                                        <?= e($merchant->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['merchant_id'])): ?><div class="invalid-feedback">
                                    <?= e($errors['merchant_id']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="bd-channel" class="form-label fw-semibold">Kênh sử dụng</label>
                            <select class="form-select <?= isset($errors['channel']) ? 'is-invalid' : '' ?>"
                                id="bd-channel" name="channel">
                                <option value="ONLINE" <?= $channel === 'ONLINE' ? 'selected' : '' ?>>Online</option>
                                <option value="OFFLINE" <?= $channel === 'OFFLINE' ? 'selected' : '' ?>>Tại cửa hàng
                                </option>
                            </select>
                            <?php if (isset($errors['channel'])): ?><div class="invalid-feedback">
                                    <?= e($errors['channel']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="bd-order" class="form-label fw-semibold">Giá trị đơn hàng (đ)</label>
                            <input type="number"
                                class="form-control <?= isset($errors['order_amount']) ? 'is-invalid' : '' ?>"
                                id="bd-order" name="order_amount" value="<?= e($orderInput) ?>" min="1" max="1000000000"
                                step="1" inputmode="numeric" required placeholder="Ví dụ: 650000">
                            <?php if (isset($errors['order_amount'])): ?><div class="invalid-feedback">
                                    <?= e($errors['order_amount']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="bd-shipping" class="form-label fw-semibold">Phí vận chuyển dự kiến (đ)</label>
                            <input type="number"
                                class="form-control <?= isset($errors['shipping_fee']) ? 'is-invalid' : '' ?>"
                                id="bd-shipping" name="shipping_fee" value="<?= e($shippingInput) ?>" min="0"
                                max="100000000" step="1" inputmode="numeric" placeholder="0">
                            <?php if (isset($errors['shipping_fee'])): ?><div class="invalid-feedback">
                                    <?= e($errors['shipping_fee']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12 d-flex align-items-center gap-3 flex-wrap">
                            <button type="submit" class="btn btn-primary">Tính và so sánh ưu đãi</button>
                            <a class="btn btn-outline-secondary" href="<?= e(url('/tools/best-deal')) ?>">Nhập lại</a>
                        </div>
                    </div>
                </div>
            </form>

            <?php if ($comparison !== null && $order !== null && $shipping !== null): ?>
                <section aria-labelledby="bd-results-heading" class="mb-4">
                    <h2 class="h4 fw-bold" id="bd-results-heading">Kết quả ước tính — <?= e($merchantName) ?></h2>
                    <p class="text-muted small">Giá trị đơn: <?= e(money($order)) ?>; phí vận chuyển:
                        <?= e(money($shipping)) ?>. Không cộng dồn nhiều coupon.</p>
                    <?php if ($comparison['eligible'] === []): ?>
                        <div class="alert alert-info" role="status">Không có coupon đang hoạt động và có thể tính mức giảm trực
                            tiếp cho đơn hàng này. Hãy kiểm tra đơn tối thiểu, kênh mua hàng hoặc thử Merchant khác.</div>
                    <?php else: ?>
                        <?php $top = $comparison['eligible'][0]; ?>
                        <div class="card border-primary mb-4">
                            <div class="card-body p-4">
                                <div class="text-uppercase text-primary small fw-bold mb-2">Ưu đãi tốt nhất theo số tiền giảm dự
                                    kiến</div>
                                <h3 class="h5 mb-2"><a
                                        href="<?= e(url('/coupons/' . (int)$top['id'])) ?>"><?= e($top['title']) ?></a></h3>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <div class="small text-muted">Giảm dự kiến</div>
                                        <div class="h3 fw-bold text-success mb-0"><?= e(money($top['estimated_saving'])) ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="small text-muted">Tổng tiền dự kiến</div>
                                        <div class="h3 fw-bold mb-0"><?= e(money($top['estimated_payable'])) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <caption class="caption-top">So sánh <?= count($comparison['eligible']) ?> ưu đãi đủ điều kiện
                                </caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Coupon</th>
                                        <th scope="col">Loại</th>
                                        <th scope="col" class="text-end">Giảm dự kiến</th>
                                        <th scope="col" class="text-end">Tiền phải trả</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($comparison['eligible'] as $candidate): ?>
                                        <tr>
                                            <td><a
                                                    href="<?= e(url('/coupons/' . (int)$candidate['id'])) ?>"><?= e($candidate['title']) ?></a>
                                            </td>
                                            <td><?= e($discountNames[(string)$candidate['discount_type']] ?? (string)$candidate['discount_type']) ?>
                                            </td>
                                            <td class="text-end text-success fw-semibold">
                                                <?= e(money($candidate['estimated_saving'])) ?></td>
                                            <td class="text-end"><?= e(money($candidate['estimated_payable'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    <?php if ($comparison['excluded'] > 0): ?>
                        <p class="small text-muted"><?= (int)$comparison['excluded'] ?> coupon không đủ điều kiện đơn hàng hoặc
                            không có mức giảm trực tiếp hợp lệ.</p>
                    <?php endif; ?>
                </section>
            <?php elseif (!$submitted): ?>
                <div class="alert alert-light border" role="note">Chọn Merchant, nhập giá trị đơn hàng và phí vận chuyển để
                    xem bảng so sánh.</div>
            <?php endif; ?>
            <p class="small"><a href="<?= e(url('/tools/match')) ?>">Tìm mã theo nhu cầu</a> · <a
                    href="<?= e(url('/tools/stack-checker') . '?merchant_id=' . (int)$merchantId) ?>">Kiểm tra khả năng
                    kết hợp nhiều mã</a></p>
            <p class="small text-muted">Chỉ tính các coupon công khai, đang hiệu lực, bằng VND và thuộc loại giảm %,
                giảm tiền hoặc miễn phí vận chuyển. Cashback, quà tặng và mua X tặng Y chưa được quy đổi. Điều kiện sản
                phẩm, thanh toán và giới hạn sử dụng thực tế vẫn phải được kiểm tra trên trang Merchant trước khi đặt
                hàng.</p>
        </div>
    </div>
</div>
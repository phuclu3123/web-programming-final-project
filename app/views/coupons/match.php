<?php
/** @var \App\Models\Merchant[] $merchants */
/** @var \App\Models\Category[] $categories */
?>
<div class="container py-4 py-lg-5" id="smart-matching-tool">
    <nav aria-label="Đường dẫn" class="small mb-3"><a href="<?= e(url('/coupons')) ?>">Coupon</a> / Tìm theo nhu cầu
    </nav>
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <h1 class="h2 fw-bold">Tìm coupon phù hợp với nhu cầu</h1>
            <p class="text-muted">Tìm kiếm theo từ khóa, Merchant, danh mục và giá trị đơn hàng. Đây là <strong>gợi ý
                    theo quy tắc</strong>, không phải tư vấn AI hay xác nhận coupon dùng được tại quầy thanh toán.</p>
            <form method="get" action="<?= e(url('/tools/match')) ?>" class="card shadow-sm mb-4"
                aria-label="Thông tin tìm coupon">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-12"><label for="match-q" class="form-label fw-semibold">Bạn muốn mua gì?</label>
                            <input id="match-q" name="q" type="search" maxlength="160"
                                class="form-control <?= isset($errors['q']) ? 'is-invalid' : '' ?>"
                                value="<?= e($input['q']) ?>"
                                placeholder="Ví dụ: Tôi muốn mua giày Nike khoảng 2 triệu">
                            <div class="form-text">Có thể nhận diện ngân sách đơn giản như “2 triệu” hoặc “500k”.</div>
                            <?php if (isset($errors['q'])): ?><div class="invalid-feedback d-block">
                                <?= e($errors['q']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6"><label for="match-merchant" class="form-label">Merchant (không bắt
                                buộc)</label>
                            <select id="match-merchant" name="merchant_id"
                                class="form-select <?= isset($errors['merchant_id']) ? 'is-invalid' : '' ?>">
                                <option value="">Tất cả cửa hàng</option>
                                <?php foreach ($merchants as $merchant): ?><option value="<?= (int)$merchant->id ?>"
                                    <?= $input['merchant_id'] === (string)$merchant->id ? 'selected' : '' ?>>
                                    <?= e($merchant->name) ?></option><?php endforeach; ?>
                            </select><?php if (isset($errors['merchant_id'])): ?><div class="invalid-feedback">
                                <?= e($errors['merchant_id']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6"><label for="match-category" class="form-label">Danh mục (không bắt
                                buộc)</label>
                            <select id="match-category" name="category_id"
                                class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>">
                                <option value="">Tất cả danh mục</option>
                                <?php foreach ($categories as $category): ?><option value="<?= (int)$category->id ?>"
                                    <?= $input['category_id'] === (string)$category->id ? 'selected' : '' ?>>
                                    <?= e($category->name) ?></option><?php endforeach; ?>
                            </select><?php if (isset($errors['category_id'])): ?><div class="invalid-feedback">
                                <?= e($errors['category_id']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6"><label for="match-amount" class="form-label">Giá trị đơn hàng (đ, không
                                bắt buộc)</label>
                            <input id="match-amount" name="order_amount" type="number" step="1" min="1" max="1000000000"
                                class="form-control <?= isset($errors['order_amount']) ? 'is-invalid' : '' ?>"
                                placeholder="Ví dụ: 2000000" value="<?= e($input['order_amount']) ?>">
                            <?php if (isset($errors['order_amount'])): ?><div class="invalid-feedback">
                                <?= e($errors['order_amount']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6"><label for="match-channel" class="form-label">Kênh mua hàng</label>
                            <select id="match-channel" name="channel"
                                class="form-select <?= isset($errors['channel']) ? 'is-invalid' : '' ?>">
                                <option value="ONLINE" <?= $input['channel'] === 'ONLINE' ? 'selected' : '' ?>>Trực
                                    tuyến</option>
                                <option value="OFFLINE" <?= $input['channel'] === 'OFFLINE' ? 'selected' : '' ?>>Tại cửa
                                    hàng</option>
                            </select>
                            <?php if (isset($errors['channel'])): ?><div class="invalid-feedback">
                                <?= e($errors['channel']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12 d-flex gap-2 flex-wrap"><button class="btn btn-primary" type="submit">Tìm
                                coupon phù hợp</button><a class="btn btn-outline-secondary"
                                href="<?= e(url('/tools/match')) ?>">Nhập lại</a></div>
                    </div>
                </div>
            </form>
            <?php if ($submitted && $errors === []): ?>
            <section aria-labelledby="match-results-title">
                <h2 class="h4" id="match-results-title">Gợi ý dựa trên thông tin đã nhập</h2>
                <?php if ($budget !== null): ?><p class="small text-muted">Giá trị đơn được dùng để kiểm tra mức chi tối
                    thiểu: <?= e(money($budget)) ?>. Ước tính giảm trực tiếp không bao gồm phí vận chuyển.</p>
                <?php endif; ?>
                <?php if ($matches === []): ?><div class="alert alert-info" role="status">Chưa tìm thấy coupon đang hoạt
                    động phù hợp. Thử từ khóa ngắn hơn, điều chỉnh Merchant hoặc bỏ bớt bộ lọc.</div>
                <?php else: ?><div class="row row-cols-1 row-cols-md-2 g-3">
                    <?php foreach ($matches as $coupon): ?><div class="col">
                        <article class="card h-100">
                            <div class="card-body">
                                <div class="small text-muted mb-2"><?= e((string)$coupon['merchant_name']) ?> ·
                                    <?= e((string)$coupon['category_name']) ?></div>
                                <h3 class="h5"><a
                                        href="<?= e(url('/coupons/' . (int)$coupon['id'])) ?>"><?= e((string)$coupon['title']) ?></a>
                                </h3>
                                <div class="small">Đơn tối thiểu: <?= e(money((float)$coupon['min_order_amount'])) ?>
                                </div>
                                <div class="small">Hết hạn (UTC): <?= e((string)$coupon['ends_at']) ?></div>
                                <?php if ($coupon['estimated_saving'] !== null): ?><div
                                    class="fw-semibold text-success mt-2">Giảm trực tiếp dự kiến:
                                    <?= e(money((int)$coupon['estimated_saving'])) ?></div>
                                <?php else: ?><div class="small text-muted mt-2">Cần kiểm tra điều kiện ưu đãi, phí vận
                                    chuyển hoặc điều kiện trên trang Merchant.</div><?php endif; ?>
                            </div>
                        </article>
                    </div><?php endforeach; ?>
                </div><?php endif; ?>
            </section>
            <?php endif; ?>
            <p class="small text-muted mt-4">Gợi ý từ tối đa 500 coupon công khai và đang hoạt động, dựa trên trùng khớp
                từ khóa, Merchant, danh mục, đơn tối thiểu và giảm trực tiếp ước tính. Từ khóa không phải công cụ hiểu
                ngữ nghĩa/AI; kết quả có thể chưa bao quát toàn bộ kho coupon. Điều khoản và hạn sử dụng thực tế do
                Merchant quyết định.</p>
        </div>
    </div>
</div>
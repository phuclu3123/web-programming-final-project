<?php
use App\Core\Session;
?>
<?php
require __DIR__ . '/layouts/head.php';
require __DIR__ . '/layouts/workspace-start.php';
$errorFor = static fn (string $name): string => isset($errors[$name])
    ? '<div class="field-error">' . e($errors[$name]) . '</div>'
    : '';
$rejectedCount = count(array_filter(
    $coupons,
    static fn (array $coupon): bool => $coupon['moderation_status'] === 'REJECTED'
));
$stats['rejected'] = $stats['rejected'] ?? $rejectedCount;
?>

<div class="page-heading">
    <div>
        <div class="eyebrow">DÀNH CHO ĐỐI TÁC</div>
        <h1>Quản lý ưu đãi</h1>
        <p>Tạo mã mới, xem trước thông tin khách hàng sẽ thấy và theo dõi quá trình duyệt.</p>
    </div>
    <a class="btn btn-outline-brand" href="#my-coupons">Xem mã đã đăng <?= icon('arrow') ?></a>
</div>

<?php if ($success): ?><div class="notice" role="status"><?= e($success) ?></div><?php endif; ?>
<?php if ($errors): ?>
<div class="notice error" role="alert">
    <strong>Chưa thể gửi mã.</strong>
    <?= e($errors['general'] ?? 'Kiểm tra các trường được đánh dấu bên dưới rồi thử lại.') ?>
</div>
<?php endif; ?>

<div class="stats-grid four" id="overview">
    <?php foreach (['total'=>'Tổng mã đã đăng','approved'=>'Đã duyệt','pending'=>'Chờ duyệt','rejected'=>'Bị từ chối'] as $key=>$label): ?>
    <div class="stat-card <?= $key === 'approved' ? 'accent' : '' ?>">
        <div class="stat-label"><?= e($label) ?><?= icon(match ($key) { 'approved'=>'check', 'rejected'=>'flag', default=>'ticket' }) ?></div>
        <div class="stat-value"><?= (int) $stats[$key] ?></div>
        <div class="stat-foot"><?= $key === 'pending' ? 'Đang chờ kiểm duyệt' : 'Trong các cửa hàng bạn quản lý' ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="editor-grid">
    <section class="panel create-panel" id="create-coupon">
        <div class="panel-head">
            <div>
                <div class="panel-kicker">TẠO ƯU ĐÃI MỚI</div>
                <h2>Thông tin ưu đãi</h2>
                <p>Mã mới luôn bắt đầu ở trạng thái <strong>Chờ duyệt</strong>.</p>
            </div>
            <span class="badge-soft badge-pending">Chờ duyệt sau khi gửi</span>
        </div>

        <form id="coupon-form" method="post" action="shop.php">
            <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
            <div class="panel-body">
                <?php if (!$merchants || !$categories): ?>
                <div class="notice error">Cần có cửa hàng đã duyệt và danh mục đang hoạt động trước khi tạo mã.</div>
                <?php endif; ?>

                <div class="field-section"><span>01</span>Thông tin cơ bản</div>
                <div class="form-section-card">
                    <div class="mb-3">
                        <label class="form-label" for="merchant-select">Cửa hàng của bạn *</label>
                        <select class="form-select" name="merchant_id" id="merchant-select" required>
                            <?php foreach ($merchants as $merchant): ?>
                            <option value="<?= (int) $merchant['id'] ?>" <?= (string) $merchant['id'] === $input['merchant_id'] ? 'selected' : '' ?>>
                                <?= e($merchant['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?= $errorFor('merchant_id') ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="coupon-title">Tên ưu đãi *</label>
                        <input class="form-control" id="coupon-title" name="title" value="<?= e($input['title']) ?>"
                            maxlength="200" placeholder="Ví dụ: Giảm 20% cho đơn hàng đầu tiên" required>
                        <?= $errorFor('title') ?>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="coupon-code">Mã giảm giá *</label>
                            <input class="form-control coupon-code-input" id="coupon-code" name="code"
                                value="<?= e($input['code']) ?>" maxlength="32" pattern="[A-Za-z0-9_-]{3,32}"
                                placeholder="Ví dụ: MAYXINH50" autocomplete="off" required>
                            <div class="form-text">3–32 ký tự; chữ, số, dấu <code>-</code> hoặc <code>_</code>.</div>
                            <?= $errorFor('code') ?>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="coupon-category">Danh mục *</label>
                            <select class="form-select" id="coupon-category" name="category_id" required>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= (string) $category['id'] === $input['category_id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?= $errorFor('category_id') ?>
                        </div>
                    </div>
                </div>

                <div class="field-section"><span>02</span>Giá trị ưu đãi</div>
                <div class="form-section-card">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="discount-type">Loại giảm *</label>
                            <select class="form-select" id="discount-type" name="discount_type">
                                <option value="PERCENTAGE" <?= $input['discount_type'] === 'PERCENTAGE' ? 'selected' : '' ?>>Theo phần trăm (%)</option>
                                <option value="FIXED_AMOUNT" <?= $input['discount_type'] === 'FIXED_AMOUNT' ? 'selected' : '' ?>>Theo số tiền (đ)</option>
                            </select>
                            <?= $errorFor('discount_type') ?>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="discount-value">Giá trị giảm <span id="discount-unit">(<?= $input['discount_type'] === 'PERCENTAGE' ? '%' : 'đ' ?>)</span> *</label>
                            <input class="form-control" id="discount-value" name="discount_value" type="number" min="1"
                                max="<?= $input['discount_type'] === 'PERCENTAGE' ? '100' : '1000000000' ?>" step="1"
                                value="<?= e($input['discount_value']) ?>" required>
                            <?= $errorFor('discount_value') ?>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="min-order">Đơn tối thiểu (đ)</label>
                            <input class="form-control" id="min-order" name="min_order_amount" type="number" min="0"
                                max="1000000000" step="1" value="<?= e($input['min_order_amount']) ?>" required>
                            <?= $errorFor('min_order_amount') ?>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="max-discount">Giảm tối đa (đ)</label>
                            <input class="form-control" id="max-discount" name="max_discount_amount" type="number" min="1"
                                max="1000000000" step="1" value="<?= e($input['max_discount_amount']) ?>">
                            <div class="form-text">Chỉ dùng cho giảm theo %. Để trống nếu không giới hạn.</div>
                            <?= $errorFor('max_discount_amount') ?>
                        </div>
                    </div>
                </div>

                <div class="field-section"><span>03</span>Thời gian & điều kiện</div>
                <div class="form-section-card">
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label" for="start-date">Ngày bắt đầu *</label>
                            <input class="form-control" id="start-date" name="starts_on" type="date" value="<?= e($input['starts_on']) ?>" required>
                            <?= $errorFor('starts_on') ?>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label" for="end-date">Ngày kết thúc *</label>
                            <input class="form-control" id="end-date" name="expires_on" type="date" value="<?= e($input['expires_on']) ?>" required>
                            <?= $errorFor('expires_on') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="usage-channel">Kênh sử dụng *</label>
                        <select class="form-select" id="usage-channel" name="usage_channel">
                            <?php foreach (['BOTH'=>'Online và tại cửa hàng','ONLINE'=>'Online','OFFLINE'=>'Tại cửa hàng'] as $value=>$label): ?>
                            <option value="<?= e($value) ?>" <?= $input['usage_channel'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $errorFor('usage_channel') ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="coupon-description">Mô tả ưu đãi *</label>
                        <textarea class="form-control" id="coupon-description" name="description" rows="3" maxlength="1000"
                            placeholder="Nói ngắn gọn khách hàng nhận được gì…" required><?= e($input['description']) ?></textarea>
                        <?= $errorFor('description') ?>
                    </div>

                    <div>
                        <label class="form-label" for="coupon-terms">Điều kiện áp dụng *</label>
                        <textarea class="form-control" id="coupon-terms" name="terms" rows="3" maxlength="1000"
                            placeholder="Sản phẩm áp dụng, số lần sử dụng, khung giờ, trường hợp loại trừ…" required><?= e($input['terms']) ?></textarea>
                        <?= $errorFor('terms') ?>
                    </div>
                </div>
            </div>

            <div class="panel-foot submit-foot">
                <div>
                    <strong>Trạng thái sau khi gửi: Chờ duyệt</strong>
                    <p>Mã sẽ xuất hiện trên trang khám phá sau khi được duyệt và đến ngày bắt đầu.</p>
                </div>
                <button type="submit" class="btn btn-brand" <?= !$merchants || !$categories ? 'disabled' : '' ?>>
                    Gửi duyệt <?= icon('arrow') ?>
                </button>
            </div>
        </form>
    </section>

    <aside class="preview-column" aria-label="Xem trước ưu đãi">
        <div class="preview-title">XEM TRƯỚC PHÍA KHÁCH HÀNG</div>
        <article class="coupon-card preview-card">
            <div class="coupon-body">
                <div class="coupon-store">
                    <span class="store-logo" id="preview-logo"><?= e(textPrefix($merchants[0]['name'] ?? 'Cửa hàng')) ?></span>
                    <div>
                        <div class="store-name" id="preview-merchant"><?= e($merchants[0]['name'] ?? 'Cửa hàng') ?></div>
                        <div class="store-category" id="preview-category"><?= e($categories[0]['name'] ?? 'Danh mục') ?></div>
                    </div>
                    <span class="badge-soft badge-neutral">Xem trước</span>
                </div>
                <div class="coupon-saving" id="preview-saving">Giảm <?= e(number_format((float) $input['discount_value'], 0, ',', '.')) ?><?= $input['discount_type'] === 'PERCENTAGE' ? '%' : 'đ' ?></div>
                <h3 id="preview-title"><?= e($input['title'] ?: 'Tên ưu đãi của bạn') ?></h3>
                <p class="coupon-description" id="preview-description"><?= e($input['description'] ?: 'Mô tả ngắn giúp khách hàng hiểu giá trị ưu đãi.') ?></p>
                <div class="coupon-facts">
                    <span id="preview-min-order"><?= (float) $input['min_order_amount'] > 0 ? 'Đơn từ ' . e(money($input['min_order_amount'])) : 'Không yêu cầu đơn tối thiểu' ?></span>
                    <span id="preview-max-discount" <?= $input['discount_type'] !== 'PERCENTAGE' || $input['max_discount_amount'] === '' ? 'hidden' : '' ?>>Tối đa <?= e(money($input['max_discount_amount'] ?: 0)) ?></span>
                </div>
                <details class="coupon-terms">
                    <summary>Điều kiện sử dụng</summary>
                    <p id="preview-terms"><?= e($input['terms'] ?: 'Điều kiện áp dụng sẽ hiển thị tại đây.') ?></p>
                    <p>Kênh áp dụng: <span id="preview-channel"><?= e(channelLabel($input['usage_channel'])) ?></span>.</p>
                </details>
                <div class="coupon-meta"><?= icon('clock') ?><span id="preview-expiry">Đến <?= e(day($input['expires_on'])) ?></span></div>
            </div>
            <div class="coupon-bottom">
                <span class="coupon-code" id="preview-code"><?= e($input['code'] ?: 'MÃ CỦA BẠN') ?></span>
                <button class="btn btn-outline-brand" disabled><?= icon('copy') ?> Sao chép</button>
            </div>
        </article>

        <div class="helper-box merchant-helper">
            <h3>Checklist trước khi gửi</h3>
            <ul class="clean-list">
                <li><?= icon('check') ?> Mã ưu đãi nhập đúng và dễ đọc</li>
                <li><?= icon('check') ?> Thời gian bắt đầu/kết thúc chính xác</li>
                <li><?= icon('check') ?> Điều kiện không gây hiểu nhầm</li>
            </ul>
        </div>
    </aside>
</div>

<section class="panel table-panel" id="my-coupons">
    <div class="panel-head table-panel-head">
        <div>
            <div class="panel-kicker">LỊCH SỬ GỬI DUYỆT</div>
            <h2>Mã đã đăng</h2>
            <p role="status" aria-live="polite"><span id="shop-visible-count"><?= count($coupons) ?></span> / <?= count($coupons) ?> mã đang hiển thị · tối đa 100 mã gần nhất</p>
        </div>
        <div class="table-search-wrap">
            <?= icon('search') ?>
            <label class="visually-hidden" for="shop-coupon-search">Tìm mã đã đăng</label>
            <input id="shop-coupon-search" class="form-control" type="search" placeholder="Tìm theo tên, mã, cửa hàng…">
        </div>
    </div>

    <div class="status-filter-bar" role="group" aria-label="Lọc trạng thái">
        <button type="button" class="status-filter" data-shop-status="ALL" aria-pressed="true">Tất cả <span><?= (int) $stats['total'] ?></span></button>
        <button type="button" class="status-filter" data-shop-status="PENDING" aria-pressed="false">Chờ duyệt <span><?= (int) $stats['pending'] ?></span></button>
        <button type="button" class="status-filter" data-shop-status="APPROVED" aria-pressed="false">Đã duyệt <span><?= (int) $stats['approved'] ?></span></button>
        <button type="button" class="status-filter" data-shop-status="REJECTED" aria-pressed="false">Từ chối <span><?= (int) $stats['rejected'] ?></span></button>
    </div>

    <div class="table-responsive">
        <table class="table merchant-table">
            <caption class="visually-hidden">Mã thuộc các cửa hàng bạn quản lý</caption>
            <thead>
                <tr>
                    <th>ƯU ĐÃI / CỬA HÀNG</th>
                    <th>MÃ</th>
                    <th>HẾT HẠN</th>
                    <th>TRẠNG THÁI</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                <tr data-shop-row data-status="<?= e($coupon['moderation_status']) ?>"
                    data-search="<?= e($coupon['title'] . ' ' . $coupon['merchant_name'] . ' ' . $coupon['code']) ?>">
                    <td>
                        <strong><?= e($coupon['title']) ?></strong>
                        <p><?= e($coupon['merchant_name']) ?> · <?= e(saving($coupon)) ?> · <?= e($coupon['category_name']) ?></p>
                        <?php if ($coupon['moderation_status'] === 'REJECTED'): ?>
                        <p class="rejection-note"><strong>Phản hồi:</strong> <?= e($coupon['review_note'] ?: 'Admin chưa ghi lý do.') ?></p>
                        <?php endif; ?>
                    </td>
                    <td><code><?= e($coupon['code']) ?></code></td>
                    <td>
                        <strong class="table-date"><?= e(day($coupon['ends_at'])) ?></strong>
                        <small><?= e(expiryLabel($coupon['ends_at'])) ?></small>
                    </td>
                    <td>
                        <span class="badge-soft <?= match ($coupon['moderation_status']) { 'PENDING'=>'badge-pending', 'REJECTED'=>'badge-rejected', default=>'' } ?>">
                            <?= e(statusLabel($coupon['moderation_status'])) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$coupons): ?>
                <tr><td colspan="4">Chưa có mã. Tạo ưu đãi đầu tiên bằng form phía trên.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <p class="inline-empty" id="shop-filter-empty" hidden>Không có mã nào khớp với bộ lọc hiện tại.</p>
</section>

<?php require __DIR__ . '/layouts/workspace-end.php'; ?>

<?php
$pageTitle = 'Đóng góp mã giảm giá - CouponHub';
$activePage = 'submit';

require_once __DIR__ . '/data/coupons.php';
$categories = couponCategories();

require_once __DIR__ . '/layouts/header.php';
?>

<main class="category-page">
    <section class="category-heading">
        <div class="heading-text-group">
            <span class="category-eyebrow">COMMUNITY / CHIA SẺ</span>
            <h1>Đóng góp mã giảm giá</h1>
            <p>Chia sẻ coupon bạn biết để giúp cộng đồng tiết kiệm chi tiêu mua sắm!</p>
        </div>
    </section>

    <!-- Thẻ thông báo gửi thành công -->
    <div class="alert alert-success" id="submitSuccessAlert" style="display: none; margin-bottom: 20px;">
        ✓ Gửi mã giảm giá thành công! Mã của bạn đã được lưu vào mục <strong>Mã của tôi</strong> và đang ở trạng thái <strong>Chờ duyệt</strong>.
    </div>

    <div class="form-container">
        <form id="submitCouponForm" class="coupon-form">
            <div class="mb-3">
                <label for="store">Tên cửa hàng / Thương hiệu (*)</label>
                <input type="text" id="store" name="store" placeholder="Ví dụ: Shopee, Lazada, Highlands Coffee..." required>
            </div>

            <div class="row g-3">
                <div class="mb-3">
                    <label for="category">Danh mục (*)</label>
                    <select id="category" name="category" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="code">Mã coupon (*)</label>
                    <input type="text" id="code" name="code" placeholder="Ví dụ: SALE50K" required style="text-transform: uppercase;">
                </div>
            </div>

            <div class="row g-3">
                <div class="mb-3">
                    <label for="discount">Mức giảm (*)</label>
                    <input type="text" id="discount" name="discount" placeholder="Ví dụ: GIẢM 50K hoặc GIẢM 20%" required>
                </div>

                <div class="mb-3">
                    <label for="expiry">Hạn sử dụng (*)</label>
                    <input type="date" id="expiry" name="expiry" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="source_url">Liên kết nguồn / Trang áp dụng mã</label>
                <input type="url" id="source_url" name="source_url" placeholder="https://shopee.vn/...">
            </div>

            <div class="mb-3">
                <label for="description">Mô tả & Điều kiện áp dụng</label>
                <textarea id="description" name="description" rows="4" placeholder="Nhập chi tiết điều kiện sử dụng mã..."></textarea>
            </div>

            <button type="submit" class="detail-primary-button">Gửi mã để Admin duyệt</button>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
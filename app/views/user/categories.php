<?php
$pageTitle = 'Danh mục ưu đãi - CouponHub';
$activePage = 'categories';

require_once __DIR__ . '/data/coupons.php';

$categories = couponCategories();
$coupons = publicCoupons();

$selectedCategory = trim($_GET['category'] ?? '');
$selectedStore = trim($_GET['store'] ?? '');
$selectedStatus = trim($_GET['status'] ?? '');
$sort = $_GET['sort'] ?? 'expiry_soon';

if (!in_array($selectedCategory, $categories, true)) {
    $selectedCategory = '';
}

$stores = array_values(array_unique(
    array_column($coupons, 'store')
));

$filteredCoupons = array_filter(
    $coupons,
    function ($coupon) use (
        $selectedCategory,
        $selectedStore,
        $selectedStatus
    ) {
        if (
            $selectedCategory !== '' &&
            $coupon['category'] !== $selectedCategory
        ) {
            return false;
        }

        if (
            $selectedStore !== '' &&
            $coupon['store'] !== $selectedStore
        ) {
            return false;
        }

        if (
            $selectedStatus !== '' &&
            $coupon['status'] !== $selectedStatus
        ) {
            return false;
        }

        return true;
    }
);

uasort($filteredCoupons, function ($a, $b) use ($sort) {
    if ($sort === 'expiry_late') {
        return strtotime($b['expiry']) <=> strtotime($a['expiry']);
    }

    return strtotime($a['expiry']) <=> strtotime($b['expiry']);
});

require_once __DIR__ . '/layouts/header.php';
?>

<main class="category-page">

    <!-- BANNER TRANG DANH MỤC -->
    <section class="category-heading">
        <div class="heading-text-group">
            <span class="category-eyebrow">
                COUPONHUB / KHÁM PHÁ
            </span>

            <h1>Danh mục ưu đãi</h1>

            <p>
                Lọc và khám phá các mã giảm giá theo từng lĩnh vực mua sắm.
            </p>
        </div>

        <div class="category-heading-stats">
            <div class="stat-pill">
                <span class="stat-number"><?= count($categories) ?></span>
                <span class="stat-label">Danh mục</span>
            </div>

            <div class="stat-divider"></div>

            <div class="stat-pill">
                <span class="stat-number"><?= count($coupons) ?>+</span>
                <span class="stat-label">Mã giảm giá</span>
            </div>
        </div>
    </section>

    <!-- HÀNG NÚT LỌC NHANH (CHIPS) -->
    <section class="category-browser" style="margin-bottom: 24px;">
        <div class="category-chip-list">
            <a href="/app/views/user/categories.php"
                class="category-chip <?= $selectedCategory === '' ? 'active' : '' ?>">
                Tất cả
            </a>
            <?php foreach ($categories as $category): ?>
                <a href="/app/views/user/categories.php?category=<?= urlencode($category) ?>"
                    class="category-chip <?= $selectedCategory === $category ? 'active' : '' ?>">
                    <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- KẾT QUẢ CÙNG DÒNG THỐNG KÊ SỐ LƯỢNG ƯU ĐÃI TỐI GIẢN -->
    <section class="category-results">

        <!-- HIỂN THỊ DUY NHẤT DÒNG SỐ LƯỢNG MÃ -->
        <div style="margin-bottom: 16px; font-size: 14px; font-weight: 600; color: var(--muted);">
            <?= count($filteredCoupons) ?> ưu đãi phù hợp
        </div>

        <div class="category-results-layout">

            <aside class="category-filter">
                <h3>Bộ lọc</h3>

                <form method="GET" action="/app/views/user/categories.php">
                    <div class="category-filter-group">
                        <label for="category">Danh mục</label>

                        <select name="category" id="category">
                            <option value="">Tất cả danh mục</option>

                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $selectedCategory === $category ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="category-filter-group">
                        <label for="store">Cửa hàng</label>

                        <select name="store" id="store">
                            <option value="">Tất cả cửa hàng</option>

                            <?php foreach ($stores as $store): ?>
                                <option value="<?= htmlspecialchars($store, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $selectedStore === $store ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($store, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="category-filter-group">
                        <label for="status">Trạng thái</label>

                        <select name="status" id="status">
                            <option value="">Tất cả trạng thái</option>

                            <?php foreach (['Còn hạn', 'Sắp hết hạn', 'Hết hạn'] as $status): ?>
                                <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $selectedStatus === $status ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="category-filter-group">
                        <label for="sort">Sắp xếp</label>

                        <select name="sort" id="sort">
                            <option value="expiry_soon" <?= $sort === 'expiry_soon' ? 'selected' : '' ?>>
                                Sắp hết hạn trước
                            </option>

                            <option value="expiry_late" <?= $sort === 'expiry_late' ? 'selected' : '' ?>>
                                Hạn sử dụng xa nhất
                            </option>
                        </select>
                    </div>

                    <button type="submit" class="category-filter-button">
                        Áp dụng bộ lọc
                    </button>

                    <a href="/app/views/user/categories.php" class="category-clear-button">
                        Xóa bộ lọc
                    </a>
                </form>
            </aside>

            <div class="category-coupon-list">

                <?php if (empty($filteredCoupons)): ?>

                    <div class="category-empty">
                        <h3>Chưa tìm thấy ưu đãi phù hợp</h3>
                        <p>Thử danh mục hoặc bộ lọc khác.</p>
                        <a href="/app/views/user/categories.php">
                            Xem tất cả ưu đãi
                        </a>
                    </div>

                <?php else: ?>

                    <?php foreach ($filteredCoupons as $coupon): ?>
                        <article class="category-coupon-card">

                            <div class="category-coupon-top">
                                <span class="category-store-name">
                                    <?= htmlspecialchars($coupon['store'], ENT_QUOTES, 'UTF-8') ?>
                                </span>

                                <span class="category-status">
                                    <?= htmlspecialchars($coupon['status'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>

                            <span class="category-coupon-discount">
                                <?= htmlspecialchars($coupon['discount'], ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <h3>
                                <?= htmlspecialchars($coupon['title'], ENT_QUOTES, 'UTF-8') ?>
                            </h3>

                            <p class="category-coupon-type">
                                <?= htmlspecialchars($coupon['category'], ENT_QUOTES, 'UTF-8') ?>
                            </p>

                            <div class="category-coupon-bottom">
                                <span>
                                    Hạn dùng:
                                    <?= date('d/m/Y', strtotime($coupon['expiry'])) ?>
                                </span>

                                <a href="/app/views/user/coupon_detail.php?code=<?= urlencode($coupon['code']) ?>"
                                    class="category-view-button">
                                    Xem ưu đãi
                                </a>
                            </div>

                        </article>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>
        </div>
    </section>

</main>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
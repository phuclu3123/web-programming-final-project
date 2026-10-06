<?php
require __DIR__ . '/layouts/head.php';

$selectedCategoryName = null;
foreach ($categories as $category) {
    if ((int) $category['id'] === $categoryId) {
        $selectedCategoryName = $category['name'];
        break;
    }
}

$selectedMerchantName = null;
foreach ($merchants as $merchant) {
    if ((int) $merchant['id'] === $merchantId) {
        $selectedMerchantName = $merchant['name'];
        break;
    }
}

$discountTypeLabels = [
    '' => 'Tất cả loại ưu đãi',
    'PERCENTAGE' => 'Giảm theo phần trăm',
    'FIXED_AMOUNT' => 'Giảm số tiền cố định',
    'FREE_SHIPPING' => 'Miễn phí vận chuyển',
    'BUY_X_GET_Y' => 'Mua X tặng Y',
    'GIFT' => 'Quà tặng',
    'CASHBACK' => 'Hoàn tiền',
];
$usageChannelLabels = [
    '' => 'Tất cả kênh sử dụng',
    'ONLINE' => 'Dùng online',
    'OFFLINE' => 'Tại cửa hàng',
    'BOTH' => 'Cả online và cửa hàng',
];

$estimableCoupons = array_values(array_filter(
    $coupons,
    static fn (array $coupon): bool => in_array($coupon['discount_type'], ['PERCENTAGE', 'FIXED_AMOUNT'], true)
));
$featuredCoupon = $estimableCoupons[0] ?? null;
$featuredCoupon ??= $coupons[0] ?? null;
$canEstimate = count($estimableCoupons) > 0;
$exampleOrder = $canEstimate ? max(250000, (float) $featuredCoupon['min_order_amount']) : 0;
$exampleSaving = 0;
if ($canEstimate) {
    $exampleSaving = $featuredCoupon['discount_type'] === 'PERCENTAGE'
        ? $exampleOrder * (float) $featuredCoupon['discount_value'] / 100
        : min($exampleOrder, (float) $featuredCoupon['discount_value']);
    if ($featuredCoupon['max_discount_amount'] !== null) {
        $exampleSaving = min($exampleSaving, (float) $featuredCoupon['max_discount_amount']);
    }
}

$urlFor = static function (array $overrides = [], string $anchor = 'coupons') use (
    $q,
    $categoryId,
    $merchantId,
    $discountType,
    $usageChannel,
    $endingSoon,
    $sort
): string {
    $params = array_merge([
        'q' => $q,
        'category' => $categoryId,
        'merchant' => $merchantId,
        'type' => $discountType,
        'channel' => $usageChannel,
        'ending' => $endingSoon ? 1 : 0,
        'sort' => $sort,
    ], $overrides);

    $params = array_filter($params, static fn ($value, $key): bool => match ($key) {
        'q' => $value !== '',
        'category', 'merchant' => (int) $value > 0,
        'type', 'channel' => $value !== '',
        'ending' => (int) $value === 1,
        default => true,
    }, ARRAY_FILTER_USE_BOTH);

    $query = http_build_query($params);
    return 'index.php' . ($query !== '' ? '?' . $query : '') . ($anchor !== '' ? '#' . $anchor : '');
};
?>

<header class="site-header">
    <div class="container-wide header-inner">
        <a class="brand" href="index.php" aria-label="CouponHub - Trang chủ">
            <span class="brand-mark"><span>C</span></span><span class="brand-word">Coupon<em>Hub</em><span class="brand-period">.</span></span>
        </a>
        <nav class="site-nav" aria-label="Điều hướng chính">
            <a class="active" href="#coupons">Khám phá</a>
            <a href="#categories">Danh mục</a>
            <?php if ($canEstimate): ?><a href="#estimate" data-estimate-link>Ước tính</a><?php endif; ?>
            <a href="#stores">Cửa hàng</a>
        </nav>
        <div class="header-account">
            <a class="btn btn-outline-brand" href="<?= $canEstimate ? '#estimate' : '#coupons' ?>" data-estimate-action><span><?= $canEstimate ? 'Thử tính ưu đãi' : 'Xem ưu đãi' ?></span><?= icon('arrow') ?></a>
        </div>
    </div>
</header>

<main id="main" class="container-wide">
    <section class="hero hero-compact" aria-labelledby="hero-title">
        <div class="hero-box">
            <div class="hero-copy">
                <div class="eyebrow"><span class="dot"></span>ƯU ĐÃI ĐÃ ĐƯỢC KIỂM CHỨNG</div>
                <h1 id="hero-title">Giá tốt hơn.<br><span>Không cần tìm lâu.</span></h1>
                <p>CouponHub tập hợp mã còn hiệu lực, giải thích điều kiện rõ ràng và giúp bạn ước tính khoản tiết kiệm trước khi thanh toán.</p>

                <form id="home-filter-form" class="hero-search" role="search" method="get" action="index.php#coupons" autocomplete="off">
                    <?= icon('search') ?>
                    <label class="visually-hidden" for="q">Tìm ưu đãi</label>
                    <input type="search" id="q" class="form-control" name="q" maxlength="100"
                        value="<?= e($q) ?>" placeholder="Tìm cửa hàng, mã hoặc ưu đãi…"
                        aria-describedby="search-help">
                    <input type="hidden" name="category" value="<?= $categoryId ?>">
                    <input type="hidden" name="merchant" value="<?= $merchantId ?>">
                    <input type="hidden" name="type" value="<?= e($discountType) ?>">
                    <input type="hidden" name="channel" value="<?= e($usageChannel) ?>">
                    <input type="hidden" name="ending" value="<?= $endingSoon ? '1' : '0' ?>">
                    <input type="hidden" name="sort" value="<?= e($sort) ?>">
                    <a class="search-clear" href="<?= e($urlFor(['q' => ''])) ?>" data-home-filter aria-label="Xóa từ khóa" <?= $q === '' ? 'hidden' : '' ?>>×</a>
                    <button type="submit" class="btn btn-lime search-submit">Khám phá <?= icon('arrow') ?></button>
                </form>
                <div id="search-help" class="hero-tip">Tìm theo cửa hàng, tên ưu đãi hoặc mã giảm giá.</div>

                <div class="hero-proof" aria-label="Điểm nổi bật">
                    <span><?= icon('check') ?> Mã đã duyệt</span>
                    <span><?= icon('clock') ?> Còn hiệu lực</span>
                    <span><?= icon('grid') ?> Điều kiện rõ ràng</span>
                </div>
            </div>

            <div class="hero-art">
                <div class="hero-stage">
                <?php if ($featuredCoupon): ?>
                <article class="hero-feature" aria-label="Ưu đãi nổi bật">
                    <div class="hero-feature-top"><span>ƯU ĐÃI ĐƯỢC CHỌN</span><span><?= e($featuredCoupon['category_name']) ?></span></div>
                    <div class="hero-feature-main">
                        <span class="hero-feature-merchant"><?= e($featuredCoupon['merchant_name']) ?></span>
                        <div class="hero-feature-saving"><?= e(saving($featuredCoupon)) ?></div>
                        <h2><?= e($featuredCoupon['title']) ?></h2>
                        <div class="hero-feature-facts">
                            <div><span>Đơn tối thiểu</span><strong><?= (float) $featuredCoupon['min_order_amount'] > 0 ? e(money($featuredCoupon['min_order_amount'])) : 'Không yêu cầu' ?></strong></div>
                            <div><span>Kênh áp dụng</span><strong><?= e(channelLabel($featuredCoupon['usage_channel'])) ?></strong></div>
                        </div>
                    </div>
                    <div class="hero-feature-bottom">
                        <span><?= e(expiryLabel($featuredCoupon['ends_at'])) ?></span>
                        <button type="button" class="hero-feature-copy" data-copy-code="<?= e($featuredCoupon['code']) ?>">
                            <?= icon('copy') ?> <span>Sao chép mã</span>
                        </button>
                    </div>
                </article>
                <?php else: ?>
                <div class="hero-feature hero-feature-empty" aria-hidden="true">
                    <span>COUPONHUB</span><strong>%</strong><span>Ít thao tác hơn. Nhiều giá trị hơn.</span>
                </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div id="discovery-results"
        data-category="<?= $categoryId ?>"
        data-merchant="<?= $merchantId ?>"
        data-discount-type="<?= e($discountType) ?>"
        data-usage-channel="<?= e($usageChannel) ?>"
        data-ending-soon="<?= $endingSoon ? '1' : '0' ?>"
        data-sort="<?= e($sort) ?>">

        <section class="market-snapshot" aria-label="Tổng quan CouponHub">
            <div class="snapshot-card snapshot-live"><small>KẾT QUẢ</small><strong><?= count($coupons) ?></strong><span>ưu đãi đang hiển thị</span></div>
            <div class="snapshot-card snapshot-partners"><small>ĐỐI TÁC</small><strong><?= count($merchants) ?></strong><span>cửa hàng đã duyệt</span></div>
            <div class="snapshot-card snapshot-needs"><small>PHẠM VI</small><strong><?= count($categories) ?></strong><span>nhóm nhu cầu</span></div>
            <aside class="snapshot-note"><span><?= icon('check') ?></span><div><strong>Dữ liệu có kiểm chứng</strong><p>Cập nhật theo bộ lọc và chỉ bao gồm mã còn hiệu lực.</p></div></aside>
        </section>

        <section id="categories" class="discovery-section" aria-labelledby="discovery-title">
            <div class="section-heading discovery-heading">
                <div>
                    <div class="eyebrow muted mb-2">KHÁM PHÁ ƯU ĐÃI</div>
                    <h2 id="discovery-title">Ưu đãi dành cho bạn</h2>
                    <p>Chọn nhu cầu hoặc tìm trực tiếp để xem những mã phù hợp đang còn hiệu lực.</p>
                </div>
                <?php if ($q !== '' || $categoryId > 0 || $merchantId > 0 || $discountType !== '' || $usageChannel !== '' || $endingSoon): ?>
                <a class="text-link" href="index.php#coupons" data-home-filter>Xóa tất cả bộ lọc <?= icon('arrow') ?></a>
                <?php endif; ?>
            </div>

            <nav class="category-strip" aria-label="Danh mục ưu đãi">
                <a class="category-chip <?= $categoryId === 0 && $q === '' && $merchantId === 0 && $discountType === '' && $usageChannel === '' && !$endingSoon ? 'selected' : '' ?>"
                    href="index.php#coupons" data-home-filter
                    <?= $categoryId === 0 && $q === '' && $merchantId === 0 && $discountType === '' && $usageChannel === '' && !$endingSoon ? 'aria-current="true"' : '' ?>>
                    <?= icon('grid') ?> Tất cả ưu đãi
                </a>
                <?php foreach ($categories as $category): ?>
                <a class="category-chip <?= (int) $category['id'] === $categoryId ? 'selected' : '' ?>"
                    href="<?= e($urlFor(['category' => (int) $category['id']])) ?>" data-home-filter
                    <?= (int) $category['id'] === $categoryId ? 'aria-current="true"' : '' ?>>
                    <?= e($category['name']) ?>
                </a>
                <?php endforeach; ?>
            </nav>

            <form class="catalog-filters" method="get" action="index.php#coupons" data-catalog-filter-form>
                <input type="hidden" name="q" value="<?= e($q) ?>">
                <input type="hidden" name="category" value="<?= $categoryId ?>">
                <input type="hidden" name="merchant" value="<?= $merchantId ?>">
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
                <div class="catalog-filter-field">
                    <label for="discount-type-filter">Loại ưu đãi</label>
                    <select id="discount-type-filter" class="form-select" name="type">
                        <?php foreach ($discountTypeLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $discountType === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="catalog-filter-field">
                    <label for="usage-channel-filter">Kênh sử dụng</label>
                    <select id="usage-channel-filter" class="form-select" name="channel">
                        <?php foreach ($usageChannelLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $usageChannel === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label class="ending-filter" for="ending-soon-filter">
                    <input id="ending-soon-filter" type="checkbox" name="ending" value="1" <?= $endingSoon ? 'checked' : '' ?>>
                    <span><strong>Sắp hết hạn</strong><small>Trong 7 ngày tới</small></span>
                </label>
                <button class="btn btn-outline-brand" type="submit">Áp dụng bộ lọc</button>
            </form>

            <?php if ($q !== '' || $categoryId > 0 || $merchantId > 0 || $discountType !== '' || $usageChannel !== '' || $endingSoon): ?>
            <div class="active-filters" aria-label="Bộ lọc đang áp dụng">
                <span class="active-filters-label">Đang lọc:</span>
                <?php if ($q !== ''): ?>
                <a class="filter-pill" href="<?= e($urlFor(['q' => ''])) ?>" data-home-filter>
                    “<?= e($q) ?>” <span aria-hidden="true">×</span>
                </a>
                <?php endif; ?>
                <?php if ($selectedCategoryName): ?>
                <a class="filter-pill" href="<?= e($urlFor(['category' => 0])) ?>" data-home-filter>
                    <?= e($selectedCategoryName) ?> <span aria-hidden="true">×</span>
                </a>
                <?php endif; ?>
                <?php if ($selectedMerchantName): ?>
                <a class="filter-pill" href="<?= e($urlFor(['merchant' => 0])) ?>" data-home-filter>
                    <?= e($selectedMerchantName) ?> <span aria-hidden="true">×</span>
                </a>
                <?php endif; ?>
                <?php if ($discountType !== ''): ?>
                <a class="filter-pill" href="<?= e($urlFor(['type' => ''])) ?>" data-home-filter>
                    <?= e($discountTypeLabels[$discountType]) ?> <span aria-hidden="true">×</span>
                </a>
                <?php endif; ?>
                <?php if ($usageChannel !== ''): ?>
                <a class="filter-pill" href="<?= e($urlFor(['channel' => ''])) ?>" data-home-filter>
                    <?= e($usageChannelLabels[$usageChannel]) ?> <span aria-hidden="true">×</span>
                </a>
                <?php endif; ?>
                <?php if ($endingSoon): ?>
                <a class="filter-pill" href="<?= e($urlFor(['ending' => 0])) ?>" data-home-filter>
                    Sắp hết hạn <span aria-hidden="true">×</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div id="coupons" class="coupon-results">
            <div class="results-toolbar">
                <div class="results-summary" role="status" aria-live="polite">
                    <strong><?= count($coupons) ?></strong> ưu đãi
                    <?php if ($q !== ''): ?> cho “<?= e($q) ?>”<?php endif; ?>
                    <?php if ($selectedMerchantName): ?> · <?= e($selectedMerchantName) ?><?php endif; ?>
                    <span class="result-limit">· tối đa 60 kết quả</span>
                </div>

                <form id="sort-form" method="get" action="index.php#coupons" class="sort-form">
                    <input type="hidden" name="q" value="<?= e($q) ?>">
                    <input type="hidden" name="category" value="<?= $categoryId ?>">
                    <input type="hidden" name="merchant" value="<?= $merchantId ?>">
                    <input type="hidden" name="type" value="<?= e($discountType) ?>">
                    <input type="hidden" name="channel" value="<?= e($usageChannel) ?>">
                    <input type="hidden" name="ending" value="<?= $endingSoon ? '1' : '0' ?>">
                    <label for="sort-select">Sắp xếp</label>
                    <select id="sort-select" class="form-select" name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="expiry" <?= $sort === 'expiry' ? 'selected' : '' ?>>Sắp hết hạn</option>
                    </select>
                    <button class="btn btn-outline-brand btn-sm sort-submit" type="submit">Áp dụng</button>
                </form>
            </div>

            <div class="coupon-grid" id="coupon-grid" aria-busy="false">
                <?php foreach ($coupons as $couponIndex => $coupon) require __DIR__ . '/partials/coupon-card.php'; ?>
            </div>

            <?php if (!$coupons): ?>
            <div class="empty-state">
                <div class="empty-symbol">⌕</div>
                <h3>Chưa có ưu đãi phù hợp</h3>
                <p>Thử từ khóa khác, đổi danh mục/cửa hàng hoặc xóa toàn bộ bộ lọc.</p>
                <a class="btn btn-outline-brand" href="index.php#coupons" data-home-filter>Xem tất cả ưu đãi</a>
            </div>
            <?php endif; ?>
            </div>
        </section>

        <?php if ($canEstimate): ?>
        <section id="estimate" class="savings-lab" aria-labelledby="estimate-title"
            data-discount-type="<?= e($featuredCoupon['discount_type']) ?>"
            data-discount-value="<?= e($featuredCoupon['discount_value']) ?>"
            data-min-order="<?= e($featuredCoupon['min_order_amount']) ?>"
            data-max-discount="<?= e($featuredCoupon['max_discount_amount'] ?? '') ?>">
            <div class="savings-lab-copy">
                <div class="eyebrow">CÔNG CỤ ƯỚC TÍNH</div>
                <h2 id="estimate-title">Mã nào lời hơn cho đơn của bạn?</h2>
                <p>Chọn một ưu đãi, nhập giá trị đơn hàng và xem ngay số tiền dự kiến giữ lại. Công cụ tự xét đơn tối thiểu, mức giảm và trần giảm của từng mã.</p>
                <div class="estimate-proof"><span>01</span><p>Chọn mã</p><span>02</span><p>Nhập giá trị đơn</p><span>03</span><p>Xem số tiền tiết kiệm</p></div>
                <span class="savings-lab-note">Kết quả là ước tính. Điều kiện chi tiết và giá trị cuối cùng do cửa hàng xác nhận khi thanh toán.</span>
            </div>
            <div class="savings-sheet">
                <div class="savings-sheet-head"><span>ƯỚC TÍNH ƯU ĐÃI</span><strong id="estimate-code"><?= e($featuredCoupon['code']) ?></strong></div>
                <label for="estimate-coupon">Ưu đãi muốn thử</label>
                <div class="estimate-select-wrap">
                    <select id="estimate-coupon" class="form-select">
                        <?php foreach ($estimableCoupons as $estimableCoupon): ?>
                        <option value="<?= e($estimableCoupon['code']) ?>"
                            data-code="<?= e($estimableCoupon['code']) ?>"
                            data-merchant="<?= e($estimableCoupon['merchant_name']) ?>"
                            data-discount-type="<?= e($estimableCoupon['discount_type']) ?>"
                            data-discount-value="<?= e($estimableCoupon['discount_value']) ?>"
                            data-min-order="<?= e($estimableCoupon['min_order_amount']) ?>"
                            data-max-discount="<?= e($estimableCoupon['max_discount_amount'] ?? '') ?>"
                            data-saving-label="<?= e(saving($estimableCoupon)) ?>">
                            <?= e($estimableCoupon['merchant_name']) ?> · <?= e(saving($estimableCoupon)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <label for="order-value">Thử với đơn hàng</label>
                <div class="savings-input"><input id="order-value" type="number" min="0" max="1000000000" step="1000" inputmode="numeric" value="<?= (int) $exampleOrder ?>"><span>đ</span></div>
                <div class="savings-line"><span>Giá trị mã</span><strong id="estimate-saving-value"><?= e(saving($featuredCoupon)) ?></strong></div>
                <div class="savings-line"><span>Đơn tối thiểu</span><strong id="estimate-minimum"><?= e(money($featuredCoupon['min_order_amount'])) ?></strong></div>
                <div class="savings-line" id="estimate-maximum-row" <?= $featuredCoupon['max_discount_amount'] === null ? 'hidden' : '' ?>><span>Trần giảm</span><strong id="estimate-maximum"><?= e(money($featuredCoupon['max_discount_amount'] ?? 0)) ?></strong></div>
                <div class="savings-total"><span>Tiết kiệm ước tính</span><output id="estimated-saving" for="order-value" aria-live="polite"><?= e(money($exampleSaving)) ?></output></div>
                <p id="estimate-note">Ước tính theo mức giảm và giới hạn của mã.</p>
                <button type="button" class="btn btn-brand" id="estimate-copy-code" data-copy-code="<?= e($featuredCoupon['code']) ?>"><?= icon('copy') ?> <span>Sao chép mã này</span></button>
            </div>
        </section>
        <?php endif; ?>

        <section id="stores" class="stores-section" aria-labelledby="stores-title">
            <div class="stores-intro">
                <div class="eyebrow">HỆ SINH THÁI ĐỐI TÁC</div>
                <h2 id="stores-title">Bắt đầu từ nơi bạn tin chọn.</h2>
                <p>Mỗi cửa hàng là một lối vào riêng đến các ưu đãi đã được kiểm tra, trình bày rõ điều kiện và còn hiệu lực.</p>
                <?php if ($merchantId > 0): ?>
                <a class="stores-reset" href="<?= e($urlFor(['merchant' => 0])) ?>" data-home-filter>Tất cả cửa hàng <?= icon('arrow') ?></a>
                <?php endif; ?>
            </div>

            <div class="store-gallery">
                <?php foreach ($merchants as $storeIndex => $merchant): ?>
                <a class="store-showcase <?= (int) $merchant['id'] === $merchantId ? 'selected' : '' ?>"
                    href="<?= e($urlFor(['merchant' => (int) $merchant['id']])) ?>" data-home-filter
                    <?= (int) $merchant['id'] === $merchantId ? 'aria-current="true"' : '' ?>>
                    <span class="store-index"><?= str_pad((string) ($storeIndex + 1), 2, '0', STR_PAD_LEFT) ?></span>
                    <span class="store-logo"><?= e(textPrefix($merchant['name'])) ?></span>
                    <span class="store-showcase-copy"><strong><?= e($merchant['name']) ?></strong><small>Xem ưu đãi đang có</small></span>
                    <span class="store-arrow"><?= icon('arrow') ?></span>
                </a>
                <?php endforeach; ?>
                <?php if (!$merchants): ?><p>Chưa có cửa hàng được duyệt.</p><?php endif; ?>
            </div>
        </section>
    </div>
</main>

<footer class="site-footer">
    <div class="container-wide">
        <div class="footer-main">
            <div class="footer-statement">
                <a class="brand" href="index.php"><span class="brand-mark"><span>C</span></span><span class="brand-word">Coupon<em>Hub</em><span class="brand-period">.</span></span></a>
                <div class="footer-kicker"><span aria-hidden="true"></span>Ưu đãi đã được kiểm chứng</div>
                <h2>Ít thời gian tìm mã.<br>Nhiều giá trị giữ lại.</h2>
                <p>Nền tảng khám phá ưu đãi rõ ràng cho người mua và không gian vận hành gọn gàng cho đối tác.</p>
            </div>
            <nav class="footer-nav" aria-label="Điều hướng cuối trang">
                <div><strong>Khám phá</strong><a href="#coupons">Ưu đãi mới</a><a href="#categories">Danh mục</a><a href="#stores">Cửa hàng</a></div>
                <div><strong>Công cụ</strong><?php if ($canEstimate): ?><a href="#estimate">Ước tính tiết kiệm</a><?php endif; ?><a href="shop.php">Không gian đối tác</a><a href="admin.php">Trung tâm quản trị</a></div>
                <div><strong>Cam kết</strong><span>Mã còn hiệu lực</span><span>Điều kiện rõ ràng</span><span>Dữ liệu đã duyệt</span></div>
            </nav>
        </div>
        <div class="footer-bottom"><span>© <?= date('Y') ?> CouponHub</span><span>Thiết kế cho trải nghiệm mua sắm sáng suốt hơn.</span><a href="#main">Về đầu trang ↑</a></div>
    </div>
</footer>

<div id="copy-toast" class="copy-toast" role="status" aria-live="polite" aria-atomic="true">
    <span class="toast-check" aria-hidden="true">✓</span>
    <span id="copy-toast-text">Đã sao chép mã</span>
</div>

</body>
</html>

<?php
$pageTitle = 'Tìm kiếm ưu đãi - CouponHub';
$activePage = 'search';

require_once __DIR__ . '/data/coupons.php';

$query = trim($_GET['q'] ?? '');
$results = [];

if ($query !== '') {
    foreach (publicCoupons() as $coupon) {
        if (couponMatches($coupon, $query)) {
            $results[] = $coupon;
        }
    }
}

require_once __DIR__ . '/layouts/header.php';
?>

<main class="category-page search-page">

    <section class="category-heading">
        <div>
            <span class="category-eyebrow">
                COUPONHUB / TÌM KIẾM
            </span>

            <h1>Tìm kiếm ưu đãi</h1>

            <p>
                Tìm theo cửa hàng, tên ưu đãi, danh mục hoặc mã coupon.
            </p>

            <form
                class="hero-search category-search"
                action="/app/views/user/search.php"
                method="GET"
            >
                <input
                    type="search"
                    name="q"
                    placeholder="Ví dụ: Shopee, ăn uống, TIKI50..."
                    value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"
                    aria-label="Từ khóa tìm kiếm"
                >

                <button type="submit">Tìm kiếm</button>
            </form>
        </div>
    </section>

    <section class="category-results">

        <div class="category-section-heading">
            <div>
                <h2>
                    <?php if ($query === ''): ?>
                        Nhập từ khóa để tìm ưu đãi
                    <?php else: ?>
                        Kết quả cho
                        “<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>”
                    <?php endif; ?>
                </h2>

                <?php if ($query !== ''): ?>
                    <p><?= count($results) ?> ưu đãi phù hợp</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($query === ''): ?>

            <div class="category-empty">
                <h3>Bạn đang tìm ưu đãi nào?</h3>
                <p>
                    Thử “Shopee”, “Mua sắm”, “Highlands” hoặc “COFFEE20”.
                </p>
            </div>

        <?php elseif (empty($results)): ?>

            <div class="category-empty">
                <h3>Không tìm thấy ưu đãi phù hợp</h3>
                <p>Kiểm tra chính tả hoặc thử từ khóa khác.</p>
                <a href="/app/views/user/search.php">Xóa từ khóa</a>
            </div>

        <?php else: ?>

            <div class="category-coupon-list">

                <?php foreach ($results as $coupon): ?>
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
                            · Mã:
                            <strong>
                                <?= htmlspecialchars($coupon['code'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </p>

                        <div class="category-coupon-bottom">
                            <span>
                                Hạn dùng:
                                <?= date('d/m/Y', strtotime($coupon['expiry'])) ?>
                            </span>

                            <a
                                href="/app/views/user/coupon_detail.php?code=<?= urlencode($coupon['code']) ?>"
                                class="category-view-button"
                            >
                                Xem ưu đãi
                            </a>
                        </div>

                    </article>
                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>
</main>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>

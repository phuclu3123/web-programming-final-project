<?php
$pageTitle = 'CouponHub - Săn mã giảm giá';
$activePage = 'home';

require_once __DIR__ . '/data/coupons.php';

$topCategories = featuredCategories();
$coupons = publicCoupons();
$storeLogos = storeLogos();

require_once __DIR__ . '/layouts/header.php';
?>

<!-- HERO BANNER -->
<section class="hero">
    <div class="hero-content">
        <span class="hero-label">COUPONHUB · MUA SẮM THÔNG MINH</span>

        <h1>Săn mã giảm giá.<br><span>Tiết kiệm mỗi ngày.</span></h1>

        <p>Khám phá các ưu đãi và mã giảm giá từ các cửa hàng bạn yêu thích.</p>

        <div class="hero-cta" style="margin-top: 24px;">
            <a href="/app/views/user/categories.php" class="detail-primary-button" style="padding: 12px 24px; font-size: 14px; border-radius: 10px;">
                Khám phá ưu đãi ngay ↗
            </a>
        </div>

        <div class="hero-note" style="margin-top: 24px;">
            <span>✓</span> Tìm kiếm dễ dàng
            <span>✓</span> Đa dạng danh mục
            <span>✓</span> Tiết kiệm chi tiêu
        </div>
    </div>

    <div class="hero-decoration">
        <div class="hero-ticket">
            <span class="ticket-small">ƯU ĐÃI ĐẶC BIỆT</span>
            <strong>30%</strong>
            <span>OFF</span>
            <div class="ticket-line"></div>
            <span class="ticket-bottom">SAVE MORE TODAY</span>
        </div>
        <!-- VỊ TRÍ 1 TRONG 2 ĐIỂM NHẤN HOT TRÊN TOÀN WEB -->
        <div class="floating-tag">🔥 Mã Hot hôm nay</div>
    </div>
</section>

<!-- TOP 3 DANH MỤC NỔI BẬT (ĐÃ SỬA CỤM TIÊU ĐỀ LIỀN MẠCH, KHÔNG BỊ RỜI RẠC) -->
<section class="featured-categories" id="categories">
    <div class="featured-heading">
        <div class="heading-text-group">
            <span class="featured-eyebrow">FIND YOUR NEXT DEAL</span>
            <h2>Danh mục nổi bật</h2>
            <p>Khám phá ưu đãi dành riêng cho những điều bạn yêu thích.</p>
        </div>

        <a href="/app/views/user/categories.php" class="featured-view-all">
            Xem tất cả <span>↗</span>
        </a>
    </div>

    <div class="category-top3-grid">
        <?php foreach ($topCategories as $index => $cat): ?>
            <a 
                href="/app/views/user/categories.php?category=<?= urlencode($cat['name']) ?>" 
                class="top3-card <?= $index === 0 ? 'top3-featured-main' : 'top3-card-sub' ?>"
            >
                <img class="top3-image" src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" loading="lazy">
                <div class="top3-overlay"></div>

                <span class="top3-number"><?= htmlspecialchars($cat['number']) ?></span>

                <div class="top3-content">
                    <span class="top3-kicker">CATEGORY / <?= htmlspecialchars($cat['number']) ?></span>
                    <h3><?= htmlspecialchars($cat['name']) ?></h3>
                    <p><?= htmlspecialchars($cat['subtitle']) ?></p>
                    <span class="top3-link">Khám phá ngay ↗</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- MÃ GIẢM GIÁ NỔI BẬT -->
<section class="coupon-section" id="coupons">
    <div class="section-heading">
        <div class="heading-text-group">
            <span class="section-label">RECOMMENDED FOR YOU</span>
            <h2>Mã giảm giá nổi bật</h2>
            <p>Tham khảo các ưu đãi tốt nhất được đề xuất cho bạn.</p>
        </div>
    </div>

    <div class="coupon-grid">
        <?php foreach ($coupons as $coupon): ?>
            <article 
                class="coupon-card" 
                onclick="window.location.href='/app/views/user/coupon_detail.php?code=<?= urlencode($coupon['code']) ?>'" 
                style="cursor: pointer;"
                data-coupon-code="<?= htmlspecialchars($coupon['code']) ?>"
            >
                <div class="coupon-card-top">
                    <div class="store-avatar">
                        <?php if (isset($storeLogos[$coupon['store']])): ?>
                            <img src="<?= htmlspecialchars($storeLogos[$coupon['store']]) ?>" alt="<?= htmlspecialchars($coupon['store']) ?>">
                        <?php else: ?>
                            <span class="highlands-logo">H</span>
                        <?php endif; ?>
                    </div>

                    <div class="store-info">
                        <h3><?= htmlspecialchars($coupon['store']) ?></h3>
                        <span><?= htmlspecialchars($coupon['category']) ?></span>
                    </div>

                    <!-- Thẻ Coupon trên Trang chủ dùng SVG Vector Monochrome -->
<button
    type="button"
    class="favorite-toggle-btn"
    data-favorite-code="<?= htmlspecialchars($coupon['code']) ?>"
>
    <!-- Icon trái tim vector nét mảnh (Monochrome SVG) -->
    <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.72-8.72 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
    </svg>
    <span class="fav-text">Yêu thích</span>
</button>
                </div>

                <div class="coupon-discount"><?= htmlspecialchars($coupon['discount']) ?></div>
                <h4><?= htmlspecialchars($coupon['title']) ?></h4>
                <p class="coupon-description"><?= htmlspecialchars($coupon['description']) ?></p>

                <div class="coupon-card-bottom">
                    <div class="coupon-code">
                        <span>MÃ:</span>
                        <strong><?= htmlspecialchars($coupon['code']) ?></strong>
                    </div>

                    <button 
                        type="button" 
                        class="copy-button" 
                        onclick="event.stopPropagation(); copyCoupon(this, '<?= htmlspecialchars($coupon['code']) ?>')"
                    >
                        Sao chép
                    </button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>

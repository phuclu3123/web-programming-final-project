<?php
$pageTitle = 'Chi tiết ưu đãi - CouponHub';
$activePage = 'home';

require_once __DIR__ . '/data/coupons.php';

$logos = storeLogos();
$code = strtoupper(trim($_GET['code'] ?? ''));
$coupon = findCoupon($code);

require_once __DIR__ . '/layouts/header.php';
?>

<main class="coupon-detail-page">

    <?php if ($coupon === null): ?>

    <section class="detail-not-found">
        <span class="detail-eyebrow">COUPONHUB</span>
        <h1>Không tìm thấy ưu đãi</h1>
        <p>Mã không tồn tại, đã hết hạn hoặc không được công khai.</p>
        <a href="/app/views/user/home.php" class="detail-primary-button">Xem mã khác</a>
    </section>

    <?php else: ?>

    <!-- Thông báo nếu mã không khả dụng hoặc hết hạn (U07) -->
    <?php if ($coupon['status'] === 'Không khả dụng' || $coupon['status'] === 'Hết hạn'): ?>
    <div class="alert alert-warning" style="margin-bottom: 20px;">
        ⚠️ <strong>Thông báo:</strong> Mã này hiện tại <strong><?= $coupon['status'] ?></strong>. Vui lòng tham khảo các
        ưu đãi khác.
    </div>
    <?php endif; ?>

    <section class="coupon-detail-layout">

        <div class="detail-main-card">

            <div class="detail-store-row">
                <div class="detail-store-logo">
                    <?php if (isset($logos[$coupon['store']])): ?>
                    <img src="<?= htmlspecialchars($logos[$coupon['store']]) ?>"
                        alt="<?= htmlspecialchars($coupon['store']) ?>">
                    <?php else: ?>
                    <span class="detail-highlands-logo">H</span>
                    <?php endif; ?>
                </div>

                <div class="detail-store-info">
                    <span class="detail-eyebrow">ƯU ĐÃI TỪ CỬA HÀNG</span>
                    <h2><?= htmlspecialchars($coupon['store']) ?></h2>
                    <span class="detail-category"><?= htmlspecialchars($coupon['category']) ?></span>
                </div>

                <!-- Sửa nút Yêu thích có thuộc tính data-favorite-code -->
                <button type="button" class="favorite-toggle-btn" id="favBtn"
                    data-favorite-code="<?= htmlspecialchars($coupon['code']) ?>">
                    <!-- Icon trái tim nét đơn sắc (Monochrome SVG) -->
                    <svg class="heart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l8.72-8.72 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                        </path>
                    </svg>
                    <span class="fav-text">Yêu thích</span>
                </button>

                <span class="detail-status"><?= htmlspecialchars($coupon['status']) ?></span>
            </div>

            <div class="detail-divider"></div>

            <div class="detail-offer">
                <h1><?= htmlspecialchars($coupon['title']) ?></h1>
                <div class="detail-discount"><?= htmlspecialchars($coupon['discount']) ?></div>
                <p><?= htmlspecialchars($coupon['description']) ?></p>
            </div>

            <div class="detail-code-box">
                <div>
                    <span class="detail-eyebrow">MÃ GIẢM GIÁ</span>
                    <strong id="couponCode"><?= htmlspecialchars($coupon['code']) ?></strong>
                </div>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="detail-primary-button" id="copyDetailCode">
                        Sao chép mã
                    </button>

                    <!-- Mở liên kết nguồn (U03) -->
                    <a href="https://shopee.vn" target="_blank" rel="noopener noreferrer"
                        class="detail-secondary-button">
                        Đến cửa hàng ↗
                    </a>
                </div>
            </div>

            <!-- Đánh giá & Báo cáo mã sai (U05) -->
            <div class="interaction-bar">
                <div class="rating-box">
                    <span>Đánh giá hiệu quả:</span>
                    <div class="stars">
                        <button class="star-btn">★</button>
                        <button class="star-btn">★</button>
                        <button class="star-btn">★</button>
                        <button class="star-btn">★</button>
                        <button class="star-btn">☆</button>
                        <strong>(4.0/5 - 28 lượt)</strong>
                    </div>
                </div>

                <button type="button" class="report-btn" onclick="openReportModal()">
                    🚩 Báo cáo mã sai / Hết hạn
                </button>
            </div>

            <div class="detail-conditions" style="margin-top: 30px;">
                <h3>Điều kiện sử dụng</h3>
                <ul>
                    <?php foreach ($coupon['conditions'] as $condition): ?>
                    <li><?= htmlspecialchars($condition) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Khối Bình luận / Trao đổi (U07) -->
            <div class="comments-section">
                <h3>Bình luận từ cộng đồng</h3>

                <div class="comment-input-box">
                    <input type="text" id="commentInput" placeholder="Viết cảm nhận hoặc phản hồi về mã này..."
                        autocomplete="off">
                    <button type="button" id="sendCommentBtn" class="detail-primary-button">
                        Gửi
                    </button>
                </div>

                <!-- Danh sách hiển thị bình luận -->
                <div class="comment-list" id="commentsList">
                    <div class="comment-item">
                        <div class="comment-header">
                            <span class="comment-author">Minh Hoàng</span>
                            <span class="comment-time">10 phút trước</span>
                        </div>
                        <p class="comment-text">Mã dùng ngon nhé mọi người, áp dụng cho Highlands size L giảm được hẳn
                            20%!</p>
                    </div>

                    <div class="comment-item">
                        <div class="comment-header">
                            <span class="comment-author">Thanh Trúc</span>
                            <span class="comment-time">1 giờ trước</span>
                        </div>
                        <p class="comment-text">Mình vừa áp thành công lúc 14h, bấm copy mã rồi dán vào app là xong
                            ngay.</p>
                    </div>
                </div>
            </div>
        </div>

        <aside class="detail-sidebar">
            <div class="detail-sidebar-card">
                <span class="detail-eyebrow">COUPONHUB TIP</span>
                <h3>Mẹo sử dụng</h3>
                <p>Nên dán mã ngay ở bước Thanh toán để đảm bảo mã chưa bị giới hạn lượt dùng.</p>
            </div>
        </aside>

    </section>

    <!-- Modal báo cáo mã sai (U05) -->
    <div id="reportModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <h3>Báo cáo mã sai hoặc hết hạn</h3>
            <p>Giúp CouponHub giữ dữ liệu luôn chính xác:</p>
            <form onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã gửi báo cáo!'); closeModal();">
                <label><input type="radio" name="reason" checked> Mã không đúng / Không áp dụng được</label><br>
                <label><input type="radio" name="reason"> Mã đã hết lượt sử dụng</label><br>
                <label><input type="radio" name="reason"> Sai mức giảm giá</label><br>
                <textarea placeholder="Ghi chú thêm (nếu có)..." rows="3"
                    style="width:100%; margin-top:10px; padding:8px;"></textarea>

                <div style="margin-top: 15px; text-align: right; gap: 10px; display: flex; justify-content: flex-end;">
                    <button type="button" onclick="closeModal()" class="category-clear-button"
                        style="width:auto;">Hủy</button>
                    <button type="submit" class="detail-primary-button">Gửi báo cáo</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openReportModal() {
        document.getElementById('reportModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('reportModal').style.display = 'none';
    }
    </script>

    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
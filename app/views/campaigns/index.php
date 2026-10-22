<div class="discovery-page editorial-campaign-page">
    <div class="member-breadcrumb">
        <a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Chiến dịch</span>
    </div>

    <!-- TIÊU ĐỀ TRANG BAN ĐẦU -->
    <div class="editorial-section-head campaign-page-heading">
        <div>
            <span class="eyebrow">NHỮNG DỊP MUA SẮM ĐÁNG CHỜ</span>
            <h1>Đúng thời điểm.<br><em>Thêm nhiều giá trị.</em></h1>
        </div>
        <a href="<?= e(url('/campaign-calendar')) ?>">Lịch ưu đãi <?= icon('arrow') ?></a>
    </div>

    <?php 
    // Tự động mở bộ lọc nếu người dùng đang chọn lọc
    $hasCampaignFilters = !empty($selectedCategory) || ($selectedChannel !== 'ALL') || (($selectedStatus ?? 'ALL') !== 'ALL'); 
    ?>

    <!-- BỘ LỌC CHIẾN DỊCH (TẮT/MỞ DẠNG TEXT TRONG SUỐT) -->
    <details class="campaign-advanced" <?= $hasCampaignFilters ? 'open' : '' ?>>
        <summary class="btn-filter-toggle">
            <?= icon('filter') ?>
            <span>Bộ lọc</span>
            <?= icon('chevron') ?>
        </summary>

        <form class="campaign-filter-row" method="GET" action="<?= e(url('/campaigns')) ?>">
            <!-- 1. Danh mục -->
            <div class="filter-field">
                <label for="campaign-category">Danh mục</label>
                <select id="campaign-category" name="category_id" class="form-select">
                    <option value="0">Tất cả danh mục</option>
                    <?php foreach ($categories as $category): ?>
                        <option
                            value="<?= (int) $category['id'] ?>"
                            <?= (int) $selectedCategory === (int) $category['id'] ? 'selected' : '' ?>
                        >
                            <?= e($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 2. Kênh sử dụng -->
            <div class="filter-field">
                <label for="campaign-channel">Kênh sử dụng</label>
                <select id="campaign-channel" name="usage_channel" class="form-select">
                    <option value="ALL" <?= $selectedChannel === 'ALL' ? 'selected' : '' ?>>Tất cả kênh</option>
                    <option value="ONLINE" <?= $selectedChannel === 'ONLINE' ? 'selected' : '' ?>>Online</option>
                    <option value="OFFLINE" <?= $selectedChannel === 'OFFLINE' ? 'selected' : '' ?>>Offline</option>
                    <option value="BOTH" <?= $selectedChannel === 'BOTH' ? 'selected' : '' ?>>Online và Offline</option>
                </select>
            </div>

            <!-- 3. Thời gian -->
            <div class="filter-field">
                <label for="campaign-status">Thời gian</label>
                <select id="campaign-status" name="status" class="form-select">
                    <option value="ALL" <?= ($selectedStatus ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>Tất cả thời gian</option>
                    <option value="RUNNING" <?= ($selectedStatus ?? '') === 'RUNNING' ? 'selected' : '' ?>>Đang diễn ra</option>
                    <option value="UPCOMING" <?= ($selectedStatus ?? '') === 'UPCOMING' ? 'selected' : '' ?>>Sắp diễn ra</option>
                    <option value="ENDED" <?= ($selectedStatus ?? '') === 'ENDED' ? 'selected' : '' ?>>Đã kết thúc</option>
                </select>
            </div>

            <!-- 4. Nút bấm thao tác -->
            <div class="filter-actions">
                <button type="submit" class="btn btn-apply-filter">Áp dụng bộ lọc</button>
                <a href="<?= e(url('/campaigns')) ?>" class="btn-reset-filter">Đặt lại</a>
            </div>
        </form>
    </details>

    <?php if (!empty($listing['items'])): ?>
        <?php $mainCampaign = $listing['items'][0]; ?>
        
        <!-- CHIẾN DỊCH ĐANG DIỄN RA (BANNER LỚN NỔI BẬT) -->
        <section class="campaign-section-block">
            <span class="section-label">CHIẾN DỊCH ĐANG DIỄN RA</span>
            
            <?php 
                $campaign = $mainCampaign; 
                $isMainPoster = true; 
                $posterPriority = true; 
                require __DIR__ . '/../partials/campaign-poster.php'; 
            ?>
        </section>

        <!-- CÁC CHIẾN DỊCH KHÁC + THẺ LỊCH SĂN MÃ -->
        <?php $otherCampaigns = array_slice($listing['items'], 1); ?>
        <section class="campaign-section-block">
            <span class="section-label">CÁC CHIẾN DỊCH KHÁC</span>

            <div class="other-campaigns-grid">
                <?php foreach ($otherCampaigns as $campaign): ?>
                    <?php 
                        $isMainPoster = false; 
                        require __DIR__ . '/../partials/campaign-poster.php'; 
                    ?>
                <?php endforeach; ?>

                <?php if (count($otherCampaigns) < 2): ?>
                    <aside class="campaign-planner">
                        <span class="eyebrow">LÊN LỊCH CHO LẦN SĂN MÃ TIẾP THEO</span>
                        
                        <div class="planner-date">
                            <strong data-planner-day><?= date('d') ?></strong>
                            <div>
                                <span data-planner-month>THÁNG <?= date('m') ?> / <?= date('Y') ?></span>
                                <time data-time-vn datetime="<?= date(DATE_ATOM) ?>"><?= date('H:i:s') ?></time>
                                <small>Giờ Việt Nam</small>
                            </div>
                        </div>

                        <p>Xem các dịp mua sắm và chiến dịch trong một lịch chung.</p>

                        <a href="<?= e(url('/campaign-calendar')) ?>">
                            Khám phá lịch ưu đãi <?= icon('arrow') ?>
                        </a>
                    </aside>
                <?php endif; ?>
            </div>
        </section>

    <?php else: ?>
        <div class="member-empty">
            <strong>Chưa tìm thấy chiến dịch phù hợp.</strong>
            <p>Vui lòng thử lại với bộ lọc hoặc chọn danh mục khác.</p>
            <a href="<?= e(url('/campaigns')) ?>" class="btn-reset-filter">Xóa bộ lọc</a>
        </div>
    <?php endif; ?>

    <!-- DEALS TRONG CHIẾN DỊCH -->
    <?php require __DIR__ . '/../partials/campaign-deals.php'; ?>
</div>
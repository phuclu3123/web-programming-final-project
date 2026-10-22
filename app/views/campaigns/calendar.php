<div class="discovery-page editorial-calendar-page">
    <div class="member-breadcrumb">
        <a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Lịch ưu đãi</span>
    </div>

    <!-- HERO HEADLINE CHUẨN ENTERPRISE -->
    <div class="editorial-section-head calendar-page-heading">
        <div class="heading-content">
            <span class="eyebrow">DÀNH CHỖ CHO KẾ HOẠCH TIẾP THEO</span>
            <h1>Lịch ưu đãi 2026</h1>
            <p class="section-desc">Theo dõi tiến độ các chiến dịch đang diễn ra và chuẩn bị cho các đợt phát hành mã ưu đãi tiếp theo.</p>
        </div>
        <div class="heading-actions">
            <a href="<?= e(url('/campaigns')) ?>" class="btn-secondary-link">
                Xem danh sách chiến dịch <?= icon('arrow') ?>
            </a>
        </div>
    </div>

    <?php
    $selectedMonth = $_GET['month'] ?? '';
    $selectedYear  = (int)($_GET['year'] ?? date('Y'));
    ?>

    <!-- BỘ LỌC THEO THÁNG & NĂM -->
    <form class="calendar-filter-bar" method="GET" action="<?= e(url('/campaign-calendar')) ?>">
        <!-- 1. Chọn Tháng -->
        <div class="filter-group">
            <label for="filter-month">Tháng</label>
            <select id="filter-month" name="month" class="form-select">
                <option value="">Tất cả các tháng</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <?php $mVal = sprintf('%02d', $m); ?>
                    <option value="<?= $mVal ?>" <?= $selectedMonth === $mVal ? 'selected' : '' ?>>
                        Tháng <?= $m ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- 2. Chọn Năm -->
        <div class="filter-group">
            <label for="filter-year">Năm</label>
            <select id="filter-year" name="year" class="form-select">
                <?php 
                $currentYear = (int)date('Y');
                for ($y = $currentYear - 1; $y <= $currentYear + 2; $y++): 
                ?>
                    <option value="<?= $y ?>" <?= $selectedYear === $y ? 'selected' : '' ?>>
                        Năm <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- 3. Nút thao tác -->
        <div class="filter-actions">
            <button type="submit" class="btn-calendar-filter">Lọc lịch</button>
            <?php if (!empty($selectedMonth) || $selectedYear !== (int)date('Y')): ?>
                <a href="<?= e(url('/campaign-calendar')) ?>" class="btn-calendar-reset">Đặt lại</a>
            <?php endif; ?>
        </div>
    </form>
    
    <!-- TIMELINE CONTAINER -->
    <section class="calendar-timeline-section">
        <div class="timeline-header">
            <h2>Đang diễn ra & Sắp tới</h2>
            <span class="timeline-count"><?= count($campaigns ?? []) ?> chiến dịch</span>
        </div>

        <div class="calendar-timeline-list">
            <?php foreach ($campaigns as $item): ?>
                <article class="timeline-row-card">
                    <!-- Khối ngày tháng vuông vức, tối giản -->
                    <div class="timeline-date-badge">
                        <span class="day"><?= date('d', strtotime($item['start_at'])) ?></span>
                        <span class="month">THÁNG <?= date('m', strtotime($item['start_at'])) ?></span>
                    </div>

                    <!-- Nội dung chi tiết -->
                    <div class="timeline-info">
                        <div class="timeline-meta">
                            <span class="status-pill status-<?= strtolower($item['status'] ?? 'running') ?>">
                                <?= ($item['status'] ?? '') === 'RUNNING' ? 'Đang diễn ra' : 'Sắp diễn ra' ?>
                            </span>
                            <span class="merchant-tag"><?= e($item['merchant_name'] ?? 'CouponHub') ?></span>
                        </div>

                        <h3 class="timeline-title">
                            <a href="<?= e(url('/campaigns/'.(int)$item['id'])) ?>">
                                <?= e($item['name']) ?>
                            </a>
                        </h3>

                        <div class="timeline-timeframe">
                            <?= icon('clock') ?>
                            <span><?= date('d/m/Y H:i', strtotime($item['start_at'])) ?> — <?= date('d/m/Y H:i', strtotime($item['end_at'])) ?></span>
                        </div>
                    </div>

                    <!-- Nút thao tác góc phải -->
                    <div class="timeline-action">
                        <a href="<?= e(url('/campaigns/'.(int)$item['id'])) ?>" class="btn-timeline-detail">
                            Chi tiết <?= icon('arrow') ?>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</div>
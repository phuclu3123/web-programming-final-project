
<?php if ($campaignPreview): ?>
<section id="campaign-deals"
         class="campaign-deals-section"
         aria-labelledby="campaign-deals-title"
         data-campaign-preview="<?= (int) $campaignPreview['id'] ?>">

    <div class="studio-section-title">
        <div>
            <span class="eyebrow">ĐI TỪ CHIẾN DỊCH ĐẾN ƯU ĐÃI</span>
            <h2 id="campaign-deals-title">Có mã gì cho bạn?</h2>
            <p>Chọn chiến dịch, lọc danh mục và tìm coupon phù hợp với bạn.</p>
        </div>

        <a href="<?= e(url('/campaigns/' . (int) $campaignPreview['id'])) ?>">
            Xem toàn bộ chiến dịch <?= icon('arrow') ?>
        </a>
    </div>

    <nav class="campaign-deal-tabs" aria-label="Chọn chiến dịch để xem coupon">
        <?php foreach ($listing['items'] as $previewOption): ?>
            <a
                href="<?= e(url('/campaigns') . '?' . http_build_query([
                    'phase' => $phase,
                    'page' => $listing['page'],
                    'preview' => (int) $previewOption['id'],
                    'category_id' => $selectedCategory ?? 0,
                    'usage_channel' => $selectedChannel ?? 'ALL',
                ])) ?>#campaign-deals"
                <?= (int) $previewOption['id'] === (int) $campaignPreview['id']
                    ? 'aria-current="page"'
                    : '' ?>
            >
                <?= brandLogo($previewOption['merchant_name'] ?: 'CouponHub') ?>
                <span><?= e(editorialTitle($previewOption['name'])) ?></span>
                <small><?= (int) $previewOption['coupon_count'] ?> mã</small>
            </a>
        <?php endforeach; ?>
    </nav>

    <form
        class="campaign-coupon-filters"
        method="get"
        action="<?= e(url('/campaigns')) ?>#campaign-deals"
    >
        <input type="hidden" name="phase" value="<?= e($phase) ?>">
        <input type="hidden" name="page" value="1">

        <div class="campaign-coupon-filter-field">
            <label for="campaign-category-filter">Danh mục coupon</label>
            <select id="campaign-category-filter" name="category_id">
                <option value="0">Tất cả danh mục</option>

                <?php foreach (($categories ?? []) as $category): ?>
                    <option
                        value="<?= (int) $category['id'] ?>"
                        <?= (int) ($selectedCategory ?? 0) === (int) $category['id']
                            ? 'selected'
                            : '' ?>
                    >
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="campaign-coupon-filter-field">
            <label for="campaign-channel-filter">Kênh sử dụng</label>
            <select id="campaign-channel-filter" name="usage_channel">
                <option value="ALL"
                    <?= ($selectedChannel ?? 'ALL') === 'ALL' ? 'selected' : '' ?>>
                    Tất cả kênh
                </option>

                <option value="ONLINE"
                    <?= ($selectedChannel ?? 'ALL') === 'ONLINE' ? 'selected' : '' ?>>
                    Online
                </option>

                <option value="OFFLINE"
                    <?= ($selectedChannel ?? 'ALL') === 'OFFLINE' ? 'selected' : '' ?>>
                    Offline
                </option>

                <option value="BOTH"
                    <?= ($selectedChannel ?? 'ALL') === 'BOTH' ? 'selected' : '' ?>>
                    Dùng được cả Online và Offline
                </option>
            </select>
        </div>

        <div class="campaign-coupon-filter-actions">
            <button type="submit" class="campaign-coupon-filter-submit">
                Lọc coupon
            </button>

            <a
                class="campaign-coupon-filter-reset"
                href="<?= e(url('/campaigns') . '?' . http_build_query([
                    'phase' => $phase,
                    'page' => $listing['page'],
                    'preview' => (int) $campaignPreview['id'],
                ])) ?>#campaign-deals"
            >
                Xóa bộ lọc
            </a>
        </div>
    </form>

    <?php if ($campaignCoupons): ?>
        <div class="campaign-deal-grid <?= count($campaignCoupons) === 1
            ? 'campaign-deal-grid-single'
            : '' ?>">
            <?php foreach ($campaignCoupons as $previewCoupon): ?>
                <?php require __DIR__ . '/campaign-coupon-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <p class="campaign-deal-note">
            <?= icon('clock') ?>
            Ngày giờ theo giờ Việt Nam. Hạn của coupon có thể khác ngày kết thúc chiến dịch.
        </p>
    <?php else: ?>
        <div class="member-empty">
            <strong>Không tìm thấy coupon phù hợp.</strong>
            <p>Thử chọn danh mục hoặc kênh sử dụng khác để xem thêm ưu đãi.</p>
            <a
                href="<?= e(url('/campaigns') . '?' . http_build_query([
                    'phase' => $phase,
                    'page' => $listing['page'],
                    'preview' => (int) $campaignPreview['id'],
                ])) ?>#campaign-deals"
            >
                Xóa bộ lọc <?= icon('arrow') ?>
            </a>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

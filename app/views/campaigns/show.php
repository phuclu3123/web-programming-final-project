
<?php
$timing = campaignTiming($campaign);
$campaignEnded = time() >= strtotime($campaign['ends_at'] . ' UTC');
?>

<div class="discovery-page campaign-reading event-detail-page">

    <div class="member-breadcrumb">
        <a href="<?= e(url('/')) ?>">CouponHub</a>
        <span>/</span>
        <a href="<?= e(url('/campaigns')) ?>">Chiến dịch</a>
        <span>/</span>
        <span><?= e($campaign['merchant_name'] ?: 'CouponHub') ?></span>
    </div>

    <?php $detailMode=true; $posterPriority=true; require __DIR__.'/../partials/campaign-feature.php'; ?>
    <section class="event-overview" aria-label="Tổng quan chiến dịch">
        <div><strong><?= (int)$campaignOverview['count'] ?></strong><span>coupon còn hiệu lực</span></div>
        <div><strong><?= count($campaignOverview['brands']) ?></strong><span>thương hiệu tham gia</span></div>
        <div><strong><?= count($campaignOverview['categories']) ?></strong><span>nhóm nhu cầu</span></div>
        <div><span>Đơn tối thiểu từ</span><strong class="event-minimum"><?= $campaignOverview['minimum']===null ? 'Chưa có coupon' : ($campaignOverview['minimum']>0 ? e(money($campaignOverview['minimum'])) : 'Không yêu cầu') ?></strong></div>
    </section>
    <?php if ($campaignOverview['brands']): ?><section class="event-participants" aria-labelledby="participants-title"><h2 id="participants-title">Các thương hiệu trong bộ sưu tập.</h2><div><?php foreach ($campaignOverview['brands'] as $brand=>$merchantId): ?><a href="<?= e(url('/campaigns/'.(int)$campaign['id']).'?platform='.$merchantId) ?>#campaign-offers"><?= brandLogo($brand) ?><span><?= e($brand) ?></span><?= icon('arrow') ?></a><?php endforeach; ?></div></section><?php endif; ?>

    <div class="campaign-date-strip">
        <span>
            <small>Bắt đầu</small>
            <time>
                <?= e(format_db_datetime($campaign['starts_at'], 'd/m/Y · H:i')) ?>
            </time>
        </span>

        <span>
            <small>Kết thúc</small>
            <time>
                <?= e(format_db_datetime($campaign['ends_at'], 'd/m/Y · H:i')) ?>
            </time>
        </span>

        <p>Giờ Việt Nam. Mỗi coupon có điều kiện và hạn dùng riêng.</p>
    </div>

    <section id="campaign-offers" class="campaign-offers-section">

    <div class="studio-section-title">
        <div>
            <span class="eyebrow">TRONG CHIẾN DỊCH NÀY</span>
            <h2>Chọn mã cho kế hoạch của bạn.</h2>
        </div>

        <span><?= count($coupons) ?> ưu đãi còn hiệu lực</span>
    </div>

    

    <details class="campaign-offer-options" <?= $selectedCategory || $selectedPlatform || $selectedChannel !== 'ALL' ? 'open' : '' ?>>
    <summary><?= icon('filter') ?> Tùy chọn lọc coupon <?= icon('chevron') ?></summary>
    <form id="campaign-coupon-filter" class="campaign-coupon-filters" method="get" action="<?= e(url('/campaigns/'.(int)$campaign['id'])) ?>#campaign-offers">
        <div class="campaign-coupon-filter-field"><label for="campaign-category">Danh mục coupon</label><select name="category_id" id="campaign-category"><option value="0">Tất cả danh mục</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>" <?= $selectedCategory===(int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div>
        <div class="campaign-coupon-filter-field"><label for="campaign-channel">Kênh sử dụng</label><select name="usage_channel" id="campaign-channel"><?php foreach (['ALL'=>'Tất cả kênh','ONLINE'=>'Online','OFFLINE'=>'Offline','BOTH'=>'Online và Offline'] as $key=>$label): ?><option value="<?= $key ?>" <?= $selectedChannel===$key ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
        <div class="campaign-coupon-filter-field"><?php $platformSelectId='campaign-platform'; require __DIR__.'/../partials/campaign-platform-filter.php'; ?></div>
        <div class="campaign-coupon-filter-actions"><button type="submit" class="campaign-coupon-filter-submit">Lọc coupon</button><a href="<?= e(url('/campaigns/'.(int)$campaign['id'])) ?>#campaign-offers" id="reset-campaign-filter" class="campaign-coupon-filter-reset">Đặt lại</a></div>
    </form>
    </details>
    <p class="campaign-filter-feedback" role="status" hidden></p>
    <!-- Khu vực được cập nhật bằng AJAX -->
    <div id="campaign-coupon-results" aria-live="polite">

        <?php if (!$coupons): ?>

            <div class="member-empty">
                <strong><?= $campaignEnded ? 'Chiến dịch đã kết thúc.' : 'Không tìm thấy coupon phù hợp.' ?></strong>
                <p><?= $campaignEnded ? 'Ưu đãi còn hạn vẫn có thể được tìm thấy ngoài chiến dịch.' : 'Thử chọn danh mục, nền tảng hoặc kênh sử dụng khác nhé.' ?></p>
                <?php if ($campaignEnded): ?><a href="<?= e(url('/coupons')) ?>">Khám phá coupon còn hiệu lực <?= icon('arrow') ?></a><?php endif; ?>
            </div>

        <?php else: ?>

            <p class="text-muted">
                <?= count($coupons) ?> ưu đãi phù hợp
            </p>

            <div class="campaign-deal-grid">
                <?php
                foreach ($coupons as $previewCoupon):
                    require __DIR__ . '/../partials/campaign-coupon-card.php';
                endforeach;
                ?>
            </div>

        <?php endif; ?>

    </div>
</section>

    <section class="event-guide" aria-labelledby="event-guide-title"><div><h2 id="event-guide-title">Chọn đúng mã.<br>Dùng đúng điều kiện.</h2><p>Thông tin cần kiểm tra trước khi thanh toán.</p></div><div><details open><summary>Mã này áp dụng cho đơn hàng nào?</summary><p>Mỗi coupon ghi rõ đơn tối thiểu, mức giảm tối đa và kênh sử dụng. Mở “Lấy mã” để đọc đầy đủ điều kiện trước khi sao chép.</p></details><details><summary>Hạn dùng coupon có trùng với chiến dịch?</summary><p>Không nhất thiết. Thời gian chiến dịch là thời gian bộ sưu tập được công bố; mỗi mã có hạn dùng riêng, hiển thị ngay trên coupon.</p></details><details><summary>Tôi kiểm tra mức giảm ở đâu?</summary><p>Áp dụng mã tại bước thanh toán của cửa hàng và kiểm tra số tiền cuối cùng. Nếu điều kiện đã thay đổi, bạn có thể gửi báo cáo trên trang chi tiết coupon.</p></details></div></section>
    <a class="campaign-back-link" href="<?= e(url('/campaigns')) ?>">
        Tất cả chiến dịch <?= icon('arrow') ?>
    </a>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('campaign-coupon-filter');

    if (!form) {
        return;
    }

    const categorySelect = document.getElementById('campaign-category');
    const channelSelect = document.getElementById('campaign-channel');
    const platformSelect = document.getElementById('campaign-platform');
    const results = document.getElementById('campaign-coupon-results');
    const resetButton = document.getElementById('reset-campaign-filter');
    const feedback = document.querySelector('.campaign-filter-feedback');

    let requestNumber = 0;

    async function filterCoupons() {
        const currentRequest = ++requestNumber;

        const url = new URL(form.action, window.location.href);

        url.searchParams.set('category_id', categorySelect.value);
        url.searchParams.set('usage_channel', channelSelect.value);
        url.searchParams.set('platform', platformSelect.value);

        results.style.opacity = '0.5';
        results.setAttribute('aria-busy', 'true');
        feedback.hidden = true;

        try {
            const response = await fetch(url.toString(), {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Không thể tải danh sách coupon.');
            }

            const html = await response.text();
            const parser = new DOMParser();
            const newDocument = parser.parseFromString(html, 'text/html');

            const newResults = newDocument.querySelector(
                '#campaign-coupon-results'
            );

            const newTitleCount = newDocument.querySelector(
                '.campaign-offers-section .studio-section-title > span'
            );

            if (!newResults) {
                throw new Error('Không tìm thấy danh sách coupon.');
            }

            // Bỏ qua kết quả của yêu cầu cũ nếu người dùng đổi bộ lọc liên tục.
            if (currentRequest !== requestNumber) {
                return;
            }

            // Chỉ cập nhật danh sách coupon, không tải lại toàn bộ trang.
            results.innerHTML = newResults.innerHTML;
            const newAction = newDocument.querySelector('.campaign-detail-action');
            const currentAction = document.querySelector('.campaign-detail-action');
            if (newAction && currentAction) currentAction.innerHTML = newAction.innerHTML;

            if (newTitleCount) {
                const titleCount = document.querySelector(
                    '.campaign-offers-section .studio-section-title > span'
                );

                if (titleCount) {
                    titleCount.textContent = newTitleCount.textContent;
                }
            }

            // Đồng bộ URL để có thể sao chép đường dẫn bộ lọc.
            window.history.replaceState({}, '', url.toString());

        } catch (error) {
            if (currentRequest === requestNumber) {
                feedback.textContent = 'Không thể lọc coupon. Vui lòng thử lại.';
                feedback.hidden = false;
            }
        } finally {
            if (currentRequest === requestNumber) {
                results.style.opacity = '1';
                results.removeAttribute('aria-busy');
            }
        }
    }

    // Tự lọc khi người dùng thay đổi một trong hai lựa chọn.
    categorySelect.addEventListener('change', filterCoupons);
    channelSelect.addEventListener('change', filterCoupons);
    platformSelect.addEventListener('change', filterCoupons);
    form.addEventListener('submit', function (event) { event.preventDefault(); filterCoupons(); });

    // Đặt lại cả hai bộ lọc mà không reload trang.
    resetButton.addEventListener('click', function (event) {
        event.preventDefault();
        categorySelect.value = '0';
        channelSelect.value = 'ALL';
        platformSelect.value = '0';

        filterCoupons();
    });
});
</script>


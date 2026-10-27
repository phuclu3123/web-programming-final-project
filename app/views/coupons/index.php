<?php
use App\Core\Auth;
$currentUser = Auth::user();
$advancedCount = (int)!empty($filters['merchant_id']) + (int)!empty($filters['discount_type']) + (int)!empty($filters['usage_channel']) + (int)!empty($filters['ending_soon']);
$types = ['PERCENTAGE'=>'Giảm phần trăm','FIXED_AMOUNT'=>'Giảm số tiền','FREE_SHIPPING'=>'Miễn phí vận chuyển','BUY_X_GET_Y'=>'Mua X tặng Y','GIFT'=>'Quà tặng','CASHBACK'=>'Hoàn tiền'];
?>
<div class="community-catalog">
    <header class="community-feed-header community-cover">
        <img class="community-cover-photo" src="<?= e(url('/assets/images/hero/category-food.webp')) ?>" alt=""
            width="2560" height="1707" fetchpriority="high" decoding="async">
        <div class="community-cover-copy">
            <h1>COUPON CỘNG ĐỒNG <span data-community-total><?= (int)$pagination['total'] ?></span></h1>
            <h2>Của bạn.<br>Cho cả hội.</h2>
            <p>Một mã tìm được. Thêm người dùng được.<br>Chia sẻ ưu đãi và trải nghiệm thật cùng CouponHub.</p>
        </div>
        <div class="community-feed-actions">
            <?php if (in_array(Auth::role(),['USER','MERCHANT'],true)): ?><a class="btn btn-brand"
                href="<?= e(url('/coupons/create')) ?>">Chia sẻ coupon <span aria-hidden="true">+</span></a>
            <?php elseif (!$currentUser): ?><a class="btn btn-brand"
                href="<?= e(url('/login').'?return='.rawurlencode(url('/coupons/create'))) ?>">Chia sẻ coupon <span
                    aria-hidden="true">+</span></a><?php endif; ?><a
                href="<?= e(url('/guides/kiem-chung-cung-cong-dong')) ?>">Cách kiểm chứng mã <?= icon('arrow') ?></a>
        </div>
    </header>
    <div class="container py-3"><a class="btn btn-outline-primary btn-sm" href="<?= e(url('/tools/best-deal')) ?>">Tính
            ưu đãi tốt nhất cho đơn hàng</a> <a class="btn btn-outline-primary btn-sm"
            href="<?= e(url('/deal-requests')) ?>">Nhờ cộng đồng tìm mã giảm</a> <a
            class="btn btn-outline-primary btn-sm" href="<?= e(url('/tools/match')) ?>">Tìm mã theo nhu cầu</a> <a
            class="btn btn-outline-primary btn-sm" href="<?= e(url('/tools/stack-checker')) ?>">Kiểm tra kết hợp mã</a>
    </div>
    <div class="community-board">
        <div class="community-board-main">
            <form id="coupon-search-form" class="community-search" method="get" action="<?= e(url('/coupons')) ?>"
                data-api-url="<?= e(url('/api/coupons/search')) ?>">
                <input type="hidden" name="page" id="coupon-page" value="<?= (int)$pagination['page'] ?>">
                <div class="community-search-row">
                    <div class="community-search-input"><?= icon('search') ?><label class="sr-only" for="keyword">Tìm ưu
                            đãi</label><input type="search" id="keyword" name="keyword" maxlength="200"
                            value="<?= e((string)($filters['keyword'] ?? '')) ?>"
                            placeholder="Tìm cửa hàng, mã giảm giá, ưu đãi…"><button class="btn btn-brand"
                            type="submit">Tìm kiếm <?= icon('arrow') ?></button></div>
                </div>
                <fieldset class="community-categories">
                    <legend class="sr-only">Danh mục ưu đãi</legend>
                    <label><input type="radio" name="category_id" value=""
                            <?= empty($filters['category_id']) ? 'checked' : '' ?>><span><?= icon('grid') ?> Tất
                            cả</span></label>
                    <?php foreach ($categories as $category): ?><label><input type="radio" name="category_id"
                            value="<?= $category->id ?>"
                            <?= (int)($filters['category_id'] ?? 0) === $category->id ? 'checked' : '' ?>><span><?= e($category->name) ?></span></label><?php endforeach; ?>
                </fieldset>
                <!-- BỘ LỌC NÚT BẤM CÓ THỂ ĐÓNG / MỞ (COLLAPSIBLE FILTER) -->
                <div class="community-controls">
                    <details class="community-advanced" <?= $advancedCount ? 'open' : '' ?>>
                        <summary class="btn-filter-toggle">
                            <?= icon('filter') ?>
                            <span>Bộ lọc</span>
                            <span class="community-filter-count"
                                <?= !$advancedCount ? 'hidden' : '' ?>><?= $advancedCount ?></span>
                            <?= icon('chevron') ?>
                        </summary>

                        <!-- BỘ LỌC DÀN NGANG (CHỈ HIỆN KHI MỞ BỘ LỌC) -->
                        <div class="community-filter-row">
                            <!-- 1. Cửa hàng -->
                            <div class="filter-field">
                                <label for="merchant_id">Cửa hàng</label>
                                <select class="form-select" id="merchant_id" name="merchant_id">
                                    <option value="">Tất cả cửa hàng</option>
                                    <?php foreach ($merchants as $merchant): ?>
                                    <option value="<?= $merchant->id ?>"
                                        <?= (int)($filters['merchant_id'] ?? 0) === (int)$merchant->id ? 'selected' : '' ?>>
                                        <?= e($merchant->name) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 2. Loại ưu đãi -->
                            <div class="filter-field">
                                <label for="discount_type">Loại ưu đãi</label>
                                <select class="form-select" id="discount_type" name="discount_type">
                                    <option value="">Tất cả loại ưu đãi</option>
                                    <?php foreach ($types as $value => $label): ?>
                                    <option value="<?= e($value) ?>"
                                        <?= ($filters['discount_type'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 3. Kênh sử dụng -->
                            <div class="filter-field">
                                <label for="usage_channel">Kênh sử dụng</label>
                                <select class="form-select" id="usage_channel" name="usage_channel">
                                    <option value="">Tất cả kênh</option>
                                    <?php foreach (['ONLINE' => 'Trực tuyến', 'OFFLINE' => 'Tại cửa hàng', 'BOTH' => 'Cả hai kênh'] as $value => $label): ?>
                                    <option value="<?= $value ?>"
                                        <?= ($filters['usage_channel'] ?? '') === $value ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- 4. Checkbox Sắp hết hạn -->
                            <div class="filter-field-checkbox">
                                <label class="checkbox-label" for="ending_soon">
                                    <input type="checkbox" id="ending_soon" name="ending_soon" value="1"
                                        <?= !empty($filters['ending_soon']) ? 'checked' : '' ?>>
                                    <span>Sắp hết hạn<br><small>Trong 3 ngày tới</small></span>
                                </label>
                            </div>

                            <!-- 5. Nút Áp dụng & Xóa lọc -->
                            <div class="filter-actions">
                                <button type="submit" class="btn btn-brand">Áp dụng</button>
                                <?php if ($advancedCount): ?>
                                <a class="community-reset" href="<?= e(url('/coupons')) ?>">Xóa lọc</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </details>

                    <!-- SẮP XẾP GÓC PHẢI -->
                    <div class="community-sort">
                        <label for="sort">Sắp xếp</label>
                        <select id="sort" name="sort">
                            <option value="NEWEST" <?= ($filters['sort'] ?? '') === 'NEWEST' ? 'selected' : '' ?>>Mới
                                nhất</option>
                            <option value="POPULAR" <?= ($filters['sort'] ?? '') === 'POPULAR' ? 'selected' : '' ?>>
                                Nhiều lượt lưu</option>
                            <option value="ENDING_SOON"
                                <?= ($filters['sort'] ?? '') === 'ENDING_SOON' ? 'selected' : '' ?>>Sắp hết hạn</option>
                        </select>
                    </div>
                </div>
            </form>
            <div class="community-results-heading">
                <p id="coupon-search-status" role="status" aria-live="polite">
                    <strong><?= (int)$pagination['total'] ?></strong> ưu đãi phù hợp <span>· Trang
                        <?= (int)$pagination['page'] ?>/<?= (int)$pagination['totalPages'] ?></span></p><span
                    class="community-results-note"><?= icon('shield') ?>Chỉ hiển thị mã còn hiệu lực</span>
            </div>
            <div id="coupon-results" class="community-ticket-grid">
                <?php require __DIR__.'/../partials/community-coupon-results.php'; ?></div>
            <div id="coupon-empty" class="community-empty <?= $coupons !== [] ? 'd-none' : '' ?>"><?= icon('search') ?>
                <h2>Chưa tìm thấy ưu đãi phù hợp.</h2>
                <p>Thử từ khóa ngắn hơn hoặc bớt một vài bộ lọc.</p><a href="<?= e(url('/coupons')) ?>"
                    class="btn btn-outline-brand">Xem tất cả ưu đãi</a>
            </div>
            <?php
$page = (int) $pagination['page'];
$totalPages = (int) $pagination['totalPages'];
$query = array_filter([
    'keyword' => $filters['keyword'],
    'category_id' => $filters['category_id'],
    'merchant_id' => $filters['merchant_id'],
    'discount_type' => $filters['discount_type'],
    'usage_channel' => $filters['usage_channel'],
    'sort' => $filters['sort'],
    'ending_soon' => $filters['ending_soon'] ? '1' : null,
], static fn($v): bool => $v !== null && $v !== '');
$pageHref = static function (int $targetPage) use ($query): string {
    return url('/coupons') . '?' . http_build_query(array_merge($query, ['page' => $targetPage]));
};
?>
            <nav id="coupon-pagination" class="<?= $totalPages > 1 ? '' : 'd-none' ?>" aria-label="Phân trang coupon">
                <ul class="pagination justify-content-center flex-wrap mt-3">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" data-page="<?= max(1, $page - 1) ?>"
                            href="<?= e($pageHref(max(1, $page - 1))) ?>" aria-label="Trang trước">Trước</a>
                    </li>
                    <?php for ($i = max(1, min($page - 2, $totalPages - 4)); $i <= min($totalPages, max(1, min($page - 2, $totalPages - 4)) + 4); $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" data-page="<?= $i ?>" href="<?= e($pageHref($i)) ?>"
                            <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" data-page="<?= min($totalPages, $page + 1) ?>"
                            href="<?= e($pageHref(min($totalPages, $page + 1))) ?>" aria-label="Trang sau">Sau</a>
                    </li>
                </ul>
            </nav>
        </div><?php require __DIR__.'/../partials/community-sidebar.php'; ?>
    </div>
</div>
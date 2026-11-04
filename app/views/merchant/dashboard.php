<header class="workspace-heading">
    <div><h1>Không gian đối tác</h1><p>Quản lý ưu đãi và theo dõi tương tác của cửa hàng.</p></div>
    <?php if ($shops !== []): ?><a class="btn btn-primary" href="<?= e(url('/merchant/coupons/create')) ?>">Tạo coupon <?= icon('arrow') ?></a><?php endif; ?>
</header>
<?php if ($shops === []): ?>
    <div class="alert alert-info">Bạn chưa có cửa hàng được duyệt. <a href="<?= e(url('/merchant/apply')) ?>">Xem quyền quản lý cửa hàng</a> để bắt đầu.</div>
<?php else: ?>
    <form method="get" class="workspace-shop-picker">
        <label for="workspace-shop">Cửa hàng đang xem</label>
        <select id="workspace-shop" class="form-select" name="merchant_id">
            <?php foreach ($shops as $shop): ?><option value="<?= (int)$shop['id'] ?>" <?= $merchant && (int)$merchant['id'] === (int)$shop['id'] ? 'selected' : '' ?>><?= e((string)$shop['name']) ?></option><?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-outline-secondary">Xem</button>
        <?php if ($merchant): ?><a href="<?= e(url('/merchant/profile/'.(int)$merchant['id'])) ?>">Hồ sơ cửa hàng <?= icon('arrow') ?></a><?php endif; ?>
    </form>
    <section class="workspace-panel" aria-labelledby="shop-coupon-overview">
        <div class="workspace-panel-heading"><h2 id="shop-coupon-overview">Ưu đãi cửa hàng</h2><a href="<?= e(url('/merchant/coupons')) ?>">Quản lý coupon <?= icon('arrow') ?></a></div>
        <dl class="workspace-metrics workspace-metrics-primary">
            <?php foreach ([['Tổng coupon','total_coupons'],['Đang hoạt động','active_coupons'],['Sắp hết hạn','expiring_soon']] as [$label,$key]): ?><div><dt><?= e($label) ?></dt><dd><?= (int)($stats[$key] ?? 0) ?></dd></div><?php endforeach; ?>
        </dl>
    </section>
    <section class="workspace-panel" aria-labelledby="shop-interaction-overview">
        <div class="workspace-panel-heading"><div><h2 id="shop-interaction-overview">Tương tác với ưu đãi</h2><p>Lượt xem, lưu và sao chép được ghi nhận trên CouponHub.</p></div></div>
        <dl class="workspace-metrics">
            <?php foreach ([['Lượt xem','total_views'],['Lượt lưu hiện tại','current_favorites'],['Lượt lưu hợp lệ','qualified_favorites'],['Lượt sao chép','total_copies']] as [$label,$key]): ?><div><dt><?= e($label) ?></dt><dd><?= (int)($stats[$key] ?? 0) ?></dd></div><?php endforeach; ?>
        </dl>
    </section>
    <a class="workspace-campaign-link" href="<?= e(url('/merchant/campaigns')) ?>"><span><strong>Chiến dịch của cửa hàng</strong><small>Kết nối các coupon trong cùng một chương trình.</small></span><?= icon('arrow') ?></a>
<?php endif; ?>

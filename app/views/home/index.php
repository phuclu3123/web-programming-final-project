<?php

use App\Core\Auth; ?>
<?php if ($currentCampaigns !== []): ?>
    <?php $campaign = $currentCampaigns[0]; ?>
    <section class="campaign-hero card shadow-sm border-0 mb-4">
        <div class="card-body p-4 p-lg-5">
            <span class="badge bg-light text-dark mb-3">Campaign đang diễn ra</span>
            <h1 class="display-4 fw-bold mb-2"><?= e((string)$campaign['name']) ?></h1>
            <?php if (!empty($campaign['description'])): ?><p class="lead mb-3"><?= e((string)$campaign['description']) ?>
                </p><?php endif; ?>
            <div class="mb-4"><i class="fa-regular fa-clock me-1"></i>Đến
                <?= e(format_db_datetime((string)$campaign['ends_at'])) ?></div>
            <a class="btn btn-light btn-lg" href="<?= e(url('/campaigns/' . (int)$campaign['id'])) ?>">Xem deal Campaign</a>
        </div>
    </section>
<?php else: ?>
    <section class="jumbotron bg-white shadow-sm border">
        <div class="row align-items-center">
            <div class="col-lg-8"><span class="badge bg-primary mb-3">CouponHub</span>
                <h1 class="display-4 fw-bold">Tìm coupon tốt hơn. Chia sẻ coupon đáng tin hơn.</h1>
                <p class="lead text-muted">Tìm deal đang hoạt động, kiểm chứng bằng cộng đồng và theo dõi Campaign của
                    Merchant.</p>
                <div class="mt-4"><a href="<?= e(url('/coupons')) ?>" class="btn btn-primary btn-lg me-2">Tìm
                        coupon</a><?php if (in_array(Auth::role(), ['USER', 'MERCHANT'], true)): ?><a
                            href="<?= e(url('/coupons/create')) ?>" class="btn btn-outline-primary btn-lg">Chia sẻ
                            coupon</a><?php elseif (!Auth::check()): ?><a href="<?= e(url('/register')) ?>"
                            class="btn btn-outline-primary btn-lg">Đăng ký</a><?php endif; ?></div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <h5 class="card-title">CouponHub</h5>
                        <ul class="mb-0 ps-3 text-muted">
                            <li>Merchant Center</li>
                            <li>Official Deal</li>
                            <li>Campaign</li>
                            <li>Admin Management</li>
                            <li>Homepage theo Campaign</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Deal nổi bật</h2><a href="<?= e(url('/coupons')) ?>">Xem tất cả</a>
    </div>
    <div class="row"><?php if ($hotDeals === []): ?><div class="col-12">
                <div class="alert alert-info">Chưa có coupon ACTIVE.</div>
            </div><?php endif; ?><?php foreach ($hotDeals as $coupon): ?><div class="col-md-6 col-lg-4 mb-4">
                <div class="card coupon-card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2"><span
                                class="badge-soft <?= e(category_badge_class((string)$coupon['category_name'])) ?>"><?= e((string)$coupon['category_name']) ?></span>
                            <span
                                class="badge-soft <?= e(usage_badge_class((string)$coupon['usage_channel'])) ?>"><?= e(usage_channel_label((string)$coupon['usage_channel'])) ?></span>
                        </div>
                        <h3 class="h5"><?= e((string)$coupon['title']) ?></h3>
                        <div class="small text-muted mb-3"><?= e((string)$coupon['merchant_name']) ?></div>
                        <div class="coupon-discount mb-2"><?= e(format_discount($coupon)) ?></div>
                        <div class="small text-muted mt-auto">Trust:
                            <?= $coupon['trust_score'] === null ? 'Chưa đủ dữ liệu' : e((string)$coupon['trust_score']) . '%' ?>
                            ·
                            <?= (int)$coupon['favorite_count'] ?> lượt lưu</div><a
                            class="btn btn-outline-primary btn-sm mt-3"
                            href="<?= e(url('/coupons/' . (int)$coupon['id'])) ?>">Xem chi tiết</a>
                    </div>
                </div>
            </div><?php endforeach; ?></div>
</section>

<div class="row mb-5">
    <div class="col-lg-7 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Campaign sắp diễn ra</h2>
        </div><?php if ($upcomingCampaigns === []): ?><div class="card shadow-sm">
                <div class="card-body text-muted">Chưa có Campaign sắp diễn ra.</div>
            </div><?php endif; ?><?php foreach ($upcomingCampaigns as $c): ?><a
                class="card shadow-sm mb-2 text-decoration-none text-reset"
                href="<?= e(url('/campaigns/' . (int)$c['id'])) ?>">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div><strong><?= e((string)$c['name']) ?></strong>
                        <div class="small text-muted"><?= e((string)($c['merchant_name'] ?? 'CouponHub')) ?></div>
                    </div>
                    <div class="text-end small"><span class="badge bg-info text-dark">Sắp diễn ra</span>
                        <div class="mt-1"><?= e(format_db_datetime((string)$c['starts_at'])) ?></div>
                    </div>
                </div>
            </a><?php endforeach; ?>
    </div>
    <div class="col-lg-5 mb-4">
        <h2 class="h4 mb-3">Merchant phổ biến</h2>
        <div class="card shadow-sm">
            <div class="list-group list-group-flush"><?php if ($popularMerchants === []): ?><div
                        class="list-group-item text-muted">Chưa đủ dữ liệu.</div>
                    <?php endif; ?><?php foreach ($popularMerchants as $m): ?><div
                        class="list-group-item d-flex justify-content-between align-items-center"><span><i
                                class="fa-solid fa-store me-2 text-primary"></i><?= e((string)$m['name']) ?></span><span
                            class="badge bg-primary rounded-pill"><?= (int)$m['active_coupon_count'] ?> deal</span></div>
                <?php endforeach; ?></div>
        </div>
    </div>
</div>

<section class="mb-5">
    <div class="d-flex justify-content-between mb-3">
        <h2 class="h4">Bài viết mới</h2><a href="<?= e(url('/articles')) ?>">Xem thêm</a>
    </div>
    <div class="row"><?php foreach (($recentArticles ?? []) as $article): ?><div class="col-md-4 mb-3">
                <div class="card card-body h-100">
                    <h3 class="h6"><a
                            href="<?= e(url('/articles/' . (string)$article['slug'])) ?>"><?= e((string)$article['title']) ?></a>
                    </h3>
                    <p class="small text-muted"><?= e((string)($article['summary'] ?? '')) ?></p>
                </div>
            </div><?php endforeach; ?></div>
    <p class="text-muted small"><a href="<?= e(url('/campaign-calendar')) ?>">Lịch ngày lễ Việt Nam</a> giúp lên ý tưởng
        Campaign.</p>
</section>
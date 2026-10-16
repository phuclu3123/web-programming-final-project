<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Admin Dashboard</h1>
        <p class="text-muted mb-0">Tổng quan User, Merchant, Coupon, Report và Campaign.</p>
    </div>
</div>
<div class="row">
    <?php foreach ([['Users','users','fa-users','/admin/users'],['User bị khóa','locked_users','fa-user-lock','/admin/users'],['Đơn xin quyền Shop','pending_manager_requests','fa-store','/admin/merchant-requests'],['Merchant đã duyệt','approved_merchants','fa-shop','/admin/merchants'],['Coupon chờ duyệt','pending_coupons','fa-ticket','/admin/coupons'],['Coupon ACTIVE','active_coupons','fa-circle-check','/coupons'],['Report đang mở','open_reports','fa-flag','/admin/reports'],['Campaign public','published_campaigns','fa-bullhorn','/admin/campaigns']] as [$label,$key,$icon,$href]): ?>
    <div class="col-6 col-lg-3 mb-3"><a class="text-decoration-none text-reset" href="<?= e(url($href)) ?>">
            <div class="card shadow-sm h-100 admin-stat-card">
                <div class="card-body">
                    <div class="text-muted small"><i class="fa-solid <?= e($icon) ?> mr-1"></i><?= e($label) ?></div>
                    <div class="display-4 admin-stat-number"><?= (int)($stats[$key]??0) ?></div>
                </div>
            </div>
        </a></div>
    <?php endforeach; ?>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <h2 class="h5">Quản trị nhanh</h2><a class="btn btn-outline-primary mr-2 mb-2"
            href="<?= e(url('/admin/users')) ?>">Users</a><a class="btn btn-outline-primary mr-2 mb-2"
            href="<?= e(url('/admin/merchants')) ?>">Merchants</a><a class="btn btn-outline-primary mr-2 mb-2"
            href="<?= e(url('/admin/merchant-requests')) ?>">Duyệt quyền Shop</a><a
            class="btn btn-outline-primary mr-2 mb-2" href="<?= e(url('/admin/articles')) ?>">Bài viết</a><a
            class="btn btn-outline-primary mr-2 mb-2" href="<?= e(url('/admin/categories')) ?>">Categories</a><a
            class="btn btn-outline-primary mr-2 mb-2" href="<?= e(url('/admin/coupons')) ?>">Coupon Moderation</a><a
            class="btn btn-outline-primary mr-2 mb-2" href="<?= e(url('/admin/campaigns')) ?>">Campaigns</a><a
            class="btn btn-outline-danger mb-2" href="<?= e(url('/admin/reports')) ?>">Reports</a>
    </div>
</div>
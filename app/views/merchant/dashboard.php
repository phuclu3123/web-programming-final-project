<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div><h1 class="h3 mb-1">Merchant Center</h1><p class="text-muted mb-0">Official Coupon, Campaign và thống kê cơ bản.</p></div>
    <div class="mt-2 mt-md-0"><a class="btn btn-primary" href="<?= e(url('/merchant/coupons/create')) ?>"><i class="fa-solid fa-plus mr-1"></i>Official Coupon</a> <a class="btn btn-outline-primary" href="<?= e(url('/merchant/campaigns/create')) ?>">Tạo Campaign</a></div>
</div>
<?php if ($shops === []): ?>
<div class="alert alert-warning">Tài khoản chưa sở hữu Merchant APPROVED. <a href="<?= e(url('/merchant/apply')) ?>">Gửi yêu cầu Merchant</a>.</div>
<?php else: ?>
<form method="get" class="form-inline mb-4"><label class="mr-2">Đang xem:</label><select class="form-control mr-2" name="merchant_id" onchange="this.form.submit()"><?php foreach($shops as $shop):?><option value="<?= (int)$shop['id'] ?>" <?= $merchant && (int)$merchant['id']===(int)$shop['id']?'selected':'' ?>><?= e((string)$shop['name']) ?></option><?php endforeach;?></select><?php if($merchant):?><a class="btn btn-outline-secondary" href="<?= e(url('/merchant/profile/'.(int)$merchant['id'])) ?>">Hồ sơ shop</a><?php endif;?></form>
<div class="row">
<?php foreach ([['Coupon','total_coupons','fa-ticket'],['Đang hoạt động','active_coupons','fa-circle-check'],['Sắp hết hạn','expiring_soon','fa-clock'],['Lượt xem','total_views','fa-eye'],['Lượt lưu','current_favorites','fa-star'],['Lượt copy','total_copies','fa-copy']] as [$label,$key,$icon]): ?>
<div class="col-6 col-lg-4 mb-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted small"><i class="fa-solid <?= e($icon) ?> mr-1"></i><?= e($label) ?></div><div class="display-4 merchant-stat-number"><?= (int)($stats[$key]??0) ?></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="card shadow-sm"><div class="card-body"><a class="btn btn-outline-primary mr-2" href="<?= e(url('/merchant/coupons')) ?>">Quản lý Official Coupon</a><a class="btn btn-outline-primary" href="<?= e(url('/merchant/campaigns')) ?>">Quản lý Campaign</a></div></div>
<?php endif; ?>

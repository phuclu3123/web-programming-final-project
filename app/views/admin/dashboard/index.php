<?php /* Trình bày thống kê đã tổng hợp tại repository, không truy vấn từ template. */ ?><header class="workspace-heading">
    <div><h1>Tổng quan quản trị</h1><p>Những việc cần xử lý và tình hình phát hành ưu đãi.</p></div>
    <a class="btn btn-primary" href="<?= e(url('/admin/articles/create')) ?>">Viết bài mới <?= icon('arrow') ?></a>
</header>
<section class="workspace-panel" aria-labelledby="review-queue-title">
    <div class="workspace-panel-heading"><h2 id="review-queue-title">Cần bạn xem xét</h2><span>Hàng đợi kiểm duyệt</span></div>
    <div class="review-queue">
        <?php foreach ([['Coupon chờ duyệt', 'pending_coupons', '/admin/coupons?status=PENDING', 'Xem nội dung và điều kiện'], ['Báo cáo đang mở', 'open_reports', '/admin/reports', 'Kiểm chứng phản hồi thành viên'], ['Yêu cầu quyền shop', 'pending_manager_requests', '/admin/merchant-requests', 'Xem xét quyền quản lý cửa hàng']] as [$label,$key,$href,$description]): ?>
        <a href="<?= e(url($href)) ?>" class="review-queue-item">
            <span><?= e($label) ?> <?= icon('arrow') ?></span><strong><?= (int)($stats[$key] ?? 0) ?></strong><small><?= e($description) ?></small>
        </a><?php endforeach; ?>
    </div>
</section>
<section class="workspace-panel" aria-labelledby="system-overview-title">
    <div class="workspace-panel-heading"><h2 id="system-overview-title">Tình hình nền tảng</h2><a href="<?= e(url('/admin/coupons')) ?>">Quản lý coupon <?= icon('arrow') ?></a></div>
    <dl class="workspace-metrics">
        <?php foreach ([['Người dùng','users'],['Tài khoản bị khóa','locked_users'],['Cửa hàng đã duyệt','approved_merchants'],['Coupon hoạt động','active_coupons'],['Chiến dịch đã xuất bản','published_campaigns']] as [$label,$key]): ?>
        <div><dt><?= e($label) ?></dt><dd><?= (int)($stats[$key] ?? 0) ?></dd></div><?php endforeach; ?>
    </dl>
</section>

<?php
// Navigation is scoped to the current workspace; authorization remains in the router.
$workspaceLinks = $workspaceArea === 'admin' ? [
    ['/admin', 'Tổng quan', 'grid'],
    ['/admin/coupons', 'Kiểm duyệt coupon', 'ticket'],
    ['/admin/reports', 'Báo cáo vi phạm', 'flag'],
    ['/admin/merchant-requests', 'Yêu cầu quyền shop', 'users'],
    ['/admin/merchants', 'Cửa hàng', 'store'],
    ['/admin/users', 'Người dùng', 'users'],
    ['/admin/campaigns', 'Chiến dịch', 'clock'],
    ['/admin/categories', 'Danh mục', 'grid'],
    ['/admin/articles', 'Bài viết', 'grid'],
] : [
    ['/merchant', 'Tổng quan', 'grid'],
    ['/merchant/coupons', 'Coupon cửa hàng', 'ticket'],
    ['/merchant/campaigns', 'Chiến dịch', 'clock'],
];
?>
<aside class="workspace-nav">
    <details class="workspace-menu" data-workspace-menu open>
        <summary><?= $workspaceArea === 'admin' ? 'Trung tâm quản trị' : 'Không gian đối tác' ?><span aria-hidden="true">⌄</span></summary>
        <nav aria-label="<?= $workspaceArea === 'admin' ? 'Điều hướng quản trị' : 'Điều hướng cửa hàng' ?>">
            <?php foreach ($workspaceLinks as [$workspaceHref, $workspaceLabel, $workspaceIcon]):
                $workspaceActive = $currentPath === $workspaceHref || ($workspaceHref !== '/'.$workspaceArea && str_starts_with($currentPath, $workspaceHref.'/'));
            ?><a href="<?= e(url($workspaceHref)) ?>" <?= $workspaceActive ? 'aria-current="page"' : '' ?>><?= icon($workspaceIcon) ?><span><?= e($workspaceLabel) ?></span></a><?php endforeach; ?>
        </nav>
    </details>
    <a class="workspace-return" href="<?= e(url('/')) ?>"><?= icon('arrow') ?> Xem trang người dùng</a>
</aside>

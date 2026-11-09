<?php /* Dùng chung điều hướng workspace; quyền thao tác vẫn kiểm tra tại controller/repository. */ ?><header class="workspace-header">
    <a class="brand" href="<?= e(url('/'.$workspaceArea)) ?>" aria-label="CouponHub - <?= $workspaceArea==='admin' ? 'Quản trị' : 'Đối tác' ?>"><span class="brand-mark"><span>C</span></span><span class="brand-word">Coupon<em>Hub</em><span class="brand-period">.</span></span></a>
    <span class="workspace-header-label"><?= $workspaceArea==='admin' ? 'Quản trị hệ thống' : 'Không gian đối tác' ?></span>
    <div class="workspace-header-account">
        <?php if ($workspaceArea==='merchant'): ?><a class="workspace-preview-link" href="<?= e(url('/')) ?>">Xem trang người dùng <?= icon('arrow') ?></a><?php endif; ?>
        <?php require __DIR__.'/notifications.php'; require __DIR__.'/account-menu.php'; ?>
    </div>
</header>

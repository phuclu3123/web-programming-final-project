<?php

use App\Core\Session; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Official Coupons</h1>
        <p class="text-muted mb-0">Coupon của Merchant vẫn phải qua Admin duyệt trước khi public.</p>
    </div><a class="btn btn-primary" href="<?= e(url('/merchant/coupons/create')) ?>"><i
            class="fa-solid fa-plus mr-1"></i>Tạo Official Coupon</a>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead class="thead-light">
                <tr>
                    <th>Coupon</th>
                    <th>Merchant</th>
                    <th>Thời hạn</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($coupons === []): ?><tr>
                        <td colspan="5" class="text-center text-muted py-4">Chưa có Official Coupon.</td>
                    </tr><?php endif; ?>
                <?php foreach ($coupons as $c): ?><tr>
                        <td><strong><?= e((string)$c['title']) ?></strong>
                            <div class="small text-muted"><?= e((string)($c['code'] ?? 'Không có mã')) ?></div>
                        </td>
                        <td><?= e((string)$c['merchant_name']) ?></td>
                        <td><?= e(format_db_datetime((string)$c['starts_at'])) ?><br><span class="text-muted">→
                                <?= e(format_db_datetime((string)$c['ends_at'])) ?></span></td>
                        <td><span
                                class="badge badge-<?= e(coupon_status_class((string)$c['effective_status'])) ?>"><?= e(coupon_status_label((string)$c['effective_status'])) ?></span>
                        </td>
                        <td class="text-right"><a class="btn btn-sm btn-outline-primary"
                                href="<?= e(url('/merchant/coupons/' . (int)$c['id'] . '/edit')) ?>">Sửa</a><?php if ((string)$c['publication_status'] !== 'DISABLED'): ?>
                                <form class="d-inline" method="post"
                                    action="<?= e(url('/merchant/coupons/' . (int)$c['id'] . '/disable')) ?>"><input
                                        type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><button
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Ngừng coupon này?')">Ngừng</button></form><?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
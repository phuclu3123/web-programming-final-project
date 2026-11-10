<?php
// Hiển thị chiến dịch quản trị và biểu mẫu tạo; không suy quyền từ các nút đang thấy.

use App\Core\Session; ?>
<div class="d-flex justify-content-end mb-3"><a class="btn btn-outline-primary" href="<?= e(url('/admin/integrations/campaigns')) ?>">Đồng bộ chiến dịch ACCESSTRADE <?= icon('arrow') ?></a></div>
<div class="row">
    <div class="col-xl-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h5">Chiến dịch chung</h1>
                <p class="small text-muted">Tập hợp ưu đãi từ nhiều cửa hàng. Tạm tắt chỉ ẩn chiến dịch; coupon còn hiệu lực ở nơi khác. Thu hồi từng mã tại kho coupon.</p>
                <form method="post" action="<?= e(url('/admin/campaigns')) ?>"><input type="hidden" name="_token"
                        value="<?= e(Session::csrfToken()) ?>">
                    <div class="mb-3"><label>Tên</label><input aria-label="Tên chiến dịch" class="form-control" name="name" maxlength="200"
                            required></div>
                    <div class="mb-3"><label>Mô tả</label><textarea aria-label="Mô tả chiến dịch" class="form-control" name="description"
                            rows="3"></textarea></div>
                    <?php $bannerValue=''; require __DIR__.'/../../partials/campaign-banner-field.php'; ?>
                    <div class="mb-3"><label>Bắt đầu</label><input aria-label="Bắt đầu" type="datetime-local" class="form-control"
                            name="starts_at" required></div>
                    <div class="mb-3"><label>Kết thúc</label><input aria-label="Kết thúc" type="datetime-local" class="form-control"
                            name="ends_at" required></div>
                    <div class="mb-3"><label>Trạng thái</label><select aria-label="Trạng thái" name="status" class="form-control">
                            <option value="DRAFT">Bản nháp</option>
                            <option value="PUBLISHED">Xuất bản</option>
                        </select></div><button class="btn btn-primary">Tạo Campaign</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8 mb-4">
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Campaign</th>
                            <th>Owner</th>
                            <th>Thời gian</th>
                            <th>Coupon</th>
                            <th>Trạng thái</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody><?php foreach ($campaigns as $c): ?><tr>
                                <td><strong><?= e((string)$c['name']) ?></strong>
                                    <div class="small text-muted"><?= e((string)$c['creator_name']) ?></div>
                                </td>
                                <td><?= e((string)($c['merchant_name'] ?? 'Nền tảng')) ?></td>
                                <td><?= e(format_db_datetime((string)$c['starts_at'])) ?><br>→
                                    <?= e(format_db_datetime((string)$c['ends_at'])) ?></td>
                                <td><?= (int)$c['coupon_count'] ?></td>
                                <td><span
                                        class="badge bg-<?= e(campaign_status_class((string)$c['status'])) ?>"><?= e(campaign_status_label((string)$c['status'])) ?></span>
                                </td>
                                <td><a class="btn btn-sm btn-outline-secondary w-100 mb-1"
                                        href="<?= e(url('/admin/campaigns/' . (int)$c['id'])) ?>">Quản lý coupon</a>
                                    <form method="post"
                                        action="<?= e(url('/admin/campaigns/' . (int)$c['id'] . '/status')) ?>">
                                        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><select aria-label="Trạng thái"
                                            class="form-control form-control-sm mb-1" name="status">
                                            <?php foreach (['DRAFT'=>'Bản nháp','PUBLISHED'=>'Xuất bản','DISABLED'=>'Tạm tắt','ARCHIVED'=>'Lưu trữ vĩnh viễn'] as $state=>$label): ?><option value="<?= $state ?>" <?= $c['status']===$state?'selected':'' ?> <?= $c['status']==='ARCHIVED' && $state!=='ARCHIVED'?'disabled':'' ?>><?= e($label) ?></option><?php endforeach; ?>
                                        </select><button class="btn btn-sm btn-outline-primary w-100" <?= $c['status']==='ARCHIVED'?'disabled':'' ?>>Cập nhật</button>
                                    </form>
                                </td>
                            </tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

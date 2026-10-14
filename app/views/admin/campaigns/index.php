<?php

use App\Core\Session; ?>
<div class="row">
    <div class="col-xl-4 mb-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h5">Campaign nền tảng</h1>
                <p class="small text-muted">owner_merchant_id = NULL; Admin quản lý, có thể chứa coupon từ nhiều
                    Merchant.</p>
                <form method="post" action="<?= e(url('/admin/campaigns')) ?>"><input type="hidden" name="_token"
                        value="<?= e(Session::csrfToken()) ?>">
                    <div class="form-group"><label>Tên</label><input class="form-control" name="name" maxlength="200"
                            required></div>
                    <div class="form-group"><label>Mô tả</label><textarea class="form-control" name="description"
                            rows="3"></textarea></div>
                    <div class="form-group"><label>Bắt đầu</label><input type="datetime-local" class="form-control"
                            name="starts_at" required></div>
                    <div class="form-group"><label>Kết thúc</label><input type="datetime-local" class="form-control"
                            name="ends_at" required></div>
                    <div class="form-group"><label>Trạng thái</label><select name="status" class="form-control">
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
                                        class="badge badge-<?= e(campaign_status_class((string)$c['status'])) ?>"><?= e(campaign_status_label((string)$c['status'])) ?></span>
                                </td>
                                <td><a class="btn btn-sm btn-outline-secondary btn-block mb-1"
                                        href="<?= e(url('/admin/campaigns/' . (int)$c['id'])) ?>">Quản lý coupon</a>
                                    <form method="post"
                                        action="<?= e(url('/admin/campaigns/' . (int)$c['id'] . '/status')) ?>">
                                        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>"><select
                                            class="form-control form-control-sm mb-1" name="status">
                                            <option>DRAFT</option>
                                            <option>PUBLISHED</option>
                                            <option>DISABLED</option>
                                            <option>ARCHIVED</option>
                                        </select><button class="btn btn-sm btn-outline-primary btn-block">Cập nhật</button>
                                    </form>
                                </td>
                            </tr><?php endforeach; ?></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
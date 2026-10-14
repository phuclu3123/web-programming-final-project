<?php

use App\Core\Auth;
use App\Core\Session; ?>
<div class="mb-4">
    <h1 class="h3 mb-1">Quản lý User</h1>
    <p class="text-muted mb-0">Khóa/mở khóa tài khoản; role Merchant được cập nhật theo trạng thái sở hữu Merchant.</p>
</div>
<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Điểm</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody><?php foreach ($users as $u): ?><tr>
                        <td><strong><?= e((string)$u['full_name']) ?></strong>
                            <div class="small text-muted"><?= e((string)$u['email']) ?></div>
                        </td>
                        <td><span class="badge badge-info"><?= e((string)$u['role']) ?></span></td>
                        <td><?= (int)$u['points'] ?></td>
                        <td><span
                                class="badge badge-<?= $u['status'] === 'ACTIVE' ? 'success' : 'danger' ?>"><?= e((string)$u['status']) ?></span>
                        </td>
                        <td><?= e(format_db_datetime((string)$u['created_at'])) ?></td>
                        <td class="text-right"><?php if ((int)$u['id'] !== (int)Auth::id()): ?><form method="post"
                                    action="<?= e(url('/admin/users/' . (int)$u['id'] . '/status')) ?>"><input type="hidden"
                                        name="_token" value="<?= e(Session::csrfToken()) ?>"><input type="hidden" name="status"
                                        value="<?= $u['status'] === 'ACTIVE' ? 'LOCKED' : 'ACTIVE' ?>"><button
                                        class="btn btn-sm btn-outline-<?= $u['status'] === 'ACTIVE' ? 'danger' : 'success' ?>"><?= $u['status'] === 'ACTIVE' ? 'Khóa' : 'Mở khóa' ?></button>
                                </form><?php endif; ?></td>
                    </tr><?php endforeach; ?></tbody>
        </table>
    </div>
</div>
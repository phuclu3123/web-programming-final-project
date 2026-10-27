<?php
use App\Core\Session;
$statuses = ['PENDING'=>['Chờ xác minh','warning'], 'FULFILLED'=>['Đã bàn giao','success'], 'CANCELLED'=>['Từ chối / hoàn điểm','secondary']];
?>
<div class="container py-4 py-lg-5" id="reward-exchange">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-bold text-primary mb-2">CouponPoint</div>
            <h1 class="h2 fw-bold">Đổi điểm thưởng</h1>
            <p class="text-muted">Điểm dùng để đổi thưởng được tách biệt với tổng điểm tích lũy xét Level.</p>
        </div>
        <a href="<?= e(url('/dashboard')) ?>" class="btn btn-outline-secondary">Quay lại Dashboard</a>
    </div>
    <div class="row g-3 mb-4" aria-label="Ví điểm thưởng">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Điểm tích lũy (xét Level)</div><strong
                        class="fs-3"><?= number_format((int)$wallet['earned'],0,',','.') ?></strong>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Điểm đã giữ / đã sử dụng</div><strong
                        class="fs-3"><?= number_format((int)$wallet['committed'],0,',','.') ?></strong>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body">
                    <div class="text-muted">Có thể đổi thưởng</div><strong
                        class="fs-3 text-primary"><?= number_format((int)$wallet['available'],0,',','.') ?></strong>
                </div>
            </div>
        </div>
    </div>
    <div class="alert alert-info" role="note">Đây là <strong>yêu cầu đổi thưởng</strong>, chưa phải voucher có thể sử
        dụng. Admin cần xác minh nguồn cung, bàn giao thủ công hoặc từ chối và hoàn điểm. Không có mã voucher được phát
        tự động.</div>
    <h2 class="h4 mb-3">Danh mục đổi thưởng</h2>
    <?php if (!$catalog): ?><div class="alert alert-secondary">Chưa có phần thưởng đang mở để đổi.</div><?php endif; ?>
    <div class="row g-3 mb-5">
        <?php foreach ($catalog as $item): ?>
        <div class="col-md-6">
            <article class="card h-100 border-0 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h3 class="h5"><?= e((string)$item['title']) ?></h3>
                    <p class="text-muted flex-grow-1"><?= e((string)$item['description']) ?></p>
                    <div class="d-flex align-items-center flex-wrap justify-content-between gap-2">
                        <strong><?= number_format((int)$item['cost_points'],0,',','.') ?> điểm</strong>
                        <form method="post" action="<?= e(url('/rewards/redeem')) ?>">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <input type="hidden" name="reward_id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="idempotency_key" value="<?= e(bin2hex(random_bytes(16))) ?>">
                            <button class="btn btn-primary" type="submit"
                                <?= (int)$item['cost_points'] > (int)$wallet['available'] ? 'disabled' : '' ?>>Gửi yêu
                                cầu đổi</button>
                        </form>
                    </div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <h2 class="h4 mb-3">Lịch sử đổi thưởng của tôi</h2>
    <?php if (!$history): ?><p class="text-muted">Bạn chưa gửi yêu cầu đổi thưởng nào.</p><?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th scope="col">Mã</th>
                    <th scope="col">Phần thưởng</th>
                    <th scope="col">Điểm</th>
                    <th scope="col">Trạng thái</th>
                    <th scope="col">Ghi chú Admin</th>
                    <th scope="col">Thời gian</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $row): $state=$statuses[$row['status']] ?? ['Không xác định','secondary']; ?>
                <tr>
                    <td>#<?= (int)$row['id'] ?></td>
                    <td><?= e((string)$row['reward_title']) ?></td>
                    <td>−<?= (int)$row['points_spent'] ?></td>
                    <td><span class="badge text-bg-<?= e($state[1]) ?>"><?= e($state[0]) ?></span></td>
                    <td><?= e((string)($row['admin_note'] ?? '—')) ?></td>
                    <td><?= e(format_db_datetime((string)$row['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
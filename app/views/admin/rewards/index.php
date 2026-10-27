<?php

use App\Core\Session;

$states = ['PENDING' => ['Chờ xử lý', 'warning'], 'FULFILLED' => ['Đã bàn giao', 'success'], 'CANCELLED' => ['Đã từ chối / hoàn điểm', 'secondary']];
?>
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="h3 mb-1">Duyệt yêu cầu đổi CouponPoint</h1>
        <p class="text-muted">Xác minh có voucher hoặc ưu đãi thực tế trước khi xác nhận bàn giao. Từ chối sẽ hoàn điểm
            về ví người yêu cầu.</p>
    </div>
    <a href="<?= e(url('/admin')) ?>" class="btn btn-outline-secondary">Admin Dashboard</a>
</div>
<?php if (!$requests): ?><div class="alert alert-info">Chưa có yêu cầu đổi thưởng.</div><?php endif; ?>
<?php foreach ($requests as $row): $state = $states[$row['status']] ?? ['Không rõ', 'secondary']; ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-2"><strong>Yêu cầu
                            #<?= (int)$row['id'] ?></strong><span
                            class="badge text-bg-<?= e($state[1]) ?>"><?= e($state[0]) ?></span></div>
                    <h2 class="h5"><?= e((string)$row['reward_title']) ?> — <?= (int)$row['points_spent'] ?> điểm</h2>
                    <p class="mb-1">Thành viên: <?= e((string)$row['user_name']) ?> (<?= e((string)$row['user_email']) ?>)
                    </p>
                    <p class="small text-muted">Tạo lúc <?= e(format_db_datetime((string)$row['created_at'])) ?></p>
                    <?php if ($row['status'] !== 'PENDING'): ?><p><strong>Ghi chú xử lý:</strong>
                            <?= e((string)($row['admin_note'] ?? '')) ?></p><?php endif; ?>
                </div>
                <?php if ($row['status'] === 'PENDING'): ?><div class="col-lg-5">
                        <form method="post" action="<?= e(url('/admin/rewards/' . (int)$row['id'] . '/review')) ?>">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <label for="reward-note-<?= (int)$row['id'] ?>" class="form-label">Ghi chú xác nhận / lý do từ
                                chối</label>
                            <textarea id="reward-note-<?= (int)$row['id'] ?>" name="note" rows="3" minlength="10"
                                maxlength="500" class="form-control mb-2" required
                                placeholder="Ghi chi tiết cách bàn giao voucher hoặc lý do từ chối"></textarea>
                            <div class="d-flex gap-2 flex-wrap"><button type="submit" name="action" value="FULFILLED"
                                    class="btn btn-success btn-sm">Xác nhận đã bàn giao</button>
                                <button type="submit" name="action" value="CANCELLED" class="btn btn-outline-danger btn-sm">Từ
                                    chối &amp; hoàn điểm</button>
                            </div>
                        </form>
                    </div><?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
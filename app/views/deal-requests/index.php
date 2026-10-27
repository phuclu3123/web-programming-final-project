<?php
/** @var array $listing */
$filters = ['OPEN'=>'Đang cần tìm', 'CLOSED'=>'Đã đóng', 'ALL'=>'Tất cả'];
?>
<div class="container py-4 py-lg-5" id="deal-requests">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <div class="text-uppercase small fw-bold text-primary mb-2">Cộng đồng CouponHub</div>
            <h1 class="h2 fw-bold mb-2">Nhờ cộng đồng tìm mã giảm giá</h1>
            <p class="text-muted mb-0">Đăng nhu cầu, nhận gợi ý bằng các coupon đã được kiểm duyệt và còn hiệu lực.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/deal-requests/create')) ?>">+ Đăng yêu cầu</a>
    </div>
    <nav class="d-flex gap-2 flex-wrap mb-4" aria-label="Lọc yêu cầu">
        <?php foreach ($filters as $key => $label): ?>
        <a href="<?= e(url('/deal-requests?status=' . $key)) ?>"
            class="btn btn-sm <?= $status === $key ? 'btn-primary' : 'btn-outline-secondary' ?>"
            <?= $status === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
    <?php if ($listing['items'] === []): ?>
    <div class="alert alert-info">Chưa có yêu cầu nào trong nhóm này. Hãy tạo yêu cầu đầu tiên để cộng đồng hỗ trợ.
    </div>
    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($listing['items'] as $item): ?>
        <div class="col-12">
            <article class="card h-100 border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <span
                            class="badge <?= $item['status'] === 'OPEN' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $item['status'] === 'OPEN' ? 'Đang tìm' : 'Đã đóng' ?></span>
                        <small class="text-muted"><?= e((string)$item['created_at']) ?></small>
                    </div>
                    <h2 class="h5"><a href="<?= e(url('/deal-requests/' . (int)$item['id'])) ?>"
                            class="text-decoration-none"><?= e((string)$item['title']) ?></a></h2>
                    <p class="text-muted mb-3">
                        <?= e((string)(function_exists('mb_strimwidth') ? mb_strimwidth($item['body'], 0, 230, '…', 'UTF-8') : substr((string)$item['body'], 0, 180))) ?>
                    </p>
                    <div class="small text-muted">Đăng bởi <?= e((string)$item['author_name']) ?> ·
                        <?= (int)$item['reply_count'] ?> gợi ý coupon</div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($listing['pages'] > 1): ?>
    <nav class="mt-4" aria-label="Phân trang yêu cầu">
        <ul class="pagination flex-wrap">
            <?php for ($page = max(1, $listing['page'] - 2); $page <= min($listing['pages'], $listing['page'] + 2); $page++): ?>
            <li class="page-item <?= $page === $listing['page'] ? 'active' : '' ?>"><a class="page-link"
                    href="<?= e(url('/deal-requests?status=' . $status . '&page=' . $page)) ?>"
                    <?= $page === $listing['page'] ? 'aria-current="page"' : '' ?>><?= $page ?></a></li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
    <?php endif; ?>
</div>
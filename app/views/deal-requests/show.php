<?php
use App\Core\Auth;
use App\Core\Session;
$actor = Auth::user();
$isOwner = $actor !== null && (int)$actor['id'] === (int)$dealRequest['user_id'];
$isOpen = $dealRequest['status'] === 'OPEN';
$canReply = $actor !== null && in_array($actor['role'], ['USER','MERCHANT'], true) && !$isOwner && $isOpen;
?>
<div class="container py-4 py-lg-5">
    <nav aria-label="Đường dẫn" class="small mb-3"><a href="<?= e(url('/deal-requests')) ?>">Cộng đồng tìm mã</a> / Yêu
        cầu #<?= (int)$dealRequest['id'] ?></nav>
    <div class="row g-4">
        <div class="col-lg-8">
            <article class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                        <span
                            class="badge <?= $isOpen ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $isOpen ? 'Đang tìm coupon' : 'Đã đóng' ?></span>
                        <small class="text-muted"><?= e((string)$dealRequest['created_at']) ?></small>
                    </div>
                    <h1 class="h3 fw-bold mb-3"><?= e((string)$dealRequest['title']) ?></h1>
                    <p class="mb-3" style="white-space:pre-wrap;overflow-wrap:anywhere">
                        <?= e((string)$dealRequest['body']) ?></p>
                    <div class="small text-muted">Người đăng: <?= e((string)$dealRequest['author_name']) ?></div>
                    <?php if ($isOwner && $isOpen): ?>
                    <form method="post" action="<?= e(url('/deal-requests/' . (int)$dealRequest['id'] . '/close')) ?>"
                        class="mt-3">
                        <input type="hidden" name="csrf" value="<?= e(Session::csrfToken()) ?>">
                        <button class="btn btn-outline-danger btn-sm" type="submit">Đã tìm được coupon — Đóng yêu
                            cầu</button>
                    </form>
                    <?php endif; ?>
                </div>
            </article>
            <section id="answers" aria-label="Gợi ý coupon">
                <h2 class="h4 fw-bold mb-3">Gợi ý từ cộng đồng (<?= count($replies) ?>)</h2>
                <?php if (!$replies): ?>
                <div class="alert alert-light border">Chưa có coupon nào được đề xuất cho yêu cầu này.</div>
                <?php else: ?>
                <?php foreach ($replies as $reply): ?>
                <article class="card mb-3">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <h3 class="h6 mb-1"><a
                                    href="<?= e(url('/coupons/' . (int)$reply['coupon_id'])) ?>"><?= e((string)$reply['coupon_title']) ?></a>
                            </h3>
                            <span
                                class="badge <?= $reply['effective_status'] === 'ACTIVE' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= $reply['effective_status'] === 'ACTIVE' ? 'Đang hoạt động' : 'Không còn hoạt động' ?></span>
                        </div>
                        <div class="small text-muted mb-2"><?= e((string)$reply['merchant_name']) ?> ·
                            <?= e((string)$reply['responder_name']) ?> · <?= e((string)$reply['created_at']) ?></div>
                        <?php if ($reply['note'] !== ''): ?><p class="mb-0"
                            style="white-space:pre-wrap;overflow-wrap:anywhere"><?= e((string)$reply['note']) ?></p>
                        <?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </div>
        <aside class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3">Đề xuất coupon</h2>
                    <?php if ($canReply): ?>
                    <p class="small text-muted">Chỉ đề xuất coupon đã được duyệt và còn hiệu lực. Tìm mã trong <a
                            href="<?= e(url('/coupons')) ?>">danh sách coupon</a>, rồi nhập ID trong đường dẫn
                        `/coupons/34` (ví dụ: 34).</p>
                    <form action="<?= e(url('/deal-requests/' . (int)$dealRequest['id'] . '/reply')) ?>" method="post">
                        <input type="hidden" name="csrf" value="<?= e(Session::csrfToken()) ?>">
                        <div class="mb-3"><label class="form-label" for="dr-coupon">ID coupon</label>
                            <input type="number" min="1" step="1" required class="form-control" name="coupon_id"
                                id="dr-coupon" placeholder="Ví dụ: 34">
                        </div>
                        <div class="mb-3"><label class="form-label" for="dr-note">Lời nhắn (không bắt buộc)</label>
                            <textarea class="form-control" name="note" id="dr-note" rows="3" maxlength="500"
                                placeholder="Điều kiện áp dụng hoặc lý do phù hợp"></textarea>
                        </div>
                        <button class="btn btn-primary" type="submit">Gửi gợi ý coupon</button>
                    </form>
                    <?php elseif (!$actor): ?>
                    <p class="text-muted">Đăng nhập để gửi gợi ý coupon cho thành viên khác.</p><a
                        class="btn btn-outline-primary"
                        href="<?= e(url('/login?return=' . rawurlencode('/deal-requests/' . (int)$dealRequest['id']))) ?>">Đăng
                        nhập</a>
                    <?php elseif ($isOwner): ?>
                    <p class="text-muted mb-0">Đây là yêu cầu của bạn. Thành viên khác có thể đề xuất coupon.</p>
                    <?php else: ?>
                    <p class="text-muted mb-0">Yêu cầu đã đóng hoặc tài khoản không có quyền trả lời.</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>
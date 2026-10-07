<?php
use App\Core\Auth;
use App\Core\Session;

$isLoggedIn = Auth::check();
$isAuthor = $isLoggedIn && (int) Auth::id() === (int) $coupon['submitted_by'];
$saved = (bool) ($interactionState['saved'] ?? false);
$currentVote = $interactionState['vote'] ?? null;
$stats = $interactionState['stats'] ?? [];
$comments = $interactionState['comments'] ?? [];
$pageScripts = ['/js/coupon-detail.js'];
?>
<div id="coupon-detail-page"
     data-coupon-id="<?= (int) $coupon['id'] ?>"
     data-favorite-url="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/favorite')) ?>"
     data-vote-url="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/vote')) ?>"
     data-comment-url="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/comments')) ?>"
     data-report-url="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/reports')) ?>">

    <div id="interaction-message"></div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="mb-3 d-flex flex-wrap align-items-center" style="gap:.35rem">
                        <?php if ((int) $coupon['is_official'] === 1): ?><span class="badge badge-primary">Official Deal</span><?php endif; ?>
                        <span class="badge-soft <?= e(category_badge_class((string) $coupon['category_name'])) ?>"><?= e((string) $coupon['category_name']) ?></span>
                        <span class="badge-soft <?= e(usage_badge_class((string) $coupon['usage_channel'])) ?>"><?= e(usage_channel_label((string) $coupon['usage_channel'])) ?></span>
                    </div>

                    <h1 class="h3 mb-2"><?= e((string) $coupon['title']) ?></h1>
                    <div class="text-muted mb-4">Merchant: <strong><?= e((string) $coupon['merchant_name']) ?></strong></div>

                    <div class="coupon-discount display-4 mb-3"><?= e(format_discount($coupon)) ?></div>
                    <?php if (!empty($coupon['code'])): ?>
                        <div class="coupon-code-large mb-4">
                            <div class="small text-muted">Mã coupon</div>
                            <code id="coupon-code-value"><?= e((string) $coupon['code']) ?></code>
                            <button type="button" class="btn btn-outline-secondary btn-sm ml-2" id="copy-coupon-code" data-code="<?= e((string) $coupon['code']) ?>">Sao chép mã</button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($coupon['description'])): ?>
                        <h2 class="h5">Mô tả</h2>
                        <p><?= nl2br(e((string) $coupon['description'])) ?></p>
                    <?php endif; ?>

                    <h2 class="h5">Điều kiện sử dụng</h2>
                    <p><?= nl2br(e((string) $coupon['terms'])) ?></p>

                    <div class="interaction-toolbar mt-4">
                        <?php if (!empty($coupon['promotion_url'])): ?>
                            <a class="btn btn-primary" href="<?= e((string) $coupon['promotion_url']) ?>" target="_blank" rel="noopener noreferrer">Mở trang khuyến mãi</a>
                        <?php endif; ?>

                        <?php if ($isLoggedIn): ?>
                            <button type="button" id="favorite-btn" class="btn <?= $saved ? 'btn-warning' : 'btn-outline-warning' ?>" data-saved="<?= $saved ? '1' : '0' ?>">
                                <?= $saved ? '★ Đã lưu' : '☆ Lưu coupon' ?>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-toggle="modal" data-target="#reportCouponModal">Báo cáo</button>
                        <?php else: ?>
                            <a class="btn btn-outline-secondary" href="<?= e(url('/login')) ?>">Đăng nhập để lưu / xác nhận</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h5 mb-1">Bình luận cộng đồng</h2>
                            <div class="small text-muted">Chia sẻ trải nghiệm hoặc lưu ý về điều kiện sử dụng coupon.</div>
                        </div>
                        <span class="badge badge-light border" id="comment-count"><?= count($comments) ?> bình luận</span>
                    </div>

                    <?php if ($isLoggedIn): ?>
                        <form id="comment-form" class="mb-3" method="post" action="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/comments')) ?>">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <div class="form-group mb-2">
                                <textarea class="form-control" id="comment-body" name="body" rows="3" maxlength="3000" placeholder="Viết bình luận..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Gửi bình luận</button>
                            <span class="small text-muted ml-2"><span id="comment-char-count">0</span>/3000</span>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-light border small">Bạn cần <a href="<?= e(url('/login')) ?>">đăng nhập</a> để bình luận.</div>
                    <?php endif; ?>

                    <div id="comment-list">
                        <?php if ($comments === []): ?>
                            <div id="comment-empty" class="text-muted small py-3">Chưa có bình luận nào.</div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item" data-comment-id="<?= (int) $comment['id'] ?>">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= e((string) $comment['full_name']) ?></strong>
                                        <span class="small text-muted"><?= e(format_db_datetime((string) $comment['created_at'])) ?></span>
                                    </div>
                                    <div class="comment-body mt-1"><?= nl2br(e((string) $comment['body'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Thông tin coupon</h2>
                    <dl class="mb-0">
                        <dt>Bắt đầu</dt><dd><?= e(format_db_datetime((string) $coupon['starts_at'])) ?></dd>
                        <dt>Hết hạn</dt><dd><?= e(format_db_datetime((string) $coupon['ends_at'])) ?></dd>
                        <dt>Đơn tối thiểu</dt><dd><?= e(format_money($coupon['min_order_amount'])) ?></dd>
                        <dt>Giảm tối đa</dt><dd><?= e(format_money($coupon['max_discount_amount'])) ?></dd>
                        <dt>Người đóng góp</dt><dd><?= e((string) $coupon['submitter_name']) ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Community Verification</h2>
                    <div id="trust-score" class="trust-score-value"><?= ($stats['trustScore'] ?? null) === null ? '—' : e((string) $stats['trustScore']) . '%' ?></div>
                    <div class="small text-muted mb-3"><span id="total-votes"><?= (int) ($stats['totalVotes'] ?? 0) ?></span> lượt xác nhận</div>
                    <ul class="list-unstyled small mb-3">
                        <li>Dùng được: <strong id="working-count"><?= (int) ($stats['workingCount'] ?? 0) ?></strong></li>
                        <li>Không dùng được: <strong id="not-working-count"><?= (int) ($stats['notWorkingCount'] ?? 0) ?></strong></li>
                        <li>Điều kiện thay đổi: <strong id="condition-changed-count"><?= (int) ($stats['conditionChangedCount'] ?? 0) ?></strong></li>
                        <li>Đang được lưu: <strong id="favorite-count"><?= (int) ($stats['favoriteCount'] ?? 0) ?></strong></li>
                    </ul>

                    <?php if ($isLoggedIn && !$isAuthor): ?>
                        <div class="small font-weight-bold mb-2">Coupon này có dùng được không?</div>
                        <div class="d-flex flex-column">
                            <button type="button" class="btn btn-sm btn-outline-success vote-btn mb-2 <?= $currentVote === 'WORKING' ? 'active' : '' ?>" data-vote="WORKING">Dùng được</button>
                            <button type="button" class="btn btn-sm btn-outline-danger vote-btn mb-2 <?= $currentVote === 'NOT_WORKING' ? 'active' : '' ?>" data-vote="NOT_WORKING">Không dùng được</button>
                            <button type="button" class="btn btn-sm btn-outline-warning vote-btn <?= $currentVote === 'CONDITION_CHANGED' ? 'active' : '' ?>" data-vote="CONDITION_CHANGED">Điều kiện thay đổi</button>
                        </div>
                    <?php elseif ($isAuthor): ?>
                        <div class="alert alert-light border small mb-0">Bạn là người đóng góp coupon này nên không thể tự xác nhận.</div>
                    <?php else: ?>
                        <div class="small text-muted">Đăng nhập để tham gia xác minh cộng đồng.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isLoggedIn): ?>
        <div class="modal fade" id="reportCouponModal" tabindex="-1" aria-labelledby="reportCouponModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="report-form" method="post" action="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/reports')) ?>">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reportCouponModalLabel">Báo cáo coupon</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Đóng"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <div class="form-group">
                                <label for="report-reason">Lý do</label>
                                <select class="form-control" id="report-reason" name="reason" required>
                                    <option value="NOT_WORKING">Coupon không hoạt động</option>
                                    <option value="EXPIRED">Coupon đã hết hạn</option>
                                    <option value="WRONG_TERMS">Sai điều kiện sử dụng</option>
                                    <option value="DUPLICATE">Coupon bị trùng</option>
                                    <option value="SPAM">Spam</option>
                                    <option value="FAKE">Thông tin giả</option>
                                    <option value="INAPPROPRIATE">Nội dung không phù hợp</option>
                                    <option value="OTHER">Khác</option>
                                </select>
                            </div>
                            <div class="form-group mb-0">
                                <label for="report-details">Chi tiết</label>
                                <textarea class="form-control" id="report-details" name="details" rows="4" maxlength="2000" placeholder="Mô tả để Admin kiểm tra nhanh hơn..."></textarea>
                                <small class="form-text text-muted">Bắt buộc khi chọn “Khác”.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-danger">Gửi báo cáo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>


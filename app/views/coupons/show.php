<?php

use App\Core\Auth;
use App\Core\Session;

$isLoggedIn = Auth::check();
$isAuthor = $isLoggedIn && (int) Auth::id() === (int) $coupon['submitted_by'];
$voteRestriction = $interactionState['voteRestriction'] ?? ($isAuthor ? 'AUTHOR' : null);
$saved = (bool) ($interactionState['saved'] ?? false);
$currentVote = $interactionState['vote'] ?? null;
$stats = $interactionState['stats'] ?? [];
$comments = $interactionState['comments'] ?? [];
$pageScripts = ['/js/coupon-detail.js'];
$promotionUrl = safeHttpUrl($coupon['promotion_url'] ?? null);
?>
<div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><a href="<?= e(url('/coupons')) ?>">Coupon</a><span>/</span><span><?= e($coupon['merchant_name']) ?></span></div>
<div class="discovery-detail-page coupon-reading" id="coupon-detail-page" data-coupon-id="<?= (int) $coupon['id'] ?>"
    data-favorite-url="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/favorite')) ?>"
    data-vote-url="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/vote')) ?>"
    data-comment-url="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/comments')) ?>"
    data-report-url="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/reports')) ?>">

    <div id="interaction-message"></div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card discovery-offer shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="offer-brand"><?= brandLogo($coupon['merchant_name']) ?><div><a href="<?= e(url('/coupons').'?merchant_id='.(int)$coupon['merchant_id']) ?>"><?= e($coupon['merchant_name']) ?></a><span><?= (int)$coupon['is_official']===1 ? 'Ưu đãi từ cửa hàng' : 'Được cộng đồng chia sẻ' ?></span></div></div>
                    <div class="mb-3 d-flex flex-wrap align-items-center" style="gap:.35rem">
                        <?php if (str_contains($coupon['title'],'[DEMO]')): ?><span class="badge-soft">Ưu đãi minh họa</span><?php endif; ?>
                        <span
                            class="badge-soft <?= e(category_badge_class((string) $coupon['category_name'])) ?>"><?= e((string) $coupon['category_name']) ?></span>
                        <span
                            class="badge-soft <?= e(usage_badge_class((string) $coupon['usage_channel'])) ?>"><?= e(usage_channel_label((string) $coupon['usage_channel'])) ?></span>
                    </div>

                    <h1 class="h3 mb-2"><?= e(editorialTitle($coupon['title'])) ?></h1>

                    <div class="offer-value"><span>MỨC ƯU ĐÃI</span><strong><?= e(saving($coupon)) ?></strong></div>
                    <dl class="offer-conditions"><div><dt>Đơn tối thiểu</dt><dd><?= (float)$coupon['min_order_amount']>0 ? e(money($coupon['min_order_amount'])) : 'Không yêu cầu' ?></dd></div><div><dt>Giảm tối đa</dt><dd><?= $coupon['max_discount_amount']!==null ? e(money($coupon['max_discount_amount'])) : 'Theo điều kiện ưu đãi' ?></dd></div><div><dt>Kênh sử dụng</dt><dd><?= e(usage_channel_label($coupon['usage_channel'])) ?></dd></div><div><dt>Hạn dùng</dt><dd><?= e(format_db_datetime($coupon['ends_at'],'d/m/Y')) ?></dd></div></dl>


                    <?php if (!empty($coupon['code'])): ?>
                        <div class="coupon-code-large mb-4">
                            <div class="small text-muted">Mã coupon</div>
                            <code id="coupon-code-value"><?= e((string) $coupon['code']) ?></code>
                            <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="copy-coupon-code" data-coupon-id="<?= (int)$coupon['id'] ?>"
                                data-code="<?= e((string) $coupon['code']) ?>">Sao chép mã</button>
                        </div>
                    <?php endif; ?>
                    <p class="redemption-note">Kiểm tra lại mức giảm tại bước thanh toán trước khi xác nhận đơn.</p>

                    <?php if (!empty($coupon['description'])): ?>
                        <h2 class="h5">Mô tả</h2>
                        <p><?= nl2br(e((string) $coupon['description'])) ?></p>
                    <?php endif; ?>

                    <h2 class="h5">Điều kiện sử dụng</h2>
                    <p><?= nl2br(e((string) $coupon['terms'])) ?></p>

                    <div class="interaction-toolbar mt-4">
                        <button type="button" id="share-coupon-link" class="btn btn-outline-primary"
                            data-coupon-id="<?= (int) $coupon['id'] ?>"
                            data-share-url="<?= e(url('/coupons/' . (int) $coupon['id'])) ?>">Chia sẻ liên kết</button>
                        <?php if ($promotionUrl !== null): ?>
                            <a class="btn btn-primary" href="<?= e($promotionUrl) ?>" target="_blank"
                                rel="noopener noreferrer">Mở trang khuyến mãi</a>
                        <?php endif; ?>

                        <?php if ($isLoggedIn): ?>
                            <button type="button" id="favorite-btn"
                                class="btn <?= $saved ? 'btn-warning' : 'btn-outline-warning' ?>"
                                data-saved="<?= $saved ? '1' : '0' ?>">
                                <?= $saved ? '★ Đã lưu' : '☆ Lưu coupon' ?>
                            </button>
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                                data-bs-target="#reportCouponModal">Báo cáo</button>
                        <?php else: ?>
                            <a class="btn btn-outline-secondary" href="<?= e(url('/login').'?return='.rawurlencode(url('/coupons/'.(int)$coupon['id']))) ?>">Đăng nhập để lưu / xác
                                nhận</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4 discovery-sidebar">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Thời hạn &amp; nguồn</h2>


                    <dl class="mb-0">
                        <dt>Bắt đầu</dt>
                        <dd><?= e(format_db_datetime((string) $coupon['starts_at'])) ?></dd>
                        <dt>Hết hạn</dt>
                        <dd><?= e(format_db_datetime((string) $coupon['ends_at'])) ?></dd>
                        <dt>Người đóng góp</dt>
                        <dd><?= e((string) $coupon['submitter_name']) ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="h5">Xác minh từ cộng đồng</h2>
                    <div id="trust-score" class="trust-score-value">
                        <?= ($stats['trustScore'] ?? null) === null ? 'Chưa có xác nhận' : e((string) $stats['trustScore']) . '%' ?>
                    </div>
                    <div class="small text-muted mb-3"><span
                            id="total-votes"><?= (int) ($stats['totalVotes'] ?? 0) ?></span> lượt xác nhận</div>
                    <details class="detail-signals"><summary>Chi tiết lượt tương tác</summary><ul class="list-unstyled small mb-3">
                        <li>Dùng được: <strong id="working-count"><?= (int) ($stats['workingCount'] ?? 0) ?></strong>
                        </li>
                        <li>Không dùng được: <strong
                                id="not-working-count"><?= (int) ($stats['notWorkingCount'] ?? 0) ?></strong></li>
                        <li>Điều kiện thay đổi: <strong
                                id="condition-changed-count"><?= (int) ($stats['conditionChangedCount'] ?? 0) ?></strong>
                        </li>
                        <li>Tổng lượt lưu: <strong
                                id="favorite-count"><?= (int) ($stats['favoriteCount'] ?? 0) ?></strong></li>
                        <li>Lượt lưu hợp lệ: <strong
                                id="qualified-favorite-count"><?= (int)($stats['qualifiedFavorites'] ?? 0) ?></strong>
                        </li>
                    </ul></details>

                    <?php if ($isLoggedIn && $voteRestriction === null): ?>
                        <div class="small fw-bold mb-2">Coupon này có dùng được không?</div>
                        <div class="d-flex flex-column">
                            <button type="button"
                                class="btn btn-sm btn-outline-success vote-btn mb-2 <?= $currentVote === 'WORKING' ? 'active' : '' ?>"
                                data-vote="WORKING">Dùng được</button>
                            <button type="button"
                                class="btn btn-sm btn-outline-danger vote-btn mb-2 <?= $currentVote === 'NOT_WORKING' ? 'active' : '' ?>"
                                data-vote="NOT_WORKING">Không dùng được</button>
                            <button type="button"
                                class="btn btn-sm btn-outline-warning vote-btn <?= $currentVote === 'CONDITION_CHANGED' ? 'active' : '' ?>"
                                data-vote="CONDITION_CHANGED">Điều kiện thay đổi</button>
                        </div>
                    <?php elseif ($isLoggedIn && $voteRestriction === 'AUTHOR'): ?>
                        <div class="alert alert-light border small mb-0" role="status">Bạn là người đóng góp coupon này nên
                            không thể tự xác nhận.</div>
                    <?php elseif ($isLoggedIn && $voteRestriction === 'SHOP_MANAGER'): ?>
                        <div class="alert alert-light border small mb-0" role="status">
                            <i class="fas fa-shield-halved me-1" aria-hidden="true"></i>
                            Bạn đang quản lý Shop phát hành coupon này nên không thể tham gia xác minh cộng đồng.
                        </div>
                    <?php else: ?>
                        <div class="small text-muted">Đăng nhập để tham gia xác minh cộng đồng.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<div class="card shadow-sm mb-4 coupon-comments">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h2 class="h5 mb-1">Bình luận cộng đồng</h2>
                            <div class="small text-muted">Chia sẻ trải nghiệm hoặc lưu ý về điều kiện sử dụng coupon.
                            </div>
                        </div>
                        <span class="badge bg-light text-dark border" id="comment-count"><?= count($comments) ?> bình
                            luận</span>
                    </div>

                    <?php if ($isLoggedIn): ?>
                        <form id="comment-form" class="mb-3" method="post"
                            action="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/comments')) ?>">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <div class="mb-3 mb-2">
                                <textarea class="form-control" id="comment-body" name="body" rows="3" maxlength="3000" aria-label="Nội dung bình luận"
                                    placeholder="Viết bình luận..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Gửi bình luận</button>
                            <span class="small text-muted ms-2"><span id="comment-char-count">0</span>/3000</span>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-light border small">Bạn cần <a href="<?= e(url('/login')) ?>">đăng nhập</a>
                            để bình luận.</div>
                    <?php endif; ?>

                    <div id="comment-list">
                        <?php if ($comments === []): ?>
                            <div id="comment-empty" class="text-muted small py-3">Chưa có bình luận nào.</div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item" data-comment-id="<?= (int) $comment['id'] ?>">
                                    <div class="d-flex justify-content-between">
                                        <strong><?= e((string) $comment['full_name']) ?></strong>
                                        <span
                                            class="small text-muted"><?= e(format_db_datetime((string) $comment['created_at'])) ?></span>
                                    </div>
                                    <div class="comment-body mt-1"><?= nl2br(e((string) $comment['body'])) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

    <?php if ($isLoggedIn): ?>
        <div class="modal fade" id="reportCouponModal" tabindex="-1" aria-labelledby="reportCouponModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="report-form" method="post"
                        action="<?= e(url('/api/coupons/' . (int) $coupon['id'] . '/reports')) ?>">
                        <div class="modal-header">
                            <h5 class="modal-title" id="reportCouponModalLabel">Báo cáo coupon</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <div class="mb-3">
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
                            <div class="mb-3 mb-0">
                                <label for="report-details">Chi tiết</label>
                                <textarea class="form-control" id="report-details" name="details" rows="4" maxlength="2000"
                                    placeholder="Mô tả để Admin kiểm tra nhanh hơn..."></textarea>
                                <small class="form-text text-muted">Bắt buộc khi chọn “Khác”.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-danger">Gửi báo cáo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$points = (int) ($rewardSummary['earned_points'] ?? 0);
$nextPoints = $rewardSummary['next_level_points'] ?? null;
$levelStart = (int) ($rewardSummary['level_min_points'] ?? 0);
$progress = \App\Services\LevelProgressService::percentage($points, $levelStart, $nextPoints !== null ? (int) $nextPoints : null);
$memberRole = ['USER' => 'Thành viên cộng đồng', 'MERCHANT' => 'Đối tác cửa hàng', 'ADMIN' => 'Quản trị viên'][$actor['role']] ?? 'Thành viên';
$canContribute = in_array($actor['role'], ['USER', 'MERCHANT'], true);
$sectionLinks = [['account-overview', 'Tổng quan'], ['account-saved', 'Coupon đã lưu'], ['account-rewards', 'Điểm & hoạt động'], ['account-next', 'Khám phá thêm']];
?>
<div class="member-dashboard">
    <div class="member-breadcrumb"><a href="<?= e(url('/')) ?>">CouponHub</a><span>/</span><span>Không gian của
            bạn</span></div>
    <div class="member-page-heading">
        <div><span class="eyebrow">MỘT GÓC RIÊNG CHO BẠN</span>
            <h1>Ưu đãi hay. Lưu ở đây.</h1>
            <p>Giữ lại mã yêu thích, theo dõi đóng góp và tích lũy CouponPoint.</p>
        </div>
        <a class="btn btn-brand" href="<?= e(url('/coupons')) ?>">Khám phá ưu đãi <?= icon('arrow') ?></a>
    </div>
    <nav class="member-inline-nav" aria-label="Các phần tài khoản" data-section-rail>
        <?php foreach ($sectionLinks as [$id, $label]): ?><a
                href="#<?= e($id) ?>"><?= e($label) ?></a><?php endforeach; ?>
    </nav>
    <section id="account-overview" aria-label="Tổng quan tài khoản">
        <div class="member-overview">
            <article class="member-profile">
                <div class="member-profile-top"><span class="account-avatar"
                        aria-hidden="true"><?= e(accountInitials($actor['full_name'])) ?></span>
                    <div>
                        <h2><?= e($actor['full_name']) ?></h2><span class="member-role"><?= e($memberRole) ?></span>
                    </div>
                </div>
                <dl>
                    <div>
                        <dt>Email tài khoản</dt>
                        <dd><?= e($actor['email']) ?></dd>
                    </div>
                    <div>
                        <dt>Trạng thái</dt>
                        <dd>Đang hoạt động</dd>
                    </div>
                </dl><a class="btn btn-outline-brand" href="<?= e(url('/account')) ?>">Chỉnh sửa thông tin cá nhân</a>
            </article>
            <article class="member-points" aria-label="Số dư CouponPoint">
                <div class="member-points-top">
                    <strong>COUPONPOINT</strong><span><?= e($rewardSummary['level_name'] ?? 'Thành viên mới') ?></span>
                </div>
                <div class="member-points-value"><strong><?= number_format($points, 0, ',', '.') ?></strong><span>điểm
                        tích
                        lũy</span></div>
                <?php if ($nextPoints !== null): ?><p>Còn <b><?= max(0, (int)$nextPoints - $points) ?> điểm</b> để lên
                        <?= e($rewardSummary['next_level_name'] ?? 'cấp tiếp theo') ?>.</p><?php else: ?><p>Bạn đang ở cấp
                        cao nhất hiện tại.</p><?php endif; ?>
                <div class="member-progress" role="progressbar" aria-label="Tiến độ trong cấp hiện tại"
                    aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"><span
                        style="width:<?= $progress ?>%"></span></div>
                <a href="#account-rewards">Xem lịch sử tích điểm <?= icon('arrow') ?></a>
                <?php if ($canContribute): ?><a href="<?= e(url('/rewards')) ?>" class="mt-2 d-inline-block">Đổi
                        CouponPoint →</a><?php endif; ?>
            </article>
        </div>
        <div class="member-stats" aria-label="Thống kê tài khoản">
            <?php if ($canContribute): ?><a class="member-stat" href="<?= e(url('/saved-coupons')) ?>"><span>Coupon đã
                        lưu</span><strong><?= (int)$savedCount ?></strong><?= icon('ticket') ?></a><?php else: ?><div
                    class="member-stat"><span>Coupon đã
                        lưu</span><strong><?= (int)$savedCount ?></strong><?= icon('ticket') ?></div><?php endif; ?>
            <?php foreach ([['submitted_count', 'Đóng góp đã gửi', 'flag'], ['approved_count', 'Đã được duyệt', 'check'], ['rejected_count', 'Bị từ chối', 'clock']] as [$key, $label, $symbol]): ?>
                <div class="member-stat">
                    <span><?= e($label) ?></span><strong><?= (int)($rewardSummary[$key] ?? 0) ?></strong><?= icon($symbol) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section id="account-saved" class="member-section" aria-labelledby="member-saved-title">
        <div class="member-section-head">
            <div><span class="eyebrow">BỘ SƯU TẬP CÁ NHÂN</span>
                <h2 id="member-saved-title">Đã lưu, đừng bỏ lỡ.</h2>
                <p>Những ưu đãi bạn muốn quay lại khi cần.</p>
            </div><?php if ($canContribute): ?><a href="<?= e(url('/saved-coupons')) ?>">Xem tất cả
                    (<?= (int)$savedCount ?>) ↗</a><?php endif; ?>
        </div>
        <?php if ($savedPreview): ?>
            <div class="member-saved-grid">
                <?php foreach ($savedPreview as $coupon): ?>
                    <a class="member-ticket"
                        href="<?= e(url(in_array($coupon['effective_status'], ['ACTIVE', 'SCHEDULED'], true) ? '/coupons/' . (int)$coupon['id'] : '/saved-coupons')) ?>">
                        <div class="member-ticket-brand"><span
                                aria-hidden="true"><?= e(textPrefix($coupon['merchant_name'])) ?></span><?= e($coupon['merchant_name']) ?>
                        </div>
                        <div class="member-ticket-saving"><?= e(saving($coupon)) ?></div>
                        <h3><?= e($coupon['title']) ?></h3>
                        <div class="member-ticket-foot">
                            <span><?= $coupon['effective_status'] === 'ACTIVE' ? e(expiryLabel($coupon['ends_at'])) : e(['EXPIRED' => 'Đã hết hạn', 'SCHEDULED' => 'Sắp có hiệu lực'][$coupon['effective_status']] ?? 'Hiện không khả dụng') ?></span><?= icon('arrow') ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="member-empty"><strong>Bộ sưu tập đang chờ ưu đãi đầu tiên.</strong>
                <p>Bấm lưu trên coupon bạn quan tâm để dễ dàng tìm lại tại đây.</p><a class="btn btn-outline-brand"
                    href="<?= e(url('/coupons')) ?>">Tìm coupon yêu thích <?= icon('arrow') ?></a>
            </div>
        <?php endif; ?>
    </section>
    <section id="account-rewards" class="member-section" aria-labelledby="member-rewards-title">
        <div class="member-section-head">
            <div><span class="eyebrow">GIÁ TRỊ TỪ ĐÓNG GÓP</span>
                <h2 id="member-rewards-title">Mỗi chia sẻ đều có giá trị.</h2>
                <p>Lịch sử CouponPoint từ các đóng góp của bạn.</p>
            </div><?php if ($canContribute): ?><a href="<?= e(url('/my-coupons')) ?>">Đóng góp của tôi
                    ↗</a><?php endif; ?>
        </div>
        <?php if ($canContribute): ?>
            <p class="text-muted small">
                Chất lượng đóng góp: <?php if ($contributorQuality['approvalRate'] === null): ?>chưa có coupon được xét
                duyệt<?php else: ?><strong><?= (int)$contributorQuality['approvalRate'] ?>%</strong> được duyệt trong
                <?= (int)$contributorQuality['reviewed'] ?> coupon đã có quyết định<?php endif; ?>.
                <?php if ($contributorQuality['pending'] > 0): ?> <?= (int)$contributorQuality['pending'] ?> coupon đang chờ
                    xét duyệt.<?php endif; ?>
                    Tỷ lệ này không tính các coupon đang chờ duyệt và chưa phải cấp uy tín.
            </p>
        <?php endif; ?>
        <?php if ($pointHistory): ?>
            <div class="member-history">
                <?php foreach ($pointHistory as $item): ?>
                    <article class="member-history-item"><span class="member-history-icon"><?= icon('spark') ?></span>
                        <div><strong><?= e($item['note'] ?: $item['rule_code']) ?></strong>
                            <p><a
                                    href="<?= e(url('/coupons/' . (int)$item['coupon_id'])) ?>"><?= e($item['coupon_title']) ?></a>
                            </p><small><?= e(format_db_datetime($item['created_at'])) ?></small>
                        </div><b><?= (int)$item['points'] > 0 ? '+' : '' ?><?= (int)$item['points'] ?></b>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="member-empty"><strong>Chưa có giao dịch điểm.</strong>
                <p>Đóng góp coupon hữu ích cho cộng đồng và theo dõi kết quả duyệt ngay trong tài khoản.</p>
                <?php if ($canContribute): ?><a class="btn btn-outline-brand" href="<?= e(url('/coupons/create')) ?>">Chia
                        sẻ coupon đầu tiên <?= icon('arrow') ?></a><?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
    <section id="account-next" class="member-section member-next" aria-labelledby="member-next-title">
        <div><span
                class="eyebrow"><?= $actor['role'] === 'USER' ? 'CÙNG XÂY DỰNG CỘNG ĐỒNG' : 'KHÔNG GIAN LÀM VIỆC' ?></span>
            <h2 id="member-next-title">
                <?= $actor['role'] === 'USER' ? 'Có ưu đãi hay? Chia sẻ cùng nhau.' : 'Tiếp tục công việc của bạn.' ?>
            </h2>
            <p><?= $actor['role'] === 'USER' ? 'Gửi mã giảm giá bạn tìm thấy để cộng đồng cùng kiểm chứng. Nếu đại diện cửa hàng, bạn có thể đăng ký quản lý shop.' : 'Truy cập khu vực quản lý riêng với các quyền đã được cấp cho tài khoản.' ?>
            </p><?php if ($actor['role'] === 'USER'): ?><a class="member-partner-link"
                    href="<?= e(url('/merchant/apply')) ?>">Tôi đại diện cửa hàng →</a><?php endif; ?>
        </div>
        <a class="btn btn-brand"
            href="<?= e(url($actor['role'] === 'ADMIN' ? '/admin' : ($actor['role'] === 'MERCHANT' ? '/merchant' : '/coupons/create'))) ?>"><?= $actor['role'] === 'USER' ? 'Chia sẻ coupon' : 'Mở trang quản lý' ?>
            <?= icon('arrow') ?></a>
    </section>
</div>
<?php require __DIR__ . '/../partials/section-nav.php'; ?>
<?php
$couponId = (int)$coupon['id'];
$isSaved = in_array($couponId, $savedCouponIds ?? [], true);
$detailUrl = url('/coupons/'.$couponId);
$official = (int)$coupon['is_official'] === 1;
$contributor = $official ? $coupon['merchant_name'] : ($coupon['submitter_name'] ?? 'Thành viên CouponHub');
?>
<article class="community-ticket community-post">
    <div class="community-ticket-top">
        <span class="community-store-mark tone-<?= (int)$coupon['merchant_id'] % 4 ?>" aria-hidden="true"><?= brandLogo($coupon['merchant_name']) ?></span>
        <div class="community-store"><strong><?= e($coupon['merchant_name']) ?></strong><span><?= e($coupon['category_name']) ?> · <?= e(usage_channel_label($coupon['usage_channel'])) ?></span></div>
        <?php if ($currentUser && in_array($currentUser['role'],['USER','MERCHANT'],true)): ?>
        <form method="post" action="<?= e(url('/saved-coupons/'.$couponId.'/save')) ?>" class="discovery-save-form" data-save-form data-coupon-id="<?= $couponId ?>">
            <input type="hidden" name="csrf" value="<?= e(\App\Core\Session::token()) ?>"><input type="hidden" name="saved" value="<?= $isSaved ? '0' : '1' ?>">
            <button type="submit" class="discovery-save <?= $isSaved ? 'is-saved' : '' ?>" aria-pressed="<?= $isSaved ? 'true' : 'false' ?>" aria-label="<?= e(($isSaved ? 'Bỏ lưu ' : 'Lưu ').$coupon['title']) ?>"><?= icon('bookmark') ?></button>
        </form>
        <?php elseif (!$currentUser): ?><a class="discovery-save" href="<?= e(url('/login').'?return='.rawurlencode($detailUrl)) ?>" aria-label="Đăng nhập để lưu coupon"><?= icon('bookmark') ?></a><?php endif; ?>
    </div>
    <div class="community-ticket-value"><span><?= $official ? 'Từ cửa hàng' : 'Thành viên chia sẻ' ?><?= str_contains($coupon['title'],'[DEMO]') ? ' · Minh họa' : '' ?></span><strong class="<?= mb_strlen(saving($coupon))>12 ? 'community-saving-long' : '' ?>"><?= e(saving($coupon)) ?></strong><small><?= (float)$coupon['min_order_amount'] > 0 ? 'Đơn từ '.e(money($coupon['min_order_amount'])) : 'Không yêu cầu đơn tối thiểu' ?><?= $coupon['max_discount_amount'] !== null ? ' · Tối đa '.e(money($coupon['max_discount_amount'])) : '' ?></small></div>
    <h2><a href="<?= e($detailUrl) ?>"><?= e(editorialTitle($coupon['title'])) ?></a></h2>
    <div class="community-ticket-meta"><span><?= icon('clock') ?><?= e(expiryLabel($coupon['ends_at'])) ?></span><span><?= icon('bookmark') ?><?= (int)$coupon['favorite_count'] ?> lượt lưu</span></div>
    <div class="community-ticket-verification"><?= icon('shield') ?><?= $coupon['trust_score'] === null ? 'Chưa có đánh giá hiệu quả' : e((string)$coupon['trust_score']).'% xác nhận dùng được' ?><?= (int)$coupon['total_votes'] > 0 ? ' · '.(int)$coupon['total_votes'].' lượt' : '' ?></div>
    <div class="community-contributor"><span aria-hidden="true"><?= e(accountInitials($contributor)) ?></span><div><strong><?= e($contributor) ?></strong><small><?= $official ? 'Cửa hàng chia sẻ' : 'Thành viên đóng góp' ?><?= !empty($coupon['created_at']) ? ' · '.e(format_db_datetime($coupon['created_at'],'d/m/Y')) : '' ?></small></div></div>
    <div class="community-ticket-bottom">
        <?php if (!empty($coupon['code'])): ?><span class="community-ticket-code"><small>MÃ ƯU ĐÃI</small><code><?= e($coupon['code']) ?></code></span><button class="community-copy" type="button" data-community-copy="<?= e($coupon['code']) ?>" aria-label="<?= e('Sao chép mã '.$coupon['code']) ?>"><?= icon('copy') ?><span>Sao chép</span></button><?php else: ?><span class="community-ticket-code"><small>ƯU ĐÃI KHÔNG CẦN MÃ</small><span>Xem hướng dẫn áp dụng</span></span><?php endif; ?>
        <a class="community-ticket-open" href="<?= e($detailUrl) ?>" aria-label="<?= e('Xem chi tiết '.$coupon['title']) ?>"><?= icon('arrow') ?></a>
    </div>
</article>

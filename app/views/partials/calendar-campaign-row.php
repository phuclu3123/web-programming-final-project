<?php
$rowTitle=editorialTitle($rowCampaign['name']);
$rowStart=(int)$rowCampaign['_start']; $rowEnd=(int)$rowCampaign['_end'];
$rowUpcoming=$rowStart>$calendarNow;
$rowRemaining=max(0,(int)floor((($rowUpcoming ? $rowStart : $rowEnd)-$calendarNow)/1000));
$rowDays=intdiv($rowRemaining,86400);
$rowClock=($rowDays ? $rowDays.' ngày ' : '').sprintf('%02d:%02d:%02d',intdiv($rowRemaining%86400,3600),intdiv($rowRemaining%3600,60),$rowRemaining%60);
if ($rowCompact) $rowClock=$rowDays ? $rowDays.' ngày '.intdiv($rowRemaining%86400,3600).' giờ' : (intdiv($rowRemaining,3600) ? intdiv($rowRemaining,3600).' giờ '.intdiv($rowRemaining%3600,60).' phút' : intdiv($rowRemaining,60).' phút '.($rowRemaining%60).' giây');
?>
<a class="calendar-offer-row<?= $rowCompact ? ' is-compact calendar-detail-event' : '' ?>" href="<?= e(url('/campaigns/'.(int)$rowCampaign['id'])) ?>" data-calendar-offer data-start="<?= $rowStart ?>" data-end="<?= $rowEnd ?>">
    <?= brandLogo($rowCampaign['merchant_name'] ?: 'CouponHub') ?>
    <span class="calendar-offer-identity"><strong><?= e($rowTitle) ?></strong><small><?= e($rowCampaign['merchant_name'] ?: 'CouponHub') ?><?php if (!$rowCompact): ?> · <?= e(format_db_datetime($rowCampaign['starts_at'],'d/m/Y')) ?> – <?= e(format_db_datetime($rowCampaign['ends_at'],'d/m/Y')) ?><?php endif; ?></small></span>
    <span class="calendar-row-timer" data-calendar-timer role="timer" aria-live="off"><small data-calendar-timer-label><?= $calendarNow >= $rowEnd ? 'Đã kết thúc' : ($rowUpcoming ? 'Bắt đầu sau' : ($rowCompact ? 'Còn' : 'Kết thúc sau')) ?></small><strong data-calendar-timer-value><?= e($rowClock) ?></strong></span>
    <span class="calendar-row-open"><?php if (!$rowCompact): ?><span>Chi tiết</span><?php endif; ?><?= icon('arrow') ?></span>
</a>

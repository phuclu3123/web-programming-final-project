<div class="calendar-day-detail-heading">
    <span><?= e($weekdayNames[(int)$day->format('N')]) ?><?= $day->format('Y-m-d') === $today->format('Y-m-d') ? ' · Hôm nay' : '' ?></span>
    <h2><time datetime="<?= e($day->format('Y-m-d')) ?>"><?= e($day->format('d/m/Y')) ?></time></h2>
    <p><?= count($dayCampaigns) ?> chiến dịch · gần hết hạn xếp trước</p>
</div>
<div class="calendar-day-events">
    <?php if ($dayCampaigns): ?>
        <?php foreach (array_slice($dayCampaigns,0,5) as $rowCampaign): $rowCompact=true; require __DIR__.'/calendar-campaign-row.php'; endforeach; ?>
        <?php if (count($dayCampaigns)>5): ?><details class="calendar-more-day"><summary><span class="calendar-more-closed">Xem thêm <?= count($dayCampaigns)-5 ?> chiến dịch</span><span class="calendar-more-open">Thu gọn</span></summary>
            <?php foreach (array_slice($dayCampaigns,5) as $rowCampaign): $rowCompact=true; require __DIR__.'/calendar-campaign-row.php'; endforeach; ?>
        </details><?php endif; ?>
    <?php else: ?><div class="calendar-day-empty"><strong>Chưa có chiến dịch vào ngày này.</strong><p>Chọn ngày khác hoặc chuyển sang Xem nhanh.</p></div><?php endif; ?>
</div>

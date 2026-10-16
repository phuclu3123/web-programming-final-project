<?php $posters=array_slice($campaignSelection,0,3); ?>
<?php if ($posters): ?>
<div class="campaign-bento">
    <?php $campaign=$posters[0]; $isMainPoster=true; require __DIR__.'/campaign-poster.php'; ?>
    <div class="campaign-bento-side <?= count($posters)===1 ? 'bento-side-single' : '' ?>">
        <?php foreach (array_slice($posters,1) as $campaign): $isMainPoster=false; require __DIR__.'/campaign-poster.php'; endforeach; ?>
        <?php if (count($posters)<3): ?>
        <aside class="campaign-planner"><span class="eyebrow">LÊN LỊCH CHO LẦN SĂN MÃ TIẾP THEO</span><div class="planner-date"><strong data-planner-day><?= date('d') ?></strong><div><span data-planner-month>THÁNG <?= date('m') ?> / <?= date('Y') ?></span><time data-time-vn datetime="<?= date(DATE_ATOM) ?>"><?= date('H:i:s') ?></time><small>Giờ Việt Nam</small></div><?= icon('clock') ?></div><p>Xem các dịp mua sắm và chiến dịch trong một lịch chung.</p><a href="<?= e(url('/campaign-calendar')) ?>">Khám phá lịch ưu đãi <?= icon('arrow') ?></a></aside>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

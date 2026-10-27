<?php $timing=campaignTiming($campaign); ?>
<div class="campaign-clock" data-campaign-clock data-start="<?= $timing['start'] ?>" data-end="<?= $timing['end'] ?>"
    data-server-now="<?= $timing['now'] ?>" role="timer" aria-live="off">
    <span class="campaign-clock-label"
        data-clock-label><?= $timing['upcoming'] ? 'Bắt đầu sau' : 'Kết thúc sau' ?></span>
    <div class="campaign-clock-parts">
        <?php foreach (['days'=>'Ngày','hours'=>'Giờ','minutes'=>'Phút','seconds'=>'Giây'] as $part=>$label): ?><span><strong
                data-clock-part="<?= $part ?>"><?= str_pad((string)$timing[$part],2,'0',STR_PAD_LEFT) ?></strong><small><?= $label ?></small></span><?php endforeach; ?>
    </div>
</div>
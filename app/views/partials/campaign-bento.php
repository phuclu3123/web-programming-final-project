<?php $posters=array_slice($campaignSelection ?? [],0,2); if ($posters): ?>
<div class="home-event-selection">
    <?php foreach ($posters as $campaign): $detailMode=false; require __DIR__.'/campaign-feature.php'; endforeach; ?>
</div>
<?php endif; ?>

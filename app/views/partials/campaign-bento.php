<?php
// Tái sử dụng các thẻ chiến dịch để giữ cùng dữ liệu và liên kết ở nhiều bố cục.
 $posters=array_slice($campaignSelection ?? [],0,2); if ($posters): ?>
<div class="home-event-selection">
    <?php foreach ($posters as $campaign): $detailMode=false; require __DIR__.'/campaign-feature.php'; endforeach; ?>
</div>
<?php endif; ?>

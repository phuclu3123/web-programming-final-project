<a class="guide-card" href="<?= e(url('/guides/'.$slug)) ?>">
    <div class="guide-card-photo">
        <?php if ($slug==='so-sanh-muc-tiet-kiem'): ?>
        <div class="guide-figure" aria-label="So sánh giảm 20 phần trăm và giảm 40 nghìn đồng">
            <div><strong>20%</strong><small>GIẢM THEO TỶ LỆ</small></div><em>hay</em>
            <div><strong>40k</strong><small>GIẢM SỐ TIỀN</small></div>
        </div>
        <?php else: ?>
        <img src="<?= e($slug==='kiem-chung-cung-cong-dong' ? brandPhoto('grab-delivery') : editorialPhoto($item['photo'])) ?>"
            alt="" width="720" height="480" loading="lazy">
        <?php endif; ?>
        <span><?= (int)$item['minutes'] ?> phút đọc</span>
    </div>
    <div class="guide-card-copy"><small><?= e($item['topic']) ?></small>
        <h3><?= e($item['title']) ?></h3>
        <p><?= e($item['summary']) ?></p><span>Đọc hướng dẫn <?= icon('arrow') ?></span>
    </div>
</a>
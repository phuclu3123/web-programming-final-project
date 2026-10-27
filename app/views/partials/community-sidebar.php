<aside class="community-sidebar" aria-label="Khám phá cộng đồng">
    <section class="community-side-brands">
        <h2>Thương hiệu</h2>
        <div class="community-brand-list">
            <?php foreach (array_slice($merchants,0,6) as $merchant): ?><a
                href="<?= e(url('/coupons').'?merchant_id='.$merchant->id) ?>"><?= brandLogo($merchant->name) ?><span><?= e($merchant->name) ?></span><?= icon('arrow') ?></a><?php endforeach; ?>
        </div>
    </section>
    <section class="community-side-guide">
        <h2>Trước khi dùng mã</h2>
        <p>Kiểm tra nguồn, đơn tối thiểu, mức giảm tối đa và hạn dùng. Chia sẻ kết quả sau khi bạn đã dùng thử.</p><a
            href="<?= e(url('/guides/kiem-chung-cung-cong-dong')) ?>">Cách cộng đồng kiểm chứng <?= icon('arrow') ?></a>
    </section>
</aside>
<nav class="section-rail" aria-label="Các phần trong trang" data-section-rail>
    <?php foreach ($sectionLinks as $index => [$target, $label]): ?>
        <a href="#<?= e($target) ?>" <?= $index === 0 ? 'aria-current="location"' : '' ?> aria-label="<?= e($label) ?>">
            <span class="rail-label"><?= e($label) ?></span><span class="rail-mark" aria-hidden="true"></span>
        </a>
    <?php endforeach; ?>
</nav>
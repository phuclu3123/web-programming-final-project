<label for="<?= e($platformSelectId) ?>">Nền tảng / thương hiệu</label>
<select id="<?= e($platformSelectId) ?>" name="platform" class="form-select">
    <option value="0">Tất cả nền tảng</option>
    <?php foreach ($platforms as $platformOption): ?>
        <option value="<?= $platformOption->id ?>" <?= $selectedPlatform===$platformOption->id ? 'selected' : '' ?>><?= e($platformOption->name) ?></option>
    <?php endforeach; ?>
</select>

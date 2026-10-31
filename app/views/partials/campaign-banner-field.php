<div class="mb-3">
    <label class="form-label" for="campaign-banner">Ảnh banner</label>
    <input id="campaign-banner" name="banner_path" class="form-control" maxlength="500" type="text"
        value="<?= e($bannerValue ?? '') ?>" placeholder="https://..." aria-describedby="campaign-banner-help">
    <small id="campaign-banner-help" class="form-text text-muted">Nhập URL HTTPS hoặc đường dẫn ảnh có sẵn trong assets/images, public/images, public/uploads. Để trống để dùng ảnh mặc định.</small>
</div>

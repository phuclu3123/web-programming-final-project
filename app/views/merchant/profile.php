<?php use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4">Hồ sơ cửa hàng</h1>
                <p class="text-muted">Cập nhật thông tin hiển thị của cửa hàng. Đường dẫn định danh được giữ nguyên.</p>
                <form method="post" action="<?= e(url('/merchant/profile/'.(int)$merchant['id'])) ?>"><input
                        type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="mb-3"><label for="profile-name">Tên cửa hàng</label><input id="profile-name" class="form-control" name="name"
                            maxlength="160" required value="<?= e((string)$merchant['name']) ?>"></div>
                    <div class="mb-3"><label for="profile-slug">Định danh cửa hàng</label><input id="profile-slug" class="form-control" disabled
                            value="<?= e((string)$merchant['slug']) ?>"></div>
                    <div class="mb-3"><label for="profile-website">Website</label><input id="profile-website" class="form-control" type="url"
                            name="website_url" value="<?= e((string)($merchant['website_url']??'')) ?>"></div>
                    <div class="mb-3"><label for="profile-description">Mô tả</label><textarea id="profile-description" class="form-control" name="description"
                            rows="5"><?= e((string)($merchant['description']??'')) ?></textarea></div>
                    <button class="btn btn-primary">Lưu hồ sơ</button><a class="btn btn-link"
                        href="<?= e(url('/merchant')) ?>">Merchant Center</a>
                </form>
            </div>
        </div>
    </div>
</div>

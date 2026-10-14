<?php use App\Core\Session; ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4">Hồ sơ Merchant</h1>
                <p class="text-muted">Slug được giữ ổn định trong v1; Merchant có thể cập nhật nội dung hiển thị.</p>
                <form method="post" action="<?= e(url('/merchant/profile/'.(int)$merchant['id'])) ?>"><input
                        type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="form-group"><label>Tên Merchant</label><input class="form-control" name="name"
                            maxlength="160" required value="<?= e((string)$merchant['name']) ?>"></div>
                    <div class="form-group"><label>Slug</label><input class="form-control" disabled
                            value="<?= e((string)$merchant['slug']) ?>"></div>
                    <div class="form-group"><label>Website</label><input class="form-control" type="url"
                            name="website_url" value="<?= e((string)($merchant['website_url']??'')) ?>"></div>
                    <div class="form-group"><label>Mô tả</label><textarea class="form-control" name="description"
                            rows="5"><?= e((string)($merchant['description']??'')) ?></textarea></div>
                    <button class="btn btn-primary">Lưu hồ sơ</button><a class="btn btn-link"
                        href="<?= e(url('/merchant')) ?>">Merchant Center</a>
                </form>
            </div>
        </div>
    </div>
</div>
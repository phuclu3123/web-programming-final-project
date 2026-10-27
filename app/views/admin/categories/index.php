<?php

use App\Core\Session; ?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h5">Thêm danh mục</h1>
                <form method="post" action="<?= e(url('/admin/categories')) ?>">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="mb-3"><label class="form-label" for="category-new-name">Tên danh mục</label>
                        <input id="category-new-name" class="form-control" name="name" maxlength="100" required>
                    </div>
                    <div class="mb-3"><label class="form-label" for="category-new-description">Mô tả</label>
                        <textarea id="category-new-description" class="form-control" name="description" maxlength="2000"
                            rows="3"></textarea>
                    </div>
                    <button class="btn btn-primary" type="submit">Thêm danh mục</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <h2 class="h5 mb-3">Danh mục hiện có</h2>
        <?php foreach ($categories as $category): $id = (int)$category['id']; ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div><strong><?= e((string)$category['name']) ?></strong>
                            <small class="text-muted ms-2"><code><?= e((string)$category['slug']) ?></code></small>
                        </div>
                        <span class="badge bg-<?= (int)$category['is_active'] === 1 ? 'success' : 'secondary' ?>">
                            <?= (int)$category['is_active'] === 1 ? 'Đang bật' : 'Đã tắt' ?></span>
                    </div>
                    <form method="post" action="<?= e(url('/admin/categories/' . $id . '/edit')) ?>">
                        <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                        <div class="row g-2">
                            <div class="col-md-8"><label class="form-label" for="category-name-<?= $id ?>">Tên danh
                                    mục</label>
                                <input class="form-control" id="category-name-<?= $id ?>" name="name" required
                                    maxlength="100" value="<?= e((string)$category['name']) ?>">
                            </div>
                            <div class="col-md-4"><label class="form-label" for="category-order-<?= $id ?>">Thứ tự</label>
                                <input class="form-control" id="category-order-<?= $id ?>" name="sort_order" type="number"
                                    min="0" max="99999" required value="<?= (int)$category['sort_order'] ?>">
                            </div>
                            <div class="col-12"><label class="form-label" for="category-desc-<?= $id ?>">Mô tả</label>
                                <textarea id="category-desc-<?= $id ?>" name="description" maxlength="2000"
                                    class="form-control"
                                    rows="2"><?= e((string)($category['description'] ?? '')) ?></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary mt-3">Lưu thay đổi</button>
                    </form>
                    <div class="d-flex gap-2 mt-2">
                        <form method="post" action="<?= e(url('/admin/categories/' . $id . '/toggle')) ?>">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <input type="hidden" name="active" value="<?= (int)$category['is_active'] === 1 ? '0' : '1' ?>">
                            <button class="btn btn-sm btn-outline-secondary"
                                type="submit"><?= (int)$category['is_active'] === 1 ? 'Tắt danh mục' : 'Bật danh mục' ?></button>
                        </form>
                        <form method="post" action="<?= e(url('/admin/categories/' . $id . '/delete')) ?>"
                            onsubmit="return confirm('Chỉ xóa được danh mục chưa dùng trong Coupon/Article. Tiếp tục?');">
                            <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa nếu chưa dùng</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
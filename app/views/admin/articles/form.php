<?php

use App\Core\Session; ?>
<h1 class="h3 mb-3"><?= $article === null ? 'Viết bài mới' : 'Chỉnh sửa bài viết' ?></h1>
<form method="post" class="card card-body shadow-sm"
    action="<?= e($article === null ? url('/admin/articles') : url('/admin/articles/' . (int)$article['id'] . '/edit')) ?>">
    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
    <div class="form-group"><label>Tiêu đề</label><input aria-label="Tiêu đề bài viết" name="title" class="form-control" required maxlength="200"
            value="<?= e((string)($article['title'] ?? '')) ?>"></div>
    <div class="form-group"><label>Tóm tắt</label><textarea aria-label="Tóm tắt bài viết" name="summary" class="form-control" maxlength="1000"
            rows="2"><?= e((string)($article['summary'] ?? '')) ?></textarea></div>
    <div class="form-group"><label>Nội dung (văn bản thuần)</label><textarea aria-label="Nội dung bài viết" name="body" class="form-control" required
            maxlength="30000" rows="12"><?= e((string)($article['body'] ?? '')) ?></textarea></div>
    <div class="form-group"><label>Trạng thái</label><select aria-label="Trạng thái" name="status"
            class="form-control"><?php foreach (['DRAFT' => 'Bản nháp', 'PUBLISHED' => 'Xuất bản', 'ARCHIVED' => 'Lưu trữ'] as $code => $name): ?>
            <option value="<?= e($code) ?>" <?= ($article['status'] ?? 'DRAFT') === $code ? 'selected' : '' ?>>
                <?= e($name) ?>
            </option><?php endforeach; ?>
        </select></div>
    <div><button class="btn btn-primary">Lưu bài viết</button> <a class="btn btn-light"
            href="<?= e(url('/admin/articles')) ?>">Hủy</a></div>
</form>

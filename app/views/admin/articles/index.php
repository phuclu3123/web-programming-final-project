<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3">Quản lý bài viết</h1><a class="btn btn-outline-primary" href="<?= e(url('/admin/article-comments')) ?>">Bình luận</a><a class="btn btn-primary" href="<?= e(url('/admin/articles/create')) ?>">+ Viết
        bài</a>
</div>
<div class="table-responsive">
    <table class="table table-striped bg-white">
        <thead>
            <tr>
                <th>Tiêu đề</th>
                <th>Trạng thái</th>
                <th>Tác giả</th>
                <th>Cập nhật</th>
            </tr>
        </thead>
        <tbody><?php if ($articles === []): ?><tr><td colspan="4" class="text-muted py-4">Chưa có bài viết. Chọn “Viết bài” để tạo bản nháp đầu tiên.</td></tr><?php endif; ?><?php foreach ($articles as $a): ?><tr>
                    <td><?= e((string)$a['title']) ?></td>
                    <td><?= e((string)$a['status']) ?></td>
                    <td><?= e((string)$a['author_name']) ?></td>
                    <td><a class="btn btn-outline-primary btn-sm"
                            href="<?= e(url('/admin/articles/' . (int)$a['id'] . '/edit')) ?>">Sửa</a></td>
                </tr><?php endforeach; ?></tbody>
    </table>
</div>

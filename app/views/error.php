<?php $title = 'Thông báo'; require __DIR__ . '/layouts/head.php'; ?>
<main id="main" class="container-wide py-5">
    <div class="empty-state">
        <p class="eyebrow">COUPONHUB · <?= (int) $status ?></p>
        <h1>Chưa thể hoàn thành thao tác</h1>
        <p><?= e($message) ?></p><a class="btn btn-brand" href="index.php">Về trang chủ</a>
    </div>
</main>
</body>

</html>
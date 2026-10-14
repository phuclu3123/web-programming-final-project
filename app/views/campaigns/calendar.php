<h1 class="h3">Lịch ngày lễ <?= (int)$year ?> – Việt Nam</h1>
<p class="text-muted">Dữ liệu từ Webservice bên ngoài Nager.Holidays (JSON). Chỉ dùng tham khảo ý tưởng lập Campaign;
    ngày nghỉ chính thức cần đối chiếu thông báo cơ quan có thẩm quyền.</p>
<?php if ($holidays === []): ?><div class="alert alert-warning">Chưa tải được lịch ngày lễ. Có thể API không khả dụng hoặc
        PHP cURL chưa bật. CouponHub vẫn hoạt động bình thường.</div><?php endif; ?>
<div class="row"><?php foreach ($holidays as $h): ?><div class="col-md-6 col-lg-4 mb-3">
            <div class="card h-100 shadow-sm">
                <div class="card-body"><span class="badge badge-primary mb-2"><?= e((string)$h['date']) ?></span>
                    <h2 class="h6 mb-0"><?= e((string)$h['name']) ?></h2>
                </div>
            </div>
        </div><?php endforeach; ?></div>
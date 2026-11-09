<?php
// Nguồn và ảnh chỉ phục vụ đối chiếu; các hàng chưa nhập không phải coupon công khai.
 use App\Core\Session; ?>
<header class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
 <div><h1 class="h3 mb-2">Nguồn coupon</h1><p class="text-muted mb-0">ACCESSTRADE · Lấy dữ liệu, đối chiếu điều kiện, chuyển sang hàng kiểm duyệt.</p></div>
 <a class="btn btn-outline-secondary" href="<?= e(url('/admin/coupons')) ?>">Hàng kiểm duyệt <?= icon('arrow') ?></a>
</header>
<?php if (!$storageReady): ?><div class="alert alert-warning">Cần cài migration <code>database/migrations/20261105_external_offers.sql</code>. Với database đang có dữ liệu, sao lưu rồi chỉ chạy migration này.</div>
<?php else: ?>
<section class="card mb-4"><div class="card-body">
 <div class="d-flex align-items-center flex-wrap gap-2 mb-3"><h2 class="h5 mb-0">Đồng bộ nguồn</h2><span class="badge text-bg-<?= $providerReady ? 'success':'secondary' ?>"><?= $providerReady ? 'Đã có khóa cấu hình':'Chưa cấu hình khóa API' ?></span></div>
 <p class="small text-muted">Mỗi lần lấy tối đa 50 mục. Dữ liệu đã nhập hoặc bỏ qua không tự mở lại; coupon hiện tại không bị ghi đè.</p>
 <form action="<?= e(url('/admin/integrations/sync')) ?>" method="post" class="row g-3 align-items-end" data-integration-form>
  <div class="col-12" data-integration-message role="alert" tabindex="-1" hidden></div>
  <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
  <div class="col-md-4"><label class="form-label" for="feed-resource">Dữ liệu</label><select id="feed-resource" name="resource" class="form-select"><option value="offers">Ưu đãi và mã khuyến mại</option><option value="coupons">Danh sách coupon</option></select></div>
  <div class="col-md-4"><label class="form-label" for="feed-merchant">Mã nhà cung cấp (tùy chọn)</label><input class="form-control" id="feed-merchant" name="merchant" maxlength="100" placeholder="Ví dụ: shopee"></div>
  <div class="col-md-2"><label class="form-label" for="feed-page">Trang nguồn</label><input type="number" id="feed-page" name="page" min="1" max="1000" value="1" class="form-control"></div>
  <div class="col-md-2"><button class="btn btn-primary w-100" <?= $providerReady ? '':'disabled' ?>>Lấy dữ liệu</button></div>

 </form>
</div></section>
<div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Hàng chờ đối chiếu</h2><span class="small text-muted"><?= (int)$listing['total'] ?> mục</span></div>
<?php if (!$listing['items']): ?><div class="card"><div class="card-body"><strong>Chưa có dữ liệu từ nguồn.</strong><p class="text-muted mb-0 mt-2">Điền ACCESSTRADE_ACCESS_KEY trong .env rồi lấy trang đầu tiên. Coupon demo không được trộn vào hàng chờ này.</p></div></div>
<?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Ưu đãi</th><th>Nhà cung cấp</th><th>Trạng thái</th><th>Cập nhật</th><th></th></tr></thead><tbody>
<?php foreach ($listing['items'] as $row): $payload=json_decode($row['payload'],true,64,JSON_THROW_ON_ERROR); $sourceImage=safeHttpUrl($payload['image_url'] ?? null); ?><tr>
 <td><div class="d-flex align-items-start gap-3">
  <?php if ($sourceImage): ?><img src="<?= e($sourceImage) ?>" alt="" width="72" height="72" loading="lazy" decoding="async" referrerpolicy="no-referrer" class="rounded border flex-shrink-0" style="object-fit:cover">
  <?php else: ?><span class="d-flex align-items-center justify-content-center rounded border bg-light text-muted flex-shrink-0" style="width:72px;height:72px" role="img" aria-label="Nguồn chưa cung cấp ảnh"><i class="fa-solid fa-image" aria-hidden="true"></i></span><?php endif; ?>
  <div><strong><?= e($payload['title']) ?></strong><small class="d-block text-muted mt-1"><?= e($row['code'] ?: 'Ưu đãi không cần mã') ?></small>
   <?php if (!empty($payload['ends_at'])): ?><small class="d-block text-muted">Đến <?= e(format_db_datetime($payload['ends_at'])) ?></small><?php endif; ?>
   <?php if (!empty($payload['ends_at']) && $payload['ends_at'] < gmdate('Y-m-d H:i:s')): ?><span class="badge text-bg-secondary mt-1">Hết hạn theo nguồn</span><?php endif; ?>
   <?php if (empty($payload['discount_type']) || ($payload['discount_value'] ?? null) === null): ?><span class="badge text-bg-warning mt-1">Cần đối chiếu mức giảm</span><?php endif; ?>
  </div>
 </div></td>
 <td><?= e($payload['merchant_ref']) ?></td><td><?= e(['PENDING'=>'Chờ đối chiếu','IMPORTED'=>'Đã chuyển kiểm duyệt','DISMISSED'=>'Đã bỏ qua'][$row['state']]) ?><?php if($row['state']==='IMPORTED' && $row['fingerprint']!==$row['imported_fingerprint']): ?><small class="d-block text-danger">Nguồn đã thay đổi · Cần kiểm tra</small><?php endif; ?></td>
 <td class="small"><?= e(format_db_datetime($row['fetched_at'])) ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/integrations/'.$row['id'].'/review')) ?>">Xem nguồn</a></td>
</tr><?php endforeach; ?></tbody></table></div>
<?php endif; ?>
<?php if ($listing['pages']>1): ?><nav aria-label="Trang nguồn đã lưu" class="d-flex gap-2 mt-3"><?php for($p=max(1,$listing['page']-2);$p<=min($listing['pages'],$listing['page']+2);++$p): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/integrations').'?page='.$p) ?>" <?= $p===$listing['page']?'aria-current="page"':'' ?>><?= $p ?></a><?php endfor; ?></nav><?php endif; ?>
<?php endif; ?>
<script src="<?= e(url('/assets/js/integrations.js')) ?>" defer></script>

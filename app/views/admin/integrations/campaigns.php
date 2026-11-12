<?php
use App\Core\Session;
// Mọi checkbox mang fingerprint đúng bản nguồn đang hiển thị, không chỉ ID.
?>
<header class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
 <div><h1 class="h3 mb-2">Nguồn chiến dịch</h1><p class="text-muted mb-0">Đồng bộ chương trình đối tác từ ACCESSTRADE và duyệt hiển thị.</p></div>
 <a class="btn btn-outline-secondary" href="<?= e(url('/campaigns')) ?>">Xem trang Chiến dịch <?= icon('arrow') ?></a>
</header>
<nav class="nav nav-tabs mb-4" aria-label="Loại nguồn ACCESSTRADE">
 <a class="nav-link" href="<?= e(url('/admin/integrations')) ?>">Coupon</a>
 <a class="nav-link active" href="<?= e(url('/admin/integrations/campaigns')) ?>" aria-current="page">Chiến dịch</a>
</nav>
<?php if(!$storageReady): ?>
 <div class="alert alert-warning">Sao lưu database và áp dụng <code>database/migrations/20261110_external_campaigns.sql</code> để bật nguồn chiến dịch.</div>
<?php else: ?>
<section class="card mb-4"><div class="card-body">
 <h2 class="h5">Đồng bộ chiến dịch ACCESSTRADE</h2>
 <p class="text-muted small">20 chiến dịch mỗi trang nguồn, tối đa 100 mục mỗi lượt. Dữ liệu vào hàng chờ trước khi hiển thị; đồng bộ không mở lại mục đã bỏ qua.</p>
 <form method="post" action="<?= e(url('/admin/integrations/campaigns/sync')) ?>" class="row g-3 align-items-end" data-integration-form>
  <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>">
  <div class="col-12" data-integration-message role="alert" tabindex="-1" hidden></div>
  <div class="col-sm-4"><label class="form-label" for="campaign-source-page">Từ trang nguồn</label><input id="campaign-source-page" class="form-control" type="number" name="page" min="1" max="1000" value="1" required></div>
  <div class="col-sm-4"><label class="form-label" for="campaign-source-pages">Số trang cần lấy</label><select id="campaign-source-pages" class="form-select" name="pages"><?php for($n=1;$n<=5;$n++): ?><option value="<?= $n ?>" <?= $n===5?'selected':'' ?>><?= $n ?> trang · tối đa <?= $n*20 ?> chiến dịch</option><?php endfor; ?></select></div>
  <div class="col-sm-4"><button type="submit" class="btn btn-primary w-100" <?= $providerReady?'':'disabled' ?>>Đồng bộ chiến dịch</button></div>
 </form>
 <?php if(!$providerReady): ?><p class="text-danger small mt-2 mb-0">Chưa cấu hình ACCESSTRADE_ACCESS_KEY.</p><?php endif; ?>
</div></section>
<form method="get" class="d-flex align-items-end flex-wrap gap-2 mb-3" action="<?= e(url('/admin/integrations/campaigns')) ?>">
 <div><label class="form-label" for="source-campaign-state">Trạng thái</label><select name="state" id="source-campaign-state" class="form-select"><?php foreach([''=>'Tất cả','PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','DISMISSED'=>'Đã bỏ qua'] as $value=>$label): ?><option value="<?= e($value) ?>" <?= $state===$value?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
 <button class="btn btn-outline-secondary">Lọc</button><span class="small text-muted"><?= (int)$listing['total'] ?> chiến dịch</span>
</form>
<div data-bulk-scope>
 <form method="post" action="<?= e(url('/admin/integrations/campaigns/moderate')) ?>" class="card mb-3" data-bulk-form>
  <div class="card-body">
   <input type="hidden" name="csrf" value="<?= e(Session::token()) ?>"><input type="hidden" name="selection" value="[]">
   <div class="d-flex gap-3 flex-wrap align-items-center mb-3">
    <label class="form-check d-flex align-items-center gap-2 mb-0"><input type="checkbox" class="form-check-input m-0" data-bulk-all>Chọn tất cả trang này</label>
    <span class="small text-muted" data-bulk-count role="status" aria-live="polite">Đã chọn 0 mục</span>
   </div>
   <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label" for="campaign-bulk-action">Thao tác</label><select class="form-select" id="campaign-bulk-action" name="action"><option value="APPROVE">Duyệt hiển thị</option><option value="DISMISS">Bỏ qua / ngừng hiển thị</option></select></div>
    <div class="col-md-6"><label class="form-label" for="campaign-bulk-note">Ghi chú chung (tùy chọn)</label><input class="form-control" id="campaign-bulk-note" name="note" maxlength="1000"></div>
    <div class="col-md-3"><button type="submit" class="btn btn-primary w-100" data-bulk-submit disabled>Áp dụng cho mục đã chọn</button></div>
   </div>
   <p class="small text-muted mt-3 mb-0">Ngày kết thúc chưa công bố được giữ nguyên. Mục đã đổi nội dung cần duyệt lại; hoa hồng đối tác không phải mức giảm cho người mua.</p>
   <div class="alert mt-3 mb-0" data-bulk-message role="alert" tabindex="-1" hidden></div>
  </div>
 </form>
 <noscript><div class="alert alert-info">Bật JavaScript để chọn và duyệt nhiều chiến dịch.</div></noscript>
 <?php if(!$listing['items']): ?><div class="card"><div class="card-body">Chưa có chiến dịch trong lựa chọn này. Bấm “Đồng bộ chiến dịch” để lấy nguồn.</div></div>
 <?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Chọn</th><th>Chương trình</th><th>Thời gian theo nguồn</th><th>Kiểm duyệt</th></tr></thead><tbody>
 <?php foreach($listing['items'] as $row): $p=json_decode($row['payload'],true,64,JSON_THROW_ON_ERROR);$changed=$row['state']==='APPROVED' && $row['fingerprint']!==$row['approved_fingerprint']; ?>
 <tr data-bulk-row="<?= (int)$row['id'] ?>">
  <td><input class="form-check-input" type="checkbox" data-bulk-item data-id="<?= (int)$row['id'] ?>" data-fingerprint="<?= e($row['fingerprint']) ?>" aria-label="Chọn <?= e($p['name']) ?>" <?= $row['state']==='DISMISSED'?'disabled':'' ?>></td>
  <td><div class="d-flex align-items-start gap-3">
   <?php if($p['logo']): ?><img src="<?= e($p['logo']) ?>" alt="" width="56" height="56" class="object-fit-contain rounded border" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php endif; ?>
   <div><strong><?= e($p['name']) ?></strong><small class="d-block text-muted">ID <?= e($row['provider_id']) ?> · <?= e($p['merchant']) ?></small>
    <small class="d-block text-muted">Quyền chạy của publisher: <?= e(['unregistered'=>'Chưa đăng ký','pending'=>'Chờ duyệt','successful'=>'Đã được duyệt'][$p['approval']]??'Chưa rõ') ?></small>
    <?php if($p['url']): ?><a class="d-inline-block mt-1" href="<?= e($p['url']) ?>" target="_blank" rel="noopener noreferrer">Xem website đối tác <?= icon('arrow') ?></a><?php endif; ?>
    <details class="mt-2"><summary>Thông tin từ nguồn</summary><p class="small mt-2 mb-0"><?= nl2br(e($p['description']?:'Nguồn chưa cung cấp mô tả.')) ?></p></details>
   </div>
  </div></td>
  <td class="small"><?php if($p['starts_at']): ?>Từ <?= e(format_db_datetime($p['starts_at'])) ?><br><?php endif; ?><?= $p['ends_at']?'Đến '.e(format_db_datetime($p['ends_at'])):'Chưa công bố ngày kết thúc' ?><span class="d-block text-<?= $p['running']?'success':'secondary' ?>"><?= $p['running']?'Nguồn đang hoạt động':'Nguồn đã ngừng' ?></span></td>
  <td><span><?= e(['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','DISMISSED'=>'Đã bỏ qua'][$row['state']]) ?></span><?php if($changed): ?><small class="d-block text-warning">Nguồn đã đổi · Cần duyệt lại</small><?php endif; ?><small class="d-block text-muted"><?= e(format_db_datetime($row['fetched_at'])) ?></small><div class="small mt-2" data-bulk-result role="status"></div></td>
 </tr>
 <?php endforeach; ?></tbody></table></div><?php endif; ?>
</div>
<?php if($listing['pages']>1): ?><nav class="d-flex flex-wrap gap-2 mt-3" aria-label="Trang chiến dịch nguồn"><?php for($n=max(1,$listing['page']-2);$n<=min($listing['pages'],$listing['page']+2);++$n): ?><a class="btn btn-outline-secondary" href="<?= e(url('/admin/integrations/campaigns').'?'.http_build_query(['page'=>$n,'state'=>$state])) ?>" <?= $listing['page']===$n?'aria-current="page"':'' ?>><?= $n ?></a><?php endfor; ?></nav><?php endif; ?>
<?php endif; ?>
<script src="<?= e(url('/assets/js/integrations.js')) ?>" defer></script>
<script src="<?= e(url('/assets/js/admin-bulk.js')) ?>" defer></script>

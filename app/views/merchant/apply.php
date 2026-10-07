<?php use App\Core\Session; ?>
<div class="row">
    <div class="col-lg-7 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-body p-4">
                <h1 class="h4">Đăng ký / nhận quyền sở hữu Merchant</h1>
                <p class="text-muted">Sau khi Admin duyệt, tài khoản sẽ được nâng lên role MERCHANT và có quyền tạo Official Deal, Campaign và xem thống kê.</p>
                <form method="post" action="<?= e(url('/merchant/apply')) ?>">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <div class="form-group">
                        <label>Hình thức</label>
                        <select name="mode" id="merchant-apply-mode" class="form-control">
                            <option value="claim">Nhận quyền Merchant đã có trên hệ thống</option>
                            <option value="new">Tạo hồ sơ Merchant mới</option>
                        </select>
                    </div>
                    <div id="merchant-claim-block">
                        <div class="form-group">
                            <label>Merchant chưa có chủ</label>
                            <select name="merchant_id" class="form-control">
                                <option value="">-- Chọn Merchant --</option>
                                <?php foreach ($claimable as $merchant): ?>
                                    <option value="<?= (int)$merchant['id'] ?>"><?= e((string)$merchant['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($claimable === []): ?><small class="text-muted">Hiện không có Merchant UNCLAIMED.</small><?php endif; ?>
                        </div>
                    </div>
                    <div id="merchant-new-block" class="d-none">
                        <div class="form-group"><label>Tên Merchant</label><input name="name" maxlength="160" class="form-control"></div>
                        <div class="form-group"><label>Website</label><input name="website_url" type="url" class="form-control" placeholder="https://..."></div>
                        <div class="form-group"><label>Mô tả</label><textarea name="description" class="form-control" rows="4"></textarea></div>
                    </div>
                    <button class="btn btn-primary" type="submit">Gửi yêu cầu Merchant</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5 mb-4">
        <div class="card shadow-sm h-100"><div class="card-body p-4">
            <h2 class="h5">Yêu cầu của tôi</h2>
            <?php if ($applications === []): ?><p class="text-muted">Chưa có yêu cầu nào.</p><?php endif; ?>
            <?php foreach ($applications as $item): ?>
                <div class="border rounded p-3 mb-2">
                    <strong><?= e((string)$item['name']) ?></strong><br>
                    <span class="badge badge-<?= e(match((string)$item['status']){'APPROVED'=>'success','PENDING'=>'warning','REJECTED'=>'danger','LOCKED'=>'dark',default=>'secondary'}) ?>"><?= e(merchant_status_label((string)$item['status'])) ?></span>
                    <?php if (!empty($item['review_note'])): ?><div class="small text-muted mt-2">Ghi chú: <?= e((string)$item['review_note']) ?></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
  var mode=document.getElementById('merchant-apply-mode'), claim=document.getElementById('merchant-claim-block'), fresh=document.getElementById('merchant-new-block');
  if(!mode)return; function sync(){var isNew=mode.value==='new';claim.classList.toggle('d-none',isNew);fresh.classList.toggle('d-none',!isNew);} mode.addEventListener('change',sync);sync();
});
</script>

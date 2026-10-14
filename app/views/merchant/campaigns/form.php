<?php use App\Core\Session; $isEdit=$mode==='edit'; ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4"><?= $isEdit?'Sửa Campaign':'Tạo Campaign' ?></h1>
                <form method="post"
                    action="<?= e($isEdit?url('/merchant/campaigns/'.(int)$campaign['id'].'/edit'):url('/merchant/campaigns')) ?>">
                    <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                    <?php if(!$isEdit):?><div class="form-group"><label>Merchant</label><select name="merchant_id"
                            class="form-control" required><?php foreach($shops as $shop):?><option
                                value="<?= (int)$shop['id'] ?>"><?= e((string)$shop['name']) ?></option>
                            <?php endforeach;?></select></div><?php else:?><div class="form-group">
                        <label>Merchant</label><input class="form-control" disabled
                            value="<?= e((string)$campaign['merchant_name']) ?>"></div><?php endif;?>
                    <div class="form-group"><label>Tên Campaign</label><input class="form-control" name="name"
                            maxlength="200" required value="<?= e((string)($old['name']??'')) ?>"></div>
                    <div class="form-group"><label>Mô tả</label><textarea class="form-control" name="description"
                            rows="4"><?= e((string)($old['description']??'')) ?></textarea></div>
                    <div class="form-row">
                        <div class="form-group col-md-6"><label>Bắt đầu</label><input type="datetime-local"
                                class="form-control" name="starts_at" required
                                value="<?= e((string)($old['starts_at']??'')) ?>"></div>
                        <div class="form-group col-md-6"><label>Kết thúc</label><input type="datetime-local"
                                class="form-control" name="ends_at" required
                                value="<?= e((string)($old['ends_at']??'')) ?>"></div>
                    </div>
                    <div class="form-group"><label>Trạng thái</label><select class="form-control" name="status">
                            <option value="DRAFT" <?= ($old['status']??'DRAFT')==='DRAFT'?'selected':'' ?>>Bản nháp
                            </option>
                            <option value="PUBLISHED" <?= ($old['status']??'')==='PUBLISHED'?'selected':'' ?>>Xuất bản
                            </option>
                        </select><small class="form-text text-muted">Campaign chỉ hiển thị public khi PUBLISHED; coupon
                            bên trong vẫn phải ACTIVE.</small></div>
                    <button class="btn btn-primary"><?= $isEdit?'Lưu Campaign':'Tạo Campaign' ?></button><a
                        class="btn btn-link" href="<?= e(url('/merchant/campaigns')) ?>">Hủy</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php use App\Core\Session; $isEdit=$mode==='edit'; ?>
<div class="row justify-content-center">
    <div class="col-xl-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h3 mb-1"><?= $isEdit?'Sửa Official Coupon':'Tạo Official Coupon' ?></h1>
                <p class="text-muted mb-0">Official Deal của Merchant vẫn chuyển về PENDING để Admin kiểm duyệt.</p>
            </div><a class="btn btn-outline-secondary" href="<?= e(url('/merchant/coupons')) ?>">Danh sách</a>
        </div>
        <?php if(!empty($errors['auth'])):?><div class="alert alert-danger auto-dismiss-alert" data-timeout="20000">
            <?= e($errors['auth']) ?></div><?php endif;?>
        <form id="coupon-create-form" method="post"
            action="<?= e($isEdit?url('/merchant/coupons/'.(int)$coupon['id'].'/edit'):url('/merchant/coupons')) ?>"
            class="card shadow-sm">
            <div class="card-body p-4"><input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Merchant <span class="text-danger">*</span></label><select
                            class="form-control" name="merchant_id" required><?php foreach($shops as $shop):?><option
                                value="<?= (int)$shop['id'] ?>"
                                <?= (int)($old['merchant_id']??0)===(int)$shop['id']?'selected':'' ?>>
                                <?= e((string)$shop['name']) ?></option><?php endforeach;?></select></div>
                    <div class="form-group col-md-6"><label>Danh mục <span class="text-danger">*</span></label><select
                            class="form-control <?= isset($errors['category_id'])?'is-invalid':'' ?>" name="category_id"
                            required>
                            <option value="">-- Chọn danh mục --</option><?php foreach($categories as $cat):?><option
                                value="<?= $cat->id ?>" <?= (int)($old['category_id']??0)===$cat->id?'selected':'' ?>>
                                <?= e($cat->name) ?></option><?php endforeach;?>
                        </select><?php if(isset($errors['category_id'])):?><div class="invalid-feedback">
                            <?= e($errors['category_id']) ?></div><?php endif;?></div>
                </div>
                <div class="form-group"><label>Tên coupon <span class="text-danger">*</span></label><input
                        class="form-control <?= isset($errors['title'])?'is-invalid':'' ?>" name="title" maxlength="200"
                        required value="<?= e((string)($old['title']??'')) ?>"><?php if(isset($errors['title'])):?><div
                        class="invalid-feedback"><?= e($errors['title']) ?></div><?php endif;?></div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Mã coupon</label><input
                            class="form-control <?= isset($errors['code'])?'is-invalid':'' ?>" name="code"
                            maxlength="100"
                            value="<?= e((string)($old['code']??'')) ?>"><?php if(isset($errors['code'])):?><div
                            class="invalid-feedback"><?= e($errors['code']) ?></div><?php endif;?></div>
                    <div class="form-group col-md-6"><label>Promotion URL</label><input type="url"
                            class="form-control <?= isset($errors['promotion_url'])?'is-invalid':'' ?>"
                            name="promotion_url"
                            value="<?= e((string)($old['promotion_url']??'')) ?>"><?php if(isset($errors['promotion_url'])):?>
                        <div class="invalid-feedback"><?= e($errors['promotion_url']) ?></div><?php endif;?><small
                            class="form-text text-muted">Cần ít nhất mã coupon hoặc URL.</small></div>
                </div>
                <div class="form-group"><label>Mô tả</label><textarea class="form-control" name="description"
                        rows="3"><?= e((string)($old['description']??'')) ?></textarea></div>
                <div class="form-group"><label>Điều kiện sử dụng <span class="text-danger">*</span></label><textarea
                        class="form-control <?= isset($errors['terms'])?'is-invalid':'' ?>" name="terms" rows="4"
                        required><?= e((string)($old['terms']??'')) ?></textarea><?php if(isset($errors['terms'])):?>
                    <div class="invalid-feedback"><?= e($errors['terms']) ?></div><?php endif;?></div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Loại giảm <span class="text-danger">*</span></label><select
                            id="discount_type" class="form-control"
                            name="discount_type"><?php foreach(['PERCENTAGE'=>'Phần trăm','FIXED_AMOUNT'=>'Số tiền','FREE_SHIPPING'=>'Free shipping','BUY_X_GET_Y'=>'Mua X tặng Y','GIFT'=>'Quà tặng','CASHBACK'=>'Hoàn tiền'] as $v=>$l):?>
                            <option value="<?= e($v) ?>"
                                <?= ($old['discount_type']??'PERCENTAGE')===$v?'selected':'' ?>><?= e($l) ?></option>
                            <?php endforeach;?></select></div>
                    <div class="form-group col-md-4" id="discount-value-group"><label id="discount-value-label">Giá trị
                            giảm</label><input id="discount_value" type="number" step="0.01" min="0"
                            class="form-control <?= isset($errors['discount_value'])?'is-invalid':'' ?>"
                            name="discount_value"
                            value="<?= e((string)($old['discount_value']??'')) ?>"><?php if(isset($errors['discount_value'])):?>
                        <div class="invalid-feedback"><?= e($errors['discount_value']) ?></div><?php endif;?></div>
                    <div class="form-group col-md-4" id="cashback-unit-group"><label>Đơn vị hoàn tiền</label><select
                            id="cashback_unit" name="cashback_unit" class="form-control">
                            <option value="PERCENTAGE"
                                <?= ($old['cashback_unit']??'PERCENTAGE')==='PERCENTAGE'?'selected':'' ?>>Phần trăm
                            </option>
                            <option value="FIXED_AMOUNT"
                                <?= ($old['cashback_unit']??'')==='FIXED_AMOUNT'?'selected':'' ?>>Số tiền</option>
                        </select></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4"><label>Đơn tối thiểu (VND)</label><input type="number" min="0"
                            step="1000" class="form-control" name="min_order_amount"
                            value="<?= e((string)($old['min_order_amount']??'0')) ?>"></div>
                    <div class="form-group col-md-4"><label>Giảm tối đa (VND)</label><input type="number" min="0"
                            step="1000" class="form-control" name="max_discount_amount"
                            value="<?= e((string)($old['max_discount_amount']??'')) ?>"></div>
                    <div class="form-group col-md-4"><label>Hình thức sử dụng</label><select name="usage_channel"
                            class="form-control">
                            <option value="ONLINE" <?= ($old['usage_channel']??'ONLINE')==='ONLINE'?'selected':'' ?>>
                                Online</option>
                            <option value="OFFLINE" <?= ($old['usage_channel']??'')==='OFFLINE'?'selected':'' ?>>Offline
                            </option>
                            <option value="BOTH" <?= ($old['usage_channel']??'')==='BOTH'?'selected':'' ?>>Online +
                                Offline</option>
                        </select></div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Bắt đầu <span class="text-danger">*</span></label><input
                            id="starts_at" type="datetime-local" name="starts_at"
                            class="form-control <?= isset($errors['starts_at'])?'is-invalid':'' ?>" required
                            value="<?= e((string)($old['starts_at']??'')) ?>">
                        <div id="starts-at-feedback" class="small datetime-feedback mt-1"></div>
                    </div>
                    <div class="form-group col-md-6"><label>Kết thúc <span class="text-danger">*</span></label><input
                            id="ends_at" type="datetime-local" name="ends_at"
                            class="form-control <?= isset($errors['ends_at'])?'is-invalid':'' ?>" required
                            value="<?= e((string)($old['ends_at']??'')) ?>">
                        <div id="ends-at-feedback" class="small datetime-feedback mt-1"></div>
                    </div>
                </div>
                <div class="alert alert-light border small"><strong>Lưu ý:</strong> khi Merchant sửa nội dung ưu
                    đãi/điều kiện/thời hạn, coupon quay về PENDING và các vote cũ bị xóa để tránh giữ xác nhận cho nội
                    dung đã thay đổi.</div>
                <button class="btn btn-primary"
                    type="submit"><?= $isEdit?'Lưu và gửi duyệt lại':'Tạo Official Coupon' ?></button><a
                    class="btn btn-link" href="<?= e(url('/merchant/coupons')) ?>">Hủy</a>
            </div>
        </form>
    </div>
</div>
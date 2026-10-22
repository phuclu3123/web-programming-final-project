<?php use App\Core\Session; $pageScripts = ['/js/merchant-approval-confirm.js']; ?>
<h1 class="h3">Đơn đăng ký quyền quản lý Merchant/Shop</h1>
<p class="text-muted">Duyệt hoặc thu hồi theo từng User–Shop. Mã đơn do hệ thống sinh; bằng chứng do người đăng ký cung cấp.</p>
<h2 class="h5 mt-4">Yêu cầu đăng ký</h2>
<div class="table-responsive">
  <table class="table table-hover table-bordered bg-white">
    <thead>
      <tr>
        <th>Mã đơn</th><th>User</th><th>Merchant</th><th>Loại</th>
        <th>Trạng thái</th><th>Bằng chứng do User cung cấp</th><th>Xử lý</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($requests as $r): ?>
        <tr>
          <td class="text-nowrap"><strong>#<?= (int)$r['id'] ?></strong></td>
          <td><?= e((string)$r['full_name']) ?><div class="small text-muted"><?= e((string)$r['email']) ?></div></td>
          <td><?= e((string)$r['merchant_name']) ?></td>
          <td><?= e((string)$r['request_type']) ?></td>
          <td><span class="badge bg-<?= $r['status']==='APPROVED' ? 'success' : ($r['status']==='PENDING' ? 'warning' : 'secondary') ?>"><?= e((string)$r['status']) ?></span></td>
          <td>
            <span style="overflow-wrap:anywhere"><?= e((string)($r['evidence_reference'] ?: 'Chưa cung cấp')) ?></span>
            <?php if ($r['status'] === 'PENDING' && (int)($r['duplicate_evidence_users'] ?? 0) > 0): ?>
              <div class="mt-2">
                <span class="badge bg-warning text-dark">Bằng chứng đã được <?= (int)$r['duplicate_evidence_users'] ?> User khác nộp trước đó cho cùng Shop</span>
                <div class="small text-muted mt-1">Đơn mới cần xác minh riêng; đơn đã duyệt trước đó không bị ảnh hưởng.</div>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($r['status'] === 'PENDING'): ?>
              <form action="<?= e(url('/admin/merchant-requests/'.(int)$r['id'].'/review')) ?>"
                    method="post"
                    class="merchant-review-form"
                    data-has-duplicate="<?= $r['status']==='PENDING' && (int)($r['duplicate_evidence_users'] ?? 0)>0 ? '1' : '0' ?>"
                    data-request-id="<?= (int)$r['id'] ?>"
                    data-user-name="<?= e((string)$r['full_name']) ?>"
                    data-merchant-name="<?= e((string)$r['merchant_name']) ?>"
                    data-evidence="<?= e((string)($r['evidence_reference'] ?? '')) ?>"
                    data-duplicate-users="<?= (int)($r['duplicate_evidence_users'] ?? 0) ?>">
                <input type="hidden" name="_token" value="<?= e(Session::csrfToken()) ?>">
                <input type="hidden" name="duplicate_confirmed" value="0">
                <input class="form-control form-control-sm mb-1" name="note" maxlength="2000" placeholder="Ghi kết quả xác minh từng User" aria-label="Ghi chú xác minh đơn #<?= (int)$r['id'] ?>">
                <div class="small text-danger d-none merchant-note-warning mb-2" role="alert">Hãy ghi kết quả xác minh riêng cho User này trước khi duyệt.</div>
                <button type="submit" class="btn btn-success btn-sm" name="action" value="APPROVE">Duyệt</button>
                <button type="submit" class="btn btn-outline-danger btn-sm" name="action" value="REJECT">Từ chối</button>
              </form>
            <?php else: ?>
              <?= e((string)($r['review_note'] ?? '')) ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($requests)): ?>
        <tr><td colspan="7" class="text-muted text-center">Chưa có đơn đăng ký.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<h2 class="h5 mt-4">Quyền quản lý đã cấp</h2>
<div class="table-responsive"><table class="table table-striped bg-white"><thead><tr><th>User</th><th>Shop</th><th>Quyền</th><th>Hành động</th></tr></thead><tbody>
<?php foreach($memberships as $m):?><tr><td><?=e((string)$m['full_name'])?></td><td><?=e((string)$m['merchant_name'])?></td><td><?=e((string)$m['status'])?></td><td><?php if($m['status']==='ACTIVE'):?><form method="post" action="<?=e(url('/admin/merchant-memberships/'.(int)$m['user_id'].'/'.(int)$m['merchant_id'].'/revoke'))?>"><input type="hidden" name="_token" value="<?=e(Session::csrfToken())?>"><input aria-label="Ghi chú xét duyệt" type="text" class="form-control form-control-sm d-inline-block" style="max-width:240px" name="note" required placeholder="Lý do thu hồi quyền"><button class="btn btn-sm btn-outline-danger">Thu hồi</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div>


<!-- Second confirmation for duplicate evidence: Bootstrap 5.3.8 modal. -->
<div class="modal fade" id="merchantDuplicateApprovalModal" tabindex="-1" role="dialog"
     aria-labelledby="merchantDuplicateApprovalTitle" aria-describedby="merchantDuplicateApprovalDescription" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="merchantDuplicateApprovalTitle">
          <i class="fas fa-triangle-exclamation text-warning me-1" aria-hidden="true"></i>
          Xác nhận duyệt đơn có bằng chứng trùng
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
      </div>
      <div class="modal-body" id="merchantDuplicateApprovalDescription">
        <div class="alert alert-warning" role="alert">
          Bằng chứng này đã xuất hiện trong đơn trước của User khác xin quản lý cùng Shop.
          Hãy đảm bảo đã xác minh độc lập quyền đại diện của từng người.
        </div>
        <dl class="row mb-2">
          <dt class="col-sm-4">Mã đơn</dt><dd class="col-sm-8" id="duplicateApprovalRequest"></dd>
          <dt class="col-sm-4">Người đăng ký</dt><dd class="col-sm-8" id="duplicateApprovalUser"></dd>
          <dt class="col-sm-4">Merchant/Shop</dt><dd class="col-sm-8" id="duplicateApprovalShop"></dd>
          <dt class="col-sm-4">Bằng chứng</dt><dd class="col-sm-8 text-break" id="duplicateApprovalEvidence"></dd>
        </dl>
        <p class="small text-muted" id="duplicateApprovalCount"></p>
        <div class="form-check form-check">
          <input type="checkbox" class="form-check-input" id="duplicateApprovalAck">
          <label class="form-check-label" for="duplicateApprovalAck">
            Tôi đã kiểm tra độc lập quyền quản lý Shop của người đăng ký này và muốn phê duyệt đơn.
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Quay lại</button>
        <button type="button" id="duplicateApprovalConfirm" class="btn btn-warning" disabled>
          <i class="fas fa-check-circle me-1" aria-hidden="true"></i> Xác nhận phê duyệt
        </button>
      </div>
    </div>
  </div>
</div>

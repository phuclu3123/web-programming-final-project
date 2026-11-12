<?php /* Nạp Swagger local; đặc tả chỉ lấy qua route dành cho admin. */ ?><h1 class="h3">API & nghiệm thu</h1>
<p class="text-muted">Swagger dùng phiên đăng nhập hiện tại. Thao tác ghi thay đổi dữ liệu thật: chạy kịch bản thu hồi/phân quyền trong database kiểm thử riêng.</p>
<p class="small">Mỗi yêu cầu ghi cần CSRF; cookie phiên được trình duyệt gửi tự động. Sau khi đăng nhập lại, tải lại tài liệu để lấy token mới.</p>
<a class="btn btn-outline-primary mb-3" href="<?= e(url('/admin/api-spec')) ?>">Tải OpenAPI JSON</a>
<link rel="stylesheet" href="<?= e(url('/assets/vendor/swagger-ui/swagger-ui.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/api-docs.css')) ?>">
<div id="swagger-ui" data-spec-url="<?= e(url('/admin/api-spec')) ?>"></div>
<script src="<?= e(url('/assets/vendor/swagger-ui/swagger-ui-bundle.js')) ?>" defer></script>
<script src="<?= e(url('/assets/js/api-docs.js')) ?>" defer></script>

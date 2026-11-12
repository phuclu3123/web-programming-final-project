# Bắt đầu với CouponHub

## Clone và chạy local

Đọc [README.md](README.md). Dự án không yêu cầu `npm install` hoặc `composer install`; asset Font Awesome/Swagger đã có trong Git.

Chưa có `.env` thì copy `.env.example` và điền cấu hình. Nếu đã có `.env` hoạt động, giữ nguyên file và chỉ bổ sung biến còn thiếu.

## Database

- DB mới, trống: làm theo [database/README.md](database/README.md).
- DB hiện có: backup trước, chọn quy trình trong [DB_UPGRADE_20261109.md](docs/DB_UPGRADE_20261109.md) và [DB_MIGRATION_21_TABLES.md](docs/DB_MIGRATION_21_TABLES.md).
- Nhận bản đầy đủ của Phúc: import snapshot mới được bàn giao riêng vào DB trống; không chạy schema/demo/seed tiếp.
- Không chạy tất cả migration theo tên ngày: có file lịch sử chỉ dành cho schema cũ và kiểm thử.

Baseline hiện tại: 21 bảng, 6 view, 21 procedure. Kiểm tra cả nội dung và quan hệ, không chỉ số bảng.

## Deploy cho Quang

Làm theo [DEPLOYMENT.md](docs/DEPLOYMENT.md). Gói SQL cũ có `external_offers=0` chưa chứa 50 mục nguồn vừa lưu; cần xuất snapshot mới trước khi bàn giao. Snapshot dữ liệu và `.env` gửi riêng, không commit.

## Tài liệu và source cũ

[docs/README.md](docs/README.md) phân biệt tài liệu hiện hành với báo cáo lịch sử. Source báo cáo được giữ trong `Latex/`. Các ZIP tải bài giảng, giao diện User mảng cũ và build/cache tạm được chuyển ra `../temp/CouponHub_cleanup_20261109/`, ngoài webroot.

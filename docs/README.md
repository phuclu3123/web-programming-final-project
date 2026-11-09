# Danh mục tài liệu CouponHub

## Dùng để phát triển và triển khai

- [Kiến trúc](ARCHITECTURE.md): MVC, entry point và trách nhiệm từng tầng.
- [Quy ước code/comment](CODE_CONVENTIONS.md).
- [Clone và deploy](DEPLOYMENT.md): dependency, asset, SQL, env và nghiệm thu hosting.
- [Database](../database/README.md), [quy ước dữ liệu](../database/RULES.md).
- [Nâng cấp DB cũ](DB_UPGRADE_20261109.md), [baseline 21 bảng](DB_MIGRATION_21_TABLES.md).
- [Thiết lập provider](PROVIDER_SETUP.md), [xác thực](AUTH_SETUP.md).
- [Kiểm tra chất lượng](QUALITY_CHECKS.md).

## Bằng chứng gần nhất

- [Dọn source và cấu trúc 09-10/11/2026](CODE_CLEANUP_20261110.md).
- [Nhập 50 mục ACCESSTRADE vào hàng chờ](ACCESSTRADE_IMPORT_20261109.md).
- [Đối chiếu tài liệu ACCESSTRADE](ACCESSTRADE_DOCUMENT_AUDIT_20261109.md).
- [Liên kết Facebook/Google](FACEBOOK_GOOGLE_ACCEPTANCE_20261109.md).
- [Google One Tap](GOOGLE_ONE_TAP_ACCEPTANCE_20261109.md).

## Báo cáo lịch sử

Các file `INTEGRATION_*`, `PHASE*`, `PHUC_PHASE*`, `REVIEW_*`, `UI_*` ghi lại từng đợt phát triển. Số bảng, URL, số test và trạng thái provider trong đó thuộc thời điểm ghi nhận; không dùng thay hướng dẫn triển khai hiện hành.

Giữ các báo cáo này để truy vết thay đổi và dẫn chiếu kiểm thử. Thư mục bài giảng tải về trước đây được lưu ngoài repo tại `../temp/CouponHub_cleanup_20261109/archived/drive-download-20261010T105110Z-1-001/` tính từ gốc ứng dụng. Giao diện User prototype và asset cũ được lưu cùng gói, không còn là source chạy.

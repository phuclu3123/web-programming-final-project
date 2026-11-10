# CouponHub Database — 24 bảng (10/11/2026)

**Bản chuẩn hiện tại:** `database/schema.sql` cài mới với **24 BASE TABLE / 6 VIEW / 21 PROCEDURE**. `comments` chứa bình luận coupon hoặc bài viết; phản hồi bài viết dùng `parent_comment_id` và `root_comment_id`. `comment_reactions` lưu lượt thích; `notifications` lưu sự kiện và trạng thái đã đọc theo tài khoản.

**Không import `schema.sql` vào database đang có dữ liệu** — bản DDL fresh install này có `DROP TABLE` và tạm tắt kiểm tra FK. `demo.sql` cũng chỉ dùng cho DB mới trống, một lần.

## Cài đặt mới

1. Tạo database mới, trống, sử dụng `utf8mb4` / `utf8mb4_vietnamese_ci`.
2. Chọn đúng database rồi import `database/schema.sql`.
3. Tùy chọn import `database/demo.sql` (6 tài khoản demo, 12 shop, 32 coupon, 2 campaign; không dùng trên môi trường production).
4. Thiết lập `COUPONHUB_DB`, `COUPONHUB_HOST`, `COUPONHUB_USER`, `COUPONHUB_PASSWORD`, hoặc `config/local.php` không commit. Xem `docs/DEMO_DATA.md` cho tài khoản thử.

## Nâng cấp từ database hiện có

- Bản 23 bảng đã có reply/react: backup rồi chạy **`20261110_comment_notifications.sql`**. Migration lưu thông báo và trạng thái đã đọc vào MySQL, đồng thời bổ sung thông báo cho reply/thả tim đã có; có thể chạy lại mà không reset đã đọc. Sau nâng cấp có 24 bảng.

- Bản 22 bảng đã có `external_campaigns`: backup rồi chạy **`20261110_comment_threads_reactions.sql`** để thêm reply và thả tim. Bình luận hiện có giữ nguyên và vẫn là bình luận gốc. Sau nâng cấp có 23 bảng; không import lại full/schema vào DB đang chạy.
- Nếu dùng full SQL 21 bảng đã gửi Quang: import vào DB mới trống, sau đó chạy lần lượt `20261110_external_campaigns.sql`, `20261110_comment_threads_reactions.sql`, `20261110_comment_notifications.sql`.

- Bản đang chạy 21 bảng hoặc SQL bàn giao tại commit `601e0b1`: backup rồi chỉ áp dụng **`20261110_external_campaigns.sql`**. Migration thêm hàng chờ chiến dịch ACCESSTRADE, giữ nguyên shop/coupon/chiến dịch hiện có. Sau bước này có 22 bảng, 6 view, 21 procedure.
- Các bước bên dưới mô tả nâng core cũ về 21 bảng; sau đó áp dụng migration nguồn chiến dịch nói trên.

Đọc **[docs/DB_MIGRATION_21_TABLES.md](../docs/DB_MIGRATION_21_TABLES.md)**. Backup dữ liệu, dừng mọi thao tác ghi, nâng cấp DB và PHP source trong cùng đợt triển khai.

- Database 22 bảng chứa `article_comments`: import migration **`20261107_unify_comments.sql`** để chuyển dữ liệu sang bảng `comments`, kiểm chứng rồi loại bỏ bảng cũ.
- Database 20 bảng core: nếu thiếu thì thêm `external_offers` bằng `20261105_external_offers.sql`, sau đó chạy `20261107_unify_comments.sql` để nâng cấu trúc `comments`, không tạo lại `article_comments`.
- `20261106_article_comments.sql` là migration **lịch sử** cho phiên bản 22 bảng, **không chạy sau baseline 21 bảng**; hai migration Phase 3/4 cũ cũng chỉ dành cho schema trước hợp nhất.
- Database rất cũ chưa được hợp nhất `coupon_status_history` cần xử lý bằng quy trình chuyển đổi core riêng trước khi dùng migration hiện tại.
- Đã bổ sung quy trình cho DB cũ 23 bảng: chạy `20261109_legacy_core_compat.sql`,
  sau đó `20261107_unify_comments.sql` trên bản sao đã backup. Xem [hướng dẫn nâng cấp](../docs/DB_UPGRADE_20261109.md).

## Kiểm tra hợp đồng schema

Các test hợp đồng schema và `sql-install.py` được lưu trong bộ `CouponHub_DevTools` riêng, không thuộc repo web. Xem [hướng dẫn kiểm chứng](../docs/QUALITY_CHECKS.md). Kiểm tra tĩnh không thay thế import SQL/PHP/HTTP trên MariaDB; runner tích hợp chỉ được dùng với DB thử riêng, không dùng cho DB làm việc.

## Dữ liệu trong demo.sql

- **6 tài khoản**: 1 Admin, 3 Merchant, 2 User; email giả `.test`, mật khẩu bcrypt.
- **12 shop**, 12 membership và yêu cầu shop đã duyệt, 8 danh mục.
- **32 coupon**: 26 đang hoạt động, 1 sắp bắt đầu, 1 hết hạn, 2 chờ duyệt, 1 bị từ chối, 1 tạm tắt. Mã bị từ chối có publication DISABLED theo nghiệp vụ hiện tại.
- **2 chiến dịch**, 10 liên kết coupon; 6 lượt lưu, 6 bình chọn, 4 bình luận, 2 báo cáo.
- **4 cấp độ**, 3 quy tắc điểm, 2 giao dịch điểm, 81 hoạt động thường và 31 lịch sử kiểm duyệt hợp nhất vào `activity_logs`.

Mốc thời gian được tính lại từ lúc import: phần lớn mã có hiệu lực khoảng 90 ngày, hai mã sắp hết hạn còn khoảng 3/5 ngày, mã sắp bắt đầu sau khoảng 7 ngày. Không phải thay ngày thủ công khi gửi file vào dịp khác.

Mọi coupon có `DEMO_`/`[DEMO]`, không dùng thanh toán thật. Bộ SQL được tạo từ fixture trên database thử riêng, **không xuất dữ liệu của database đang dùng**. Không có tài khoản OAuth hay token đặt lại mật khẩu. Bảng `articles` để trống sau khi nạp demo; đăng nội dung biên tập bằng lệnh riêng bên dưới. Không tạo bình luận giả.

Tên đăng nhập, mật khẩu và phân quyền shop nằm tại [docs/DEMO_DATA.md](../docs/DEMO_DATA.md). Đây là tài khoản thử có mật khẩu công khai; không dùng nguyên bộ demo cho môi trường công khai.

## Lịch sử nâng cấp và các tài liệu cũ

Quy trình hiện hành dùng `docs/DB_MIGRATION_21_TABLES.md`. Hướng dẫn 22 bảng lỗi thời đã được lược khỏi gói FINAL. Không chạy migration lịch sử `20261106_article_comments.sql` sau khi đã dùng schema 21 bảng. Bắt đầu với file `00_BAT_DAU_O_DAY.md` ở thư mục gốc.

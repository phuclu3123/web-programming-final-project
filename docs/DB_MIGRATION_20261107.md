> **Lưu trữ lịch sử (22 bảng).** Hướng dẫn hiện tại cho schema 21 bảng: [DB_MIGRATION_21_TABLES.md](DB_MIGRATION_21_TABLES.md). Không làm theo bước import `20261106_article_comments.sql` sau khi đã chuyển sang schema 21 bảng.

# CouponHub — đồng bộ database đã tối ưu (07/11/2026)

## Kết luận thiết kế

- **20 bảng core** trong `schema_migrated_portable.sql` do nhóm cung cấp.
- **2 bảng bổ sung** phải giữ: `article_comments`, `external_offers`, vì `main`
  đã có Repository/API/Controller sử dụng hai bảng này.
- **Tổng canonical:** 22 BASE TABLE, 6 VIEW, 21 PROCEDURE.
- **Bảng loại bỏ:** `coupon_status_history`; dữ liệu chuyển vào `activity_logs`.
- **5 cột loại bỏ:** `articles.category_id`, `articles.cover_path`,
  `coupons.currency`, `coupons.image_path`, `coupons.qr_image_path`.
- `oauth_accounts.provider` giữ `facebook` cùng `github`,`google` do `main` có callback Facebook.

## A — Máy đang có `couponhub_migration_test` (20 bảng) và dữ liệu đã kiểm tra

**Đây là trường hợp tương ứng dữ liệu bạn đã kiểm tra trong phpMyAdmin.**

1. Backup toàn bộ `couponhub_migration_test` (schema + dữ liệu) trước khi thử.
2. Chọn đúng database `couponhub_migration_test` trong phpMyAdmin.
3. Import lần lượt `database/migrations/20261105_external_offers.sql`
   và `database/migrations/20261106_article_comments.sql`. Chúng chỉ bổ sung
   bảng nếu thiếu; không thay bảng `activity_logs`, `coupons` hay lịch sử cũ.
   Sau đó import `database/migrations/20261107_facebook_provider_compat.sql` để
   bổ sung `facebook` vào ENUM provider (source `main` đã có callback Facebook).
4. Kiểm tra bằng SQL read-only:

```sql
SELECT TABLE_TYPE, COUNT(*) AS total
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE()
GROUP BY TABLE_TYPE;

SELECT ROUTINE_TYPE, COUNT(*) AS total
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA=DATABASE()
GROUP BY ROUTINE_TYPE;

SELECT COUNT(*) AS total_logs,
       SUM(legacy_history_id IS NOT NULL) AS history_migrated
FROM activity_logs;

SELECT COUNT(*) AS obsolete_history_tables
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='coupon_status_history';
```

Kỳ vọng trên snapshot **trước khi tương tác thêm**: `22 BASE TABLE`, `6 VIEW`,
`21 PROCEDURE`, `112 activity_logs`, `31 migrated history`, 0 bảng
`coupon_status_history`. Con số dữ liệu có thể tăng khi app được sử dụng.

5. Cấu hình `COUPONHUB_DB=couponhub_migration_test` trong `.env` local của
   **bản source thử**. Tuyệt đối không commit `.env`.
6. Thử login, tìm kiếm tiếng Việt, coupon detail, yêu thích, vote, báo cáo,
   chiến dịch, notification; test bình luận bài viết và hàng chờ provider khi có
   data/credentials test tương ứng.

**Không import lại `schema.sql` hoặc `demo.sql` ở bước này.**

## B — Cài mới trên database trống

1. Tạo database MỚI, trống với `utf8mb4` / `utf8mb4_vietnamese_ci`.
2. Chọn DB đó rồi import `database/schema.sql`; file này dành riêng cho
   fresh-install, có `DROP TABLE` / tạm tắt kiểm tra FK.
3. Nếu cần tài khoản giả, import `database/demo.sql` **một lần**. Import demo
   sẽ từ chối khi có dữ liệu.
4. Kết nối app với DB mới; kiểm tra schema, 112 hoạt động (81 + 31 lịch sử),
   32 coupon, 6 user, 12 shop, 2 chiến dịch.

## C — Máy hiện chỉ có schema cũ, 21–23 bảng, chưa migration

**Chưa cung cấp lệnh DROP/ALTER tự động ở đây.** Migration phải giữ đúng dữ liệu
và kiểm tra ràng buộc/row parity trước khi xóa cột/bảng. Tạo một bản sao database
và thực hiện quá trình copy/verify đã nghiệm thu của nhóm để tạo **20 bảng core**;
sau đó làm theo mục A. Không tự import `schema.sql` hay chạy `DROP TABLE
coupon_status_history` lên database gốc. Các script migration Phase 3/4 cũ
không áp dụng lại vào schema đã hợp nhất vì chúng còn tham chiếu bảng cũ.

## Phạm vi sửa code

- `CouponRepository` dùng đúng 17 tham số `sp_submit_coupon`, không truyền
  cột hình ảnh đã bỏ, và ghi audit trạng thái vào `activity_logs`.
- `AdminCouponRepository` ghi status audit mới.
- `NotificationRepository` đọc các sự kiện `COUPON_STATUS_CHANGE`.
- `seed_demo.php` và fixture SQL / integration assertions cập nhật bảng lịch sử.
- `schema.sql` giữ 21 routine và view của migration, không hardcode DB;
  `demo.sql` không còn INSERT vào bảng bị loại bỏ.

## Xác thực

Môi trường đóng gói không có MariaDB server và PHP mysqli. Có thể chạy PHP lint,
JavaScript, static contract tests; **không được xem là đã PASS integration MySQL**.
Bộ test có reset database thử `couponhub_merge_test` ở port `13379` — chỉ chạy
khi bạn thực sự sở hữu database thử đó. Không chạy trên dữ liệu thật.

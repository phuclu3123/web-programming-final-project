# BẮT ĐẦU TẠI ĐÂY — CouponHub Database FINAL 21 bảng

**Đây là gói FINAL duy nhất để dùng tiếp.** Không cần các file SQL, patch, ZIP, CJS mà trợ lý đã gửi riêng trước đó.

## A. Nếu tạo database HOÀN TOÀN MỚI, TRỐNG

1. Tạo database trống trên phpMyAdmin, charset `utf8mb4`, collation `utf8mb4_vietnamese_ci`.
2. Chọn đúng database, import `database/schema.sql`.
3. Tùy chọn: import `database/demo.sql` nếu cần **dữ liệu mẫu**, không dùng trên website thật.
4. **Không chạy migration** cho database mới đã tạo từ schema FINAL.

## B. Nếu dùng database thử CŨ `couponhub_migration_test` đã hợp nhất còn 20 bảng

**Trước hết:** backup toàn bộ database; chỉ thử trên **BẢN SAO** của nó, không thao tác trực tiếp trên database đang phục vụ website. Dừng các luồng ghi khi nâng cấp. Chọn đúng bản sao trong phpMyAdmin.

Import theo đúng thứ tự dưới đây, nếu chưa áp dụng từng thay đổi:

1. `database/migrations/20261105_external_offers.sql` — thêm bảng `external_offers` (20 → 21 bảng).
2. `database/migrations/20261107_unify_comments.sql` — bổ sung hỗ trợ bình luận bài viết ngay trong `comments` (vẫn 21 bảng).
3. `database/migrations/20261107_facebook_provider_compat.sql` — **chỉ nếu** cột `oauth_accounts.provider` chưa hỗ trợ `facebook` (không làm đổi số bảng).

**KHÔNG import** `database/schema.sql` hoặc `database/demo.sql` vào database có dữ liệu.
**KHÔNG chạy** các migration lịch sử `20261031_...`, `20261104_...`, `20261106_article_comments.sql` trên database core 20 bảng đã hợp nhất.

## C. Nếu database hiện tại có 22 bảng, bao gồm `article_comments`

Sau khi backup, chỉ chạy `database/migrations/20261107_unify_comments.sql` để chuyển dữ liệu vào `comments` và loại bỏ bảng cũ sau khi xác minh. Chỉ chạy script Facebook nếu provider chưa được hỗ trợ. Đọc `docs/DB_MIGRATION_21_TABLES.md` trước khi thực hiện.

## D. Xác minh trong tab SQL của phpMyAdmin

```sql
SELECT DATABASE() AS database_hien_tai;
SELECT TABLE_TYPE, COUNT(*) AS so_luong
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
GROUP BY TABLE_TYPE;
SELECT COUNT(*) AS bang_cu_con_lai
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('coupon_status_history', 'article_comments');
```

Kỳ vọng: **21 BASE TABLE, 6 VIEW, 21 PROCEDURE**, `bang_cu_con_lai = 0`. Thống kê Procedure:

```sql
SELECT ROUTINE_TYPE, COUNT(*) AS so_luong
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE()
GROUP BY ROUTINE_TYPE;
```

## E. Thử mã nguồn mới trước khi cập nhật GitHub

- Dùng source PHP trong **cùng ZIP này**, làm việc trên bản sao thư mục XAMPP / nhánh `db-migration-quang`.
- Kiểm tra tĩnh: `node --test tests/db-migration-contract.test.cjs`.
- Kiểm tra cú pháp PHP và các chức năng: đăng nhập, coupon, bình luận coupon/bài viết, thông báo, merchant, admin; chỉ test trên database đã nâng cấp.
- Chưa push/merge cho đến khi chạy qua kiểm thử MariaDB trên XAMPP.

## Tại sao ZIP vẫn có các migration cũ?

Một vài file `database/migrations/20261031_...`, `20261104_...`, `20261106_article_comments.sql` **chỉ để bảo lưu lịch sử nâng cấp và cho các bài regression test của nhóm**. KHÔNG dùng chúng cho quy trình 20 → 21 bảng ở mục B. Xóa các file đó khỏi repo sẽ khiến một số test tự động thất bại, nên tôi giữ lại có chủ đích.

Cần chi tiết về dữ liệu và tình huống ngoại lệ: `docs/DB_MIGRATION_21_TABLES.md`.

# CouponHub — cấu trúc 21 bảng (07/11/2026)

## Quyết định thiết kế

Bản canonical cho **cài đặt mới** gồm **21 BASE TABLE, 6 VIEW, 21 PROCEDURE**. Hai loại bình luận dùng chung bảng `comments`:

- `coupon_id IS NOT NULL`, `article_id IS NULL`, `request_id IS NULL`: bình luận coupon.
- `article_id IS NOT NULL`, `coupon_id IS NULL`, `request_id IS NOT NULL`: bình luận bài viết.

Bảng chung giữ `user_id`, `body`, `status`, `created_at`, `updated_at`, có khóa ngoại đến `articles`/`coupons` và CHECK `ck_comment_target`. Khóa UNIQUE `(user_id, request_id)` giữ tính idempotent của yêu cầu đăng bình luận bài viết. Các endpoint/API và đường dẫn URL không thay đổi.

21 bảng: `activity_logs`, `articles`, `campaign_coupons`, `campaigns`, `categories`, `comments`, `coupon_events`, `coupons`, `external_offers`, `favorites`, `levels`, `merchant_memberships`, `merchant_requests`, `merchants`, `oauth_accounts`, `password_reset_tokens`, `point_rules`, `point_transactions`, `reports`, `users`, `votes`.

`article_comments` **không còn là bảng vật lý**. Bảng `coupon_status_history` đã được hợp nhất từ lần nâng cấp trước vào `activity_logs`. Không bỏ `external_offers`: mô-đun nhập ưu đãi bên ngoài đang dùng bảng này.

## Quy trình A — DB đang có **22 bảng** (có `article_comments`)

1. **Sao lưu toàn bộ** database (bao gồm dữ liệu), kiểm tra file backup có thể import. Dùng DB sao chép/test trước. Đưa website và worker viết DB vào bảo trì; ngăn ghi trong toàn bộ quá trình.
2. Kiểm tra đúng database bằng `SELECT DATABASE();` và chạy preflight dưới đây. **Không import lại `schema.sql` hoặc `demo.sql`** vì hai file đó dành cho DB mới.
3. Chọn database đang nâng cấp trong phpMyAdmin/MySQL và import **một file duy nhất**: `database/migrations/20261107_unify_comments.sql`.
4. Migration giữ nguyên ID các bình luận coupon, chuyển **tất cả** bình luận bài viết sang `comments` với ID mới không va chạm, kiểm tra số lượng/nội dung/trạng thái/thời gian, cập nhật tham chiếu ID bên trong audit JSON và chỉ xóa bảng `article_comments` khi đối chiếu dữ liệu thành công.
5. Chạy các câu xác minh hậu migration, sau đó triển khai **PHP source mới cùng lúc** trước khi cho ghi trở lại. Không vận hành source mới trên schema cũ hoặc source cũ trên schema mới.

## Quy trình B — DB **20 bảng core** (chưa có `article_comments` và `external_offers`)

1. Backup, kiểm tra đúng DB và dừng mọi luồng ghi.
2. Nếu thiếu `external_offers`, chạy `database/migrations/20261105_external_offers.sql`.
3. **Không chạy** migration lịch sử `20261106_article_comments.sql`. Chạy thẳng `20261107_unify_comments.sql` để bổ sung cột/ràng buộc vào bảng `comments` đang có (không tạo bảng thứ 22).
4. Nếu chưa áp dụng tương thích Facebook, chạy `20261107_facebook_provider_compat.sql` trên DB test đã backup.
5. Xác minh kết quả và deploy PHP source mới như quy trình A.

## Quy trình C — Cài mới

Tạo database **mới, trống**, UTF8MB4/`utf8mb4_vietnamese_ci`, import `database/schema.sql` trước, `database/demo.sql` sau (tùy chọn). Không chạy các migrations A/B trên DB mới.

## SQL preflight (read-only)

```sql
SELECT DATABASE() AS selected_database;
SELECT TABLE_TYPE, COUNT(*) AS total
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() GROUP BY TABLE_TYPE;
SELECT COUNT(*) AS article_comment_rows FROM article_comments; -- chỉ chạy nếu bảng còn tồn tại
SELECT COUNT(*) AS coupon_comment_rows FROM comments;
SELECT COUNT(*) AS audit_rows FROM activity_logs WHERE action LIKE 'ARTICLE_COMMENT_%';
```

Nếu không có bảng `article_comments`, bỏ câu query bảng đó. Kiểm tra dữ liệu có khớp backup; không chạy script gộp trên database 21+ bảng chưa được migration sang **core 20 bảng** trước đó.

## SQL kiểm tra sau khi gộp (read-only)

```sql
SELECT TABLE_TYPE, COUNT(*) AS total
FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() GROUP BY TABLE_TYPE;
SELECT ROUTINE_TYPE, COUNT(*) AS total
FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE()
GROUP BY ROUTINE_TYPE;
SELECT COUNT(*) AS obsolete_table_count FROM information_schema.TABLES
WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='article_comments';
SELECT COUNT(*) AS coupon_comments FROM comments WHERE coupon_id IS NOT NULL;
SELECT COUNT(*) AS article_comments FROM comments WHERE article_id IS NOT NULL;
SELECT COUNT(*) AS invalid_target FROM comments WHERE NOT
  ((coupon_id IS NOT NULL AND article_id IS NULL AND request_id IS NULL)
   OR (coupon_id IS NULL AND article_id IS NOT NULL AND request_id IS NOT NULL));
SELECT COUNT(*) AS orphan_articles FROM comments c LEFT JOIN articles a ON a.id=c.article_id
WHERE c.article_id IS NOT NULL AND a.id IS NULL;
```

Kỳ vọng: `BASE TABLE=21`, `VIEW=6`, `PROCEDURE=21`, `obsolete_table_count=0`, `invalid_target=0`, `orphan_articles=0`. Số bình luận coupon phải bằng bản backup; số bình luận bài viết cũng phải bằng trước migration (kể cả `HIDDEN` và `DELETED`). Dữ liệu demo chuẩn có 4 bình luận coupon và 0 bình luận bài viết ban đầu. Nếu DB đã có dữ liệu khác, **không dùng số demo làm tiêu chí**.

## Cột đã rà soát

- Đã loại bỏ khỏi **bảng vật lý riêng** `article_comments`: `id`, `article_id`, `user_id`, `body`, `status`, `request_id`, `created_at`, `updated_at`. Dữ liệu tương ứng được chuyển sang `comments` (không mất chức năng, nhưng ID bài viết được ánh xạ lại).
- `comments` thêm `article_id`, `request_id`; `coupon_id` trở thành NULLable. `id` của coupon comments được giữ nguyên. `status` và `updated_at` chung phục vụ kiểm duyệt/audit.
- **Không xóa thêm cột khác khi chưa chứng minh an toàn**: `campaign_coupons.added_at` được dùng ORDER BY trong `CampaignRepository`; `legacy_history_id` dùng đối chiếu dữ liệu migration; `coupon_events.request_key` chống lặp sự kiện; `code_normalized` có index chống trùng coupon; `external_offers.imported_at`, `reports.resolution_note`, `votes.is_eligible_for_trust` phục vụ nghiệp vụ/audit. Việc một cột ít xuất hiện trong PHP không chứng minh nó vô ích; nhiều cột được dùng trong view/procedure/seed hoặc `SELECT *`.
- Các cột đã loại bỏ **trước đó** vẫn không được tạo lại: `articles.category_id`, `articles.cover_path`, `coupons.currency`, `coupons.image_path`, `coupons.qr_image_path`.

## Test và rủi ro

- `node --test tests/db-migration-contract.test.cjs` và `python tests/db-migration-contract.py`: **chỉ kiểm thử tĩnh**.
- Test integration (`tests/sql-install.py`, `tests/article-comments-http.py` và các bài test PHP) phải chạy trên database thử riêng bằng MariaDB + PHP/mysqli và điều kiện môi trường đã mô tả trong `tests/sql-install.py`. Test cài đặt có thao tác reset database thử, không chạy trên DB thật.
- SQL migration có lệnh `ALTER TABLE`/`DROP TABLE`; DDL tự COMMIT và **không thể rollback toàn bộ bằng một transaction**. Khi lỗi, dừng lại, không tiếp tục triển khai source mới; xác minh backup và trạng thái giữa chừng. Script có thể chạy lại với cùng schema nền, nhưng không thay thế quy trình phục hồi backup.
- Đặt website vào bảo trì khi migration để ngăn thay đổi dữ liệu song song. Kiểm thử UI bình luận coupon, bình luận bài viết, phân trang, chống đăng trùng, kiểm duyệt, xóa của tác giả, audit và các flow coupon/campaign trước khi merge PR.

# Nâng cấp database 23 bảng sang bản hiện tại

Áp dụng cho MariaDB 10.4 trở lên, source từ `9ce148b` cùng bản sửa 09/11/2026.
Không dùng số bảng làm tiêu chí duy nhất: phải kiểm chứng dữ liệu, procedure, view và các luồng ứng dụng.

## Phần thay đổi

- `coupon_status_history` chuyển vào `activity_logs`; giữ ID cũ trong `legacy_history_id`.
- `article_comments` chuyển vào `comments`; giữ ID bình luận coupon và ánh xạ lại ID bình luận bài viết trong audit JSON.
- `sp_submit_coupon` chuyển từ 19 xuống 17 tham số; cài lại bộ 21 procedure/6 view đúng phiên bản.
- Cột cũ `articles.category_id`, `articles.cover_path`, `coupons.image_path`, `coupons.qr_image_path`
  chỉ được bỏ khi trống; `coupons.currency` chỉ được bỏ khi không có giá trị ngoài VND.
  Nếu có dữ liệu khác, migration dừng và giữ nguồn để người quản trị quyết định cách lưu trữ.
- Hai phép đối chiếu nội dung bình luận dùng so sánh byte chính xác; khác hoa/thường, dấu hoặc khoảng trắng đều bị từ chối trước khi xóa bảng nguồn.

## Quy trình trên DB bản sao

1. Backup đầy đủ dữ liệu, routine, view, trigger và event. Dùng `mysqldump --single-transaction --routines --triggers --events` hoặc chức năng Export tương đương.
2. Khôi phục backup vào **DB mới, tên khác**. Xác minh số lượng và nội dung trước/sau khôi phục. Các tên schema được gắn cứng trong view/routine phải trỏ sang bản sao, không trỏ ngược DB gốc.
3. Dừng các luồng ghi. Có thể đặt file `.runtime/maintenance.lock` trong thư mục dự án:
   các entrypoint HTTP trả 503/Retry-After, trong khi PHP CLI vẫn chạy được migration.
   Không dùng cơ chế này thay cho việc dừng worker/cron CLI nếu môi trường có các worker đó.
4. Chọn đúng DB bản sao trong phpMyAdmin, import lần lượt:
   - `database/migrations/20261109_legacy_core_compat.sql`
   - `database/migrations/20261107_unify_comments.sql`
5. **Không import `schema.sql` hoặc `demo.sql` lên DB có dữ liệu.** Không tự xóa hai bảng cũ.
6. Đối chiếu từng bảng còn lại, password hash, ID coupon/bình luận coupon, nội dung bài viết/bình luận,
   lịch sử kiểm duyệt, quyền shop, OAuth và tham chiếu audit. Xác minh **21 bảng, 6 view, 21 procedure**,
   `sp_submit_coupon` 17 tham số và không có bình luận thiếu/đồng thời hai đích.
7. Chuyển `COUPONHUB_DB` trong `.env` sang DB đã kiểm chứng, rồi bỏ file bảo trì. Giữ DB gốc và backup để phục hồi.

## Cấu hình local sau nâng cấp

Lần nâng cấp local này dùng **`dbcouponshubweb_v3`**; `dbcouponshubweb_v2` được giữ nguyên làm bản dự phòng.
`.env` là file riêng, bị gitignore và bị chặn truy cập HTTP. Khi thêm API key, giữ dòng:

```dotenv
COUPONHUB_DB=dbcouponshubweb_v3
```

Không chép đè toàn bộ `.env` bằng `.env.example` rồi vô tình đổi lại DB cũ.
Các khóa OAuth, Resend, Turnstile và ACCESSTRADE được điền theo `docs/PROVIDER_SETUP.md`;
kiểm thử local không chứng minh kết nối provider thật đã hoạt động.

## Kiểm thử lặp lại

Chỉ chạy các lệnh sau khi sở hữu MariaDB test riêng trên `127.0.0.1:13379`.
Chúng có thể reset các database **test** được ghi trong từng file; không dùng server production.

```text
COUPONHUB_RUN_DB_TEST=1 python tests/legacy-core-upgrade.py
COUPONHUB_RUN_DB_TEST=1 python tests/comments-merge-integration.py
COUPONHUB_RUN_DB_TEST=1 python tests/maintenance-http.py
```

Bài test legacy cần git history chứa commit `27fa6a0`. Chạy thêm SQL install, các integration
admin/merchant/user và article HTTP/concurrency. File `.runtime` và backup SQL không được đưa lên GitHub.

## Kết quả local ngày 09/11/2026

- Đã chuyển ứng dụng tại `http://localhost:8000/` sang `dbcouponshubweb_v3`.
  DB `dbcouponshubweb_v2` được giữ nguyên; phép đối chiếu toàn bộ dữ liệu nguồn trước/sau nâng cấp không phát hiện thay đổi.
- DB đang dùng có 21 bảng, 6 view, 21 procedure; `sp_submit_coupon` có 17 tham số.
- Giữ 6 tài khoản và password hash, 40 coupon, 10 chiến dịch, 29 liên kết chiến dịch/coupon,
  8 bài viết, 4 bình luận coupon và 1 bình luận bài viết. Giữ 31 bản ghi lịch sử cũ trong
  `activity_logs`, gồm người thực hiện, trạng thái, thời gian và ghi chú; cập nhật đúng tham chiếu audit bình luận.
- Backup cuối: `dbcouponshubweb_v2-final-before-switch.sql`, nằm ngoài webroot tại
  `C:\Users\ADMIN\.codex\visualizations\2026\10\11\01a12a38-f5f9-7cd2-aa71-1c6f5d2d74d5\db-upgrade-20261109\`.
  SHA-256: `4f304af60706cfba3aa384ee27b5c84aabf94d47085ed77acc336a9aa70b964f`.
- Nghiệm thu migration trên bản sao: dữ liệu giữ nguyên, chạy lại không nhân bản lịch sử/bình luận;
  dữ liệu ở cột cũ chưa trống và bình luận không khớp byte đều khiến migration dừng an toàn.
- Các nhóm integration admin/merchant/user, phase 3/4, thu hồi/external offers, xác thực,
  bình luận HTTP và bình luận đồng thời đều pass. Chuyển hướng đăng nhập cả 6 tài khoản
  pass ở đường dẫn gốc và `/CouponHub_UI`; lần đầu thiếu cờ opt-in HTTP đã được chạy lại đúng cấu hình.
- 137 kiểm thử JavaScript và lint 294 file PHP pass. Cơ chế bảo trì HTTP/CLI đã kiểm thử;
  file bảo trì đã được gỡ sau chuyển DB.
- Trang chủ, coupon, chiến dịch, bài viết và API danh sách bình luận trả 200.
  Cả 8 bài đã xuất bản có liên kết và trang chi tiết trả 200; 39 đường dẫn ảnh/CSS local
  của trang chiến dịch/bài viết trả 200. `.env` và `database/schema.sql` bị chặn với 403.
- Giữ nguyên template/CSS chiến dịch và bài viết. Đợt này không thiết kế lại hai trang.

Bằng chứng máy local nằm trong `.runtime/db-upgrade-20261109/`; kết quả diễn tập,
chuyển DB và kiểm tra HTTP nằm cạnh backup (`rehearsal.json`, `activation.json`, `live-http.json`).
Các kiểm thử provider dùng transport giả lập có kiểm soát, không xác nhận API thật.
OAuth, Resend, Turnstile và ACCESSTRADE vẫn cần điền khóa và nghiệm thu với provider thật.

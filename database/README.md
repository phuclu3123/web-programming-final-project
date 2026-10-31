# Database CouponHub

Hai file SQL có mục đích riêng:

| File | Nội dung | Thứ tự import |
|---|---|---|
| [schema.sql](schema.sql) | Toàn bộ cấu trúc: 21 bảng, index, khóa ngoại, 6 view, 21 procedure | 1 — bắt buộc trên database mới, trống |
| [demo.sql](demo.sql) | Tài khoản thử, shop, coupon, chiến dịch và tương tác mẫu | 2 — tùy chọn, sau schema, khi các bảng còn trống |

`schema.sql` **không chứa dữ liệu demo**. Các INSERT/UPDATE/DELETE trong procedure chỉ chạy khi ứng dụng gọi nghiệp vụ tương ứng. `demo.sql` không thay cấu trúc hay logic backend/API.

## Cài trên máy mới bằng phpMyAdmin

1. Tạo một database **mới, trống**, chọn `utf8mb4_vietnamese_ci`. Tên database tùy máy.
2. Chọn database vừa tạo → **Import → schema.sql → Go**.
3. Muốn có dữ liệu dùng thử ngay: vẫn chọn database đó → **Import → demo.sql → Go**. Chỉ nạp một lần.
4. Cấu hình kết nối bằng biến môi trường `COUPONHUB_HOST`, `COUPONHUB_PORT`, `COUPONHUB_DB`, `COUPONHUB_USER`, `COUPONHUB_PASSWORD`, hoặc `config/local.php` (không commit file này). Mặc định nằm tại `config/database.php`; cấu hình local ưu tiên hơn biến môi trường.
5. Mở web và đăng nhập bằng [tài khoản demo](../docs/DEMO_DATA.md).

SQL không hardcode `USE`, không tạo/xóa database và không tắt khóa ngoại. Tất cả thời gian lưu UTC; ứng dụng hiển thị giờ Việt Nam. Bản cài đã kiểm tra trên **MariaDB 10.4.32 / PHP 8.1 với mysqli (XAMPP)**; chưa xác nhận trên mọi phiên bản MySQL/MariaDB khác.

## Dữ liệu trong demo.sql

- **6 tài khoản**: 1 Admin, 3 Merchant, 2 User; email giả `.test`, mật khẩu bcrypt.
- **12 shop**, 12 membership và yêu cầu shop đã duyệt, 8 danh mục.
- **32 coupon**: 26 đang hoạt động, 1 sắp bắt đầu, 1 hết hạn, 2 chờ duyệt, 1 bị từ chối, 1 tạm tắt. Mã bị từ chối có publication DISABLED theo nghiệp vụ hiện tại.
- **2 chiến dịch**, 10 liên kết coupon; 6 lượt lưu, 6 bình chọn, 4 bình luận, 2 báo cáo.
- **4 cấp độ**, 3 quy tắc điểm, 2 giao dịch điểm cùng lịch sử kiểm duyệt/hoạt động.

Mốc thời gian được tính lại từ lúc import: phần lớn mã có hiệu lực khoảng 90 ngày, hai mã sắp hết hạn còn khoảng 3/5 ngày, mã sắp bắt đầu sau khoảng 7 ngày. Không phải thay ngày thủ công khi gửi file vào dịp khác.

Mọi coupon có `DEMO_`/`[DEMO]`, không dùng thanh toán thật. Bộ SQL được tạo từ fixture trên database thử riêng, **không xuất dữ liệu của database đang dùng**. Không có tài khoản OAuth hay token đặt lại mật khẩu. Bảng `articles` để trống; nội dung hướng dẫn có sẵn của ứng dụng vẫn hoạt động, không cần tạo bài viết giả.

Tên đăng nhập, mật khẩu và phân quyền shop nằm tại [docs/DEMO_DATA.md](../docs/DEMO_DATA.md). Đây là tài khoản thử có mật khẩu công khai; không dùng nguyên bộ demo cho môi trường công khai.

## Database đã có dữ liệu

**Không import lại schema.sql**: đây là bản cài mới, không phải migration. Máy đang chạy `dbcouponshubweb_v2` đã có dữ liệu không nạp lại schema/demo.

Khi cập nhật đợt 3 ngày 31/10/2026, backup rồi import riêng [migrations/20261031_phase3_risk_controls.sql](migrations/20261031_phase3_risk_controls.sql) vào database đang sử dụng. Script cập nhật view hiệu lực và procedure kiểm duyệt để chặn bật lại coupon đã thu hồi; không thay bảng, seed hay sửa bản ghi hiện có. Máy cài mới bằng schema hiện tại đã có thay đổi này. Xem [nghiệm thu đợt 3](../docs/PHASE3_ACCEPTANCE.md).

`demo.sql` kiểm tra cả 21 bảng và từ chối nạp nếu bất kỳ bảng nào đã có dữ liệu. Mọi INSERT nằm trong một transaction; lỗi giữa chừng sẽ rollback toàn bộ dữ liệu demo, không ghi đè tài khoản hay mật khẩu có sẵn. Không nạp thêm bằng CLI sau khi đã import demo.sql.

File demo dùng procedure tạm `couponhub_install_demo`, được xóa sau khi nạp thành công. Nếu công cụ CLI dừng ngay khi báo lỗi, procedure này có thể còn lại; sau khi kiểm tra lỗi có thể dọn bằng `DROP PROCEDURE IF EXISTS couponhub_install_demo;`. Không xóa dữ liệu để cố import lên database đang sử dụng.

Nếu chỉ cài schema để phát triển với dữ liệu riêng, tạo User qua form đăng ký, Admin qua `scripts/create_admin.php`; chủ động cấu hình danh mục, cấp độ và quy tắc điểm trước khi dùng nghiệp vụ tương ứng. Có bảng `oauth_accounts` không đồng nghĩa đã bật đăng nhập OAuth thật.

## Kiểm tra và phát triển

[RULES.md](RULES.md) là quy ước chung. `schema.sql` là nguồn chuẩn duy nhất cho cấu trúc cài mới; `demo.sql` chỉ là bộ dữ liệu thử riêng. Khi đổi schema, cập nhật fixture và bộ demo tương ứng; không thêm nhiều bản dump `_final`, `_fix`, `_v2` song song.

Kiểm tra bàn giao SQL (chỉ database thử `couponhub_merge_test` trên `127.0.0.1:13379`, không đọc cấu hình kết nối của web):

```powershell
$env:COUPONHUB_RUN_DB_TEST='1'
python tests/sql-install.py
```

Lệnh **tạo lại database thử này**; cần chuẩn bị MariaDB riêng trên cổng 13379, Python và PHP/mysqli. Nó kiểm tra đủ object, import tách biệt, đăng nhập/quyền demo, từ chối import lặp và rollback khi gặp lỗi ràng buộc. Có thể truyền `--mysql` và `--php` để đổi đường dẫn executable. Không chạy trên database của thành viên khác.

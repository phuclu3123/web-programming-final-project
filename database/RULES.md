# Quy ước database — dành cho Quang và cả nhóm

Phúc là người tổng hợp dự án. Mọi thay đổi database cần theo một hợp đồng chung để khi ghép nhánh không phát sinh hai bộ bảng, kết nối hoặc Auth khác nhau.

## 1. Thống nhất charset và collation

- File SQL lưu UTF-8. Charset database/kết nối là **`utf8mb4`**.
- Collation văn bản dùng **`utf8mb4_vietnamese_ci`**, thống nhất cho database, bảng, cột, view, tham số và biến chuỗi của procedure.
- Không trộn `utf8mb4_unicode_ci`, `utf8mb4_general_ci` hoặc collation khác vào SQL đang dùng.
- Khi tạo view/procedure, đặt `SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci` trước phần khai báo.
- Tham số và biến chuỗi của procedure phải ghi rõ charset/collation, vì chúng có thể kế thừa mặc định của database, không chỉ `SET NAMES`.

```sql
SET NAMES utf8mb4 COLLATE utf8mb4_vietnamese_ci;

-- Ví dụ khai báo trong procedure:
IN p_account VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci
-- Biến cục bộ:
DECLARE v_role VARCHAR(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_vietnamese_ci;
```

Token/hash, request key và ID provider là **mã kỹ thuật**, dùng `VARBINARY` để khớp chính xác từng byte, kể cả khác hoa/thường. Không đổi những cột này sang so sánh văn bản tiếng Việt.

## 2. Một nguồn SQL chuẩn

- `database/schema.sql` chứa đủ cấu trúc và procedure cho cài mới. Sửa trực tiếp file này khi cập nhật source; không sửa một bản dump khác rồi bỏ quên schema chuẩn.
- Không commit dump phpMyAdmin chứa tài khoản/dữ liệu cá nhân, mật khẩu thật, token hoặc `DEFINER=root@localhost`.
- `database/demo.sql` là bộ dữ liệu thử được yêu cầu riêng: chỉ danh tính giả từ fixture, mật khẩu hash, coupon ghi rõ DEMO. Giữ tách khỏi schema; không xuất database đang dùng để làm demo. Import sau schema vào database chưa có dữ liệu.
- Không hardcode tên database/máy cá nhân bằng `USE ...` trong bản cài. Người import chọn database đích trước.
- Không thêm file theo tên người thực hiện như `quang-*.sql`, nhiều bản `_v2`, `_final`, `_fix` có nội dung chồng nhau.
- Khi cần thay đổi database đã có dữ liệu, gửi script cập nhật có phạm vi rõ ràng cùng thay đổi source. Không dùng schema cài mới để cập nhật tại chỗ.

## 3. Dùng chung cấu trúc bảng

- Bảng chuẩn là `users`, `merchants`, `coupons`, `categories`… Không tạo lại hệ `tbl_*` chạy song song.
- Không đổi tên cột, alias view, tên procedure hoặc thứ tự tham số mà chưa cập nhật repository và các nơi gọi tương ứng.
- Khóa ngoại phải khớp kiểu, độ dài và UNSIGNED; với khóa chuỗi còn phải khớp charset/collation.
- Giữ các ràng buộc UNIQUE của email, username, danh tính OAuth và các cặp quan hệ. Kiểm tra trùng ở ứng dụng không thay được UNIQUE trong DB.
- Giữ index và FK khi chỉnh bảng; không bỏ ràng buộc để làm cho câu lệnh chạy qua.

## 4. Dùng chung kết nối và mô hình MVC

- Toàn dự án dùng **mysqli**, qua `App\Core\Database::connection()`; không thêm PDO hoặc kết nối riêng trong mỗi module.
- SQL/prepared statement/procedure call đặt trong repository. Controller nhận HTTP; service xử lý nghiệp vụ; view chỉ hiển thị.
- Bind dữ liệu bằng prepared statement. Không nối trực tiếp input vào SQL, kể cả tên bộ lọc/sắp xếp chưa được kiểm tra.
- Giữ hợp đồng các interface repository khi sửa implementation.

## 5. Auth và quyền truy cập

- Session/role dùng chung `App\Core\Session` và `App\Core\Auth`; định danh phiên là `user_id`.
- Role/status/owner phải lấy và kiểm tra ở server. Không tin hidden input hoặc role từ JS.
- Quyền quản lý shop dựa vào membership ACTIVE và trạng thái shop; không chỉ kiểm tra `owner_user_id`.
- Mật khẩu lưu bằng `password_hash`, kiểm tra bằng `password_verify`; không lưu plaintext hay đưa dữ liệu tài khoản thật vào SQL nguồn. Tài khoản giả trong demo.sql dùng hash; thông tin đăng nhập thử công khai nằm trong docs/DEMO_DATA.md.
- Provider hiện tại là **Google và GitHub**. Không tự đổi sang Facebook hoặc thêm một luồng Auth độc lập.

## 6. Thời gian và cấu hình máy

- Dữ liệu thời gian trong DB dùng **UTC**. Chuyển sang/nhận từ giờ Việt Nam ở phần hiển thị/xử lý input phù hợp.
- Thông tin kết nối qua `config/database.php`, biến môi trường hoặc `config/local.php` được ignore. Không commit mật khẩu hoặc secret OAuth.
- Mỗi máy có thể đặt tên database khác nhau; code nghiệp vụ không phụ thuộc tên `dbcouponshubweb_v2`.

## 7. Trước khi bàn giao để tổng hợp

1. Import `schema.sql` vào database thử **mới, trống** để xác nhận source tự cài được.
   Nếu gửi dữ liệu mẫu, import tiếp `demo.sql`, xác nhận đăng nhập/quyền shop, ngày hiệu lực và kiểm tra import lặp không đổi dữ liệu. Bộ kiểm tra có sẵn: `tests/sql-install.py`.
2. Thử các truy vấn/routine bị ảnh hưởng bằng mysqli; chạy bộ kiểm tra liên quan, gồm dữ liệu sai và sai quyền khi có thay đổi nghiệp vụ.
3. Kiểm tra alias/cột trả về khớp repository; giữ các tập kết quả của routine trang chủ/shop/Admin.
4. Ghi rõ bảng/routine thay đổi, ảnh hưởng dữ liệu và script cập nhật nếu cần. Không tự chạy DDL hàng loạt lên database của thành viên khác.

`unicode_ci` không phải một cấu hình sai. Quy ước chọn `vietnamese_ci` là quyết định thống nhất của dự án; khi review nên xác định chính xác chỗ trộn cấu hình. Lỗi InnoDB `#1932` cần xem trạng thái engine/log, không mặc định quy cho collation hoặc cho người viết SQL.

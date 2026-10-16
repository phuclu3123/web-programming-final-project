# Database CouponHub

Thư mục này chỉ giữ ba file:

```text
database/
├── schema.sql    Bản cài đặt đầy đủ: bảng, index, khóa ngoại, view và procedure
├── README.md     Hướng dẫn sử dụng
└── RULES.md      Quy ước chung cho Quang và cả nhóm
```

## Database đang chạy

Máy hiện tại dùng **`dbcouponshubweb_v2`**, cấu hình tại `config/database.php`. Database này đã cài đủ **21 bảng, 6 view, 21 procedure** với charset `utf8mb4`, collation `utf8mb4_vietnamese_ci`.

**Không cần import lại.** Database cũ `dbcouponshubweb` đã được xóa theo yêu cầu; ứng dụng dùng `_v2`, đã nạp 6 tài khoản, 12 shop và 32 coupon thử nghiệm theo luồng nghiệp vụ hiện có. Xem [tài khoản, mật khẩu và bộ dữ liệu mẫu](../docs/DEMO_DATA.md).

## Cài trên máy mới

1. Trong phpMyAdmin, tạo một database **mới, trống**, chọn `utf8mb4_vietnamese_ci`.
2. Chọn database vừa tạo → **Import → chọn `schema.sql` → Go**.
3. Cấu hình `COUPONHUB_DB` hoặc `config/local.php` để ứng dụng trỏ tới database đó. Cấu hình mặc định của dự án nằm ở `config/database.php`.
4. Mở trang web và thử đăng ký, đăng nhập.

Chỉ import **một file `schema.sql`**, không còn thứ tự import nhiều file. Bản cài đặt không có `DROP TABLE`, không xóa database cũ và không kèm tài khoản/coupon mẫu. Không import lên database đã có bảng: đây là bản cài mới, không phải script cập nhật dữ liệu.

`schema.sql` chứa các phần có chú thích riêng: cấu trúc bảng/index/FK, view, nghiệp vụ coupon, đăng ký, đăng nhập, guard, dữ liệu trang chủ/shop/Admin và thao tác từ form. Các lệnh INSERT/UPDATE/DELETE bên trong procedure chỉ chạy khi ứng dụng gọi procedure tương ứng, không tự tạo dữ liệu khi import.

## Dữ liệu khởi tạo

- Tài khoản thường có thể tạo qua form đăng ký.
- Tài khoản Admin có thể tạo bằng công cụ CLI `scripts/create_admin.php` với thông tin bạn chọn.
- Danh mục, cấp độ, quy tắc điểm, shop và coupon cần dữ liệu cấu hình/thử nghiệm phù hợp. Chúng không được seed vào bản cài này.
- Muốn nạp bộ thử nghiệm trên máy mới sau khi import schema, dùng `scripts/seed_demo.php --confirm-demo` theo [DEMO_DATA.md](../docs/DEMO_DATA.md). Bộ này tách khỏi schema cài mới; máy hiện tại đã nạp xong, không cần chạy lại.
- `oauth_accounts` chuẩn bị cho Google/GitHub; luồng OAuth thật vẫn cần phần đổi token/xác minh danh tính. Có bảng không đồng nghĩa đã bật đăng nhập OAuth.

## Khi phát triển tiếp

`schema.sql` là **nguồn chuẩn duy nhất cho cài đặt mới**. Đọc [RULES.md](RULES.md) trước khi thay schema/routine. Khi cần nâng cấp database có dữ liệu, chuẩn bị script cập nhật riêng cho thay đổi cụ thể; không chạy lại bản cài mới hoặc tạo thêm nhiều bản dump song song.

Các thư mục `routines/`, `migrations/`, `quang-reference/`, bản dump cũ, manifest và công cụ sinh hotfix đã bỏ khỏi repository. Bản sao trước khi dọn nằm trong thư mục `.integration/` của workspace, ngoài repository, để khôi phục khi cần.

## Hai lỗi đã gặp

- **Illegal mix of collations:** biểu thức/column/parameter dùng các collation khác nhau. Quy ước hiện tại thống nhất `utf8mb4_vietnamese_ci` trong kết nối và SQL.
- **InnoDB #1932:** MySQL không mở được bảng trong engine. Đây là vấn đề khác collation; không sửa bằng việc import lại hoặc CONVERT bảng hàng loạt. Database cũ đã gặp lỗi này; ứng dụng hiện dùng database mới.

`utf8mb4_unicode_ci` là một collation hợp lệ, hỗ trợ văn bản Unicode. Vấn đề trong lần tích hợp là **không thống nhất cấu hình**, không phải bản thân collation đó sai hoặc đủ cơ sở kết luận lỗi do một thành viên.

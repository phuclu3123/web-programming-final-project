# CouponHub

Kiến trúc hiện tại và quy ước phát triển: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md). Dự án dùng MVC, repository/interface và **mysqli**, một Router và một abstract Controller chung.

Cấu hình đăng nhập email/Google/GitHub và OTP qua Resend: [docs/AUTH_SETUP.md](docs/AUTH_SETUP.md). PHP tự nạp `.env` tại project root; dùng `.env.example` làm mẫu. Không cần import lại schema cho phần xác thực này.

Rà soát giai đoạn 2 của Phúc: [kết quả kiểm thử, lỗi đã sửa và phần còn chờ nghiệm thu thật](docs/PHUC_PHASE2_REVIEW.md).

## Bản tích hợp Phúc + Quang

Đã tích hợp thủ công `origin/Quang` tại `cc8a2d8`, giữ UI/Auth hiện tại và dùng **mysqli** cho toàn dự án. MVC, abstract controller, repository/interface và session được dùng chung.

- [Báo cáo đối chiếu, kiến trúc, phần đã ghép và kiểm thử](docs/INTEGRATION_QUANG.md).
- [Cài database và dữ liệu thử](database/README.md): import `database/schema.sql` vào database mới, trống; tùy chọn import tiếp `database/demo.sql` để có tài khoản, shop và coupon demo.
- [Quy ước database dành cho Quang và cả nhóm](database/RULES.md).
- [Phần tích hợp chọn lọc nhánh Trúc và các kiểm tra](docs/INTEGRATION_TRUC.md).
- [Hướng dẫn Auth trước khi tích hợp](docs/AUTH_PHASE1.md): đọc cùng báo cáo mới vì vị trí endpoint và schema đã được thống nhất lại.

Code không tự chạy migration hoặc thay đổi cấu trúc database. Google/GitHub và OTP Resend đã có luồng backend; cần cấu hình riêng và thử tài khoản nhà cung cấp thật theo [AUTH_SETUP.md](docs/AUTH_SETUP.md). Các báo cáo tích hợp cũ bên trên mô tả lịch sử trước khi hoàn thiện xác thực.

Đồ án Web Programming viết bằng PHP OOP theo mô hình MVC.

## Chạy dự án

1. Chuẩn bị database theo `database/README.md`.
2. Kiểm tra cấu hình duy nhất tại `config/database.php` (env hoặc local.php).
3. Trong thư mục project, chạy `php -S localhost:8000 router.php` rồi mở `http://localhost:8000/`.
4. Với Apache/XAMPP, dùng thư mục project làm gốc ứng dụng, bật `.htaccess`/rewrite; đặt `COUPONHUB_APP_URL` khớp URL. `public/` chỉ chứa asset của module mới.

Các trang chính:

- `index.php`: danh sách coupon công khai.
- `shop.php`: khu vực merchant.
- `admin.php`: khu vực quản trị.
- `/coupons`, `/saved-coupons`, `/dashboard`: Coupon cộng đồng và tài khoản.
- `/merchant`, `/admin`: các module chức năng tích hợp từ Quang.

`index.php` là front controller, `config/routes.php` khai báo route. Các URL PHP cũ được giữ làm wrapper để không hỏng liên kết/JS hiện tại. Bảng chuẩn là `users`, `merchants`, `coupons`… theo dump Quang; routine `ch_*` và `sp_*` cùng đọc/ghi các bảng này qua mysqli.

## Thành viên

- Từ Trương Thanh Trúc — nhánh `Truc`
- Lữ Võ Hoàng Phúc — nhánh `Phuc`
- Trương Ứng Quang — nhánh `Quang`

## Tài khoản thử nghiệm local

Database đang dùng: `dbcouponshubweb_v2`. Đã có 6 tài khoản User/Merchant/Admin và 32 coupon của 12 nền tảng. Xem [tài khoản, mật khẩu và dữ liệu mẫu](docs/DEMO_DATA.md). Các mã `DEMO_` chỉ phục vụ chạy thử trong CouponHub.
# Thiết kế giao diện và ảnh thật

Hướng nhận diện, nguồn ảnh, dải thương hiệu chuyển động và phần khám phá danh mục được ghi tại [docs/EDITORIAL_DESIGN.md](docs/EDITORIAL_DESIGN.md).

Đợt hoàn thiện toàn bộ UI ngày 17/10/2026: [phạm vi, nghiệm thu và giới hạn kiểm tra](docs/UI_RELEASE_REVIEW.md). Chạy `python scripts/check_ui_release.py` khi PHP/MySQL và bản preview local đang hoạt động; kiểm tra chỉ đọc dữ liệu.

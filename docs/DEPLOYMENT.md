# Clone và triển khai CouponHub

## 1. Chốt cùng một bản

Source, snapshot SQL và bộ tệp upload phải tương ứng cùng đợt bàn giao. Ghi mã commit source và SHA-256 SQL để đối chiếu. Clone trước khi các thay đổi local được push sẽ chưa có bản mới.

Không coi gói `CouponHub_Quang_Deploy_20261109` cũ là snapshot mới nhất: nó được xuất khi `external_offers=0`, trước lần lưu 50 mục ACCESSTRADE. Xuất và kiểm chứng snapshot mới trước khi bàn giao. Hướng dẫn trong file này không tự xuất/import database.

## 2. Dependency và asset

Runtime dùng PHP 8.3 để khớp CI, cùng `mysqli`/mysqlnd, `mbstring`, `curl`, `openssl`, session và kết nối HTTPS có kiểm tra chứng chỉ.

Không có `package.json` hoặc `composer.json`; không cần `npm install`/`composer install`. Font Awesome và Swagger được Git theo dõi trong `assets/vendor/`. Bootstrap tải từ CDN. Không cần Node/Python trên hosting để phục vụ ứng dụng.

Nếu mất icon: kiểm tra HTTP status của `assets/vendor/fontawesome/css/all.min.css`, `webfonts/fa-solid-900.woff2` và `webfonts/fa-brands-400.woff2`. Nếu mất ảnh: kiểm tra tên file, chữ hoa/thường, URL gốc và các thư mục `assets/images/`, `public/images/`. Tệp trong `public/uploads/` không được Git theo dõi, phải bàn giao riêng nếu có.

## 3. Database

Ứng dụng dùng MySQL/MariaDB, không phải Microsoft SQL Server. phpMyAdmin là công cụ import/quản trị.

- Backup DB đích trước khi thay đổi. Nếu có dữ liệu riêng, hợp nhất có kiểm soát trước khi chuyển ứng dụng.
- Bản đầy đủ của Phúc: import vào database mới, trống; không chạy tiếp `schema.sql`, `demo.sql` hay seed.
- Database đang hoạt động: chọn migration theo [database/README.md](../database/README.md), không chạy mọi file theo ngày.
- Tài khoản import cần tạo bảng/view/routine; runtime cần quyền đọc/ghi và thực thi procedure. Không bỏ qua lỗi quyền hoặc `DEFINER`.
- Đối chiếu 21 bảng, 6 view, 21 procedure và 17 tham số của `sp_submit_coupon`; kiểm tra số lượng nội dung theo manifest snapshot mới.
- Kiểm tra đủ bài viết, chiến dịch, bình luận, membership và hàng chờ nguồn ngoài, không chỉ đếm bảng.
- Đổi mật khẩu/khóa các tài khoản demo có quyền cao trước khi mở website công khai. SQL và các liên kết OAuth là dữ liệu riêng, không đưa vào Git hay webroot.

## 4. Vị trí ứng dụng trên hosting

Gốc ứng dụng là thư mục chứa `index.php` của repo. Với domain đặt tại `public_html`, upload nội dung gốc ứng dụng vào đó và giữ `.htaccess`. `public/` hiện chỉ chứa asset và wrapper chuyển về gốc, không phải document root độc lập.

Áp dụng rewrite trên Apache/LiteSpeed; không đổi URL vào thẳng `app/views/`. Nginx cần quy tắc bảo vệ/rewrite tương đương và phải được cấu hình, kiểm tra riêng.

Gói website không gồm `.git`, `.runtime`, `.vscode`, SQL/backup dữ liệu thật, ZIP tải về, tài liệu báo cáo hoặc source sơ đồ. Repo không còn thư mục test và script Python/Shell/PowerShell; chúng ở bộ DevTools riêng. `scripts/` hiện chỉ giữ PHP CLI để quản trị/kiểm tra cấu hình; có thể giữ khi triển khai để dùng terminal, nhưng phải chặn truy cập web trực tiếp bằng `.htaccess`. `docs/` là tài liệu phát triển và đặc tả, không cần mở công khai qua webroot.

## 5. Env và provider

Tạo `.env` riêng trên hosting từ mẫu, điền khóa được gửi riêng. Không chép đè `.env` đang hoạt động khi cập nhật source.

```dotenv
COUPONHUB_HOST=HOST_DATABASE
COUPONHUB_PORT=3306
COUPONHUB_DB=TEN_DB_HOSTING_CAP
COUPONHUB_USER=USER_DB_HOSTING_CAP
COUPONHUB_PASSWORD=MAT_KHAU_RIENG
COUPONHUB_APP_URL=https://blueviolet-mole-383647.hostingersite.com
COUPONHUB_DEBUG=0
COUPONHUB_TURNSTILE_MODE=required
TURNSTILE_HOSTNAMES=blueviolet-mole-383647.hostingersite.com
```

Điền thêm Google/GitHub/Facebook, Resend, Turnstile, ACCESSTRADE và khóa OTP theo `.env.example`. Khóa OTP phải ngẫu nhiên, tối thiểu 32 ký tự và ổn định. Không dùng `||` nối hai URL trong env.

Biến môi trường tiến trình có ưu tiên hơn `.env`, kể cả giá trị rỗng. `config/local.php` nếu tồn tại có thể ghi đè DB; không mang file cấu hình máy cá nhân lên hosting.

OAuth callback đăng ký với provider phải khớp chính xác domain và đường dẫn:

```text
https://blueviolet-mole-383647.hostingersite.com/api/oauth/google-callback.php
https://blueviolet-mole-383647.hostingersite.com/api/oauth/github-callback.php
https://blueviolet-mole-383647.hostingersite.com/api/oauth/facebook-callback.php
```

Google One Tap cần origin HTTPS ở Google Cloud; hiển thị còn phụ thuộc lựa chọn cookie và trình duyệt. Facebook Development vẫn giới hạn tài khoản được phép thử, không coi là đăng nhập công khai đã nghiệm thu. Giữ Graph version đang được app Meta hỗ trợ.

Resend chỉ dùng quên mật khẩu; domain gửi đã xác minh có thể khác domain website. Turnstile phải cho phép hostname trên cả Cloudflare và env. ACCESSTRADE có lỗi quyền/nguồn gián đoạn cần xử lý riêng; không xuất bản dữ liệu giả khi API lỗi.

## 6. Quyền ghi và nghiệm thu

Session và rate-limit dùng đường dẫn riêng, có quyền ghi, ưu tiên ngoài `public_html`. Không chép đường dẫn `C:\...` từ máy Windows và không mở quyền 777 để bỏ qua lỗi. Các tệp upload phải có quyền phù hợp nếu dùng chức năng upload.

Nếu có terminal, chạy:

```bash
php scripts/check_auth_config.php --db --facebook
php scripts/check_integrations.php --database
```

Đây chỉ là kiểm tra cấu hình/schema, không gửi mail và không xác minh khóa provider thật.

Trước khi công khai:

1. GET trang chủ, bài viết, chiến dịch và asset đều đúng; route lạ trả 404.
2. `.env`, `config/database.php`, SQL, `.git` và `.runtime` trả 403/404; không có nội dung riêng trong body.
3. Kiểm tra User/Merchant/Admin đúng quyền; merchant không đọc/sửa shop khác.
4. Bình luận/lưu mã tồn tại sau tải lại; gửi lặp không tạo dữ liệu trùng.
5. Duyệt/thu hồi coupon và chiến dịch phản ánh đúng ngoài trang công khai.
6. OAuth callback, email quên mật khẩu, Turnstile chạy trên HTTPS thật; cookie phiên có Secure/HttpOnly/SameSite phù hợp.
7. ACCESSTRADE chỉ ghi hàng chờ; kiểm tra nguồn trước khi chuyển duyệt.

Chỉ chạy test ghi dữ liệu trên DB riêng. Không chạy toàn bộ collection Postman trên DB production. Giữ bản cũ đến khi nghiệm thu; nếu DB mới đã có thao tác ghi, rollback phải bảo toàn phần dữ liệu phát sinh.

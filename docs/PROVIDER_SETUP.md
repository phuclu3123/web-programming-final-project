# Cấu hình API bên ngoài

Code dùng PHP/mysqli, session cookie, HTML/CSS/JS và Bootstrap hiện có. Không thêm JWT hay lưu access token của nhà cung cấp trong trình duyệt. Chưa có khóa thật nên kiểm thử transport giả lập và local không thay thế nghiệm thu với nhà cung cấp.

## Chạy local an toàn trước khi điền khóa

Chạy tại thư mục `CouponHub_UI`:

```powershell
.\scripts\serve-local.ps1 -Port 8080
```

Mở `http://localhost:8080/CouponHub_UI/`. Script dùng `router.php` để chặn `.env`, SQL, source, session và tài liệu nội bộ; giữ nguyên server đang chiếm cổng khác. Dừng bằng Ctrl+C. Nếu đã có server 8080, chọn cổng khác; script in URL tương ứng.

**Preview không có router không phù hợp để điền secret**: kiểm tra hiện tại cho thấy server VSCode cổng 8000 trả `.env.example` và `database/schema.sql` trực tiếp. Dừng preview đó trước khi tạo `.env` chứa khóa thật. Apache phải bật rewrite và áp dụng `.htaccess` của dự án. Khi triển khai HTTPS, kiểm tra trực tiếp `.env`/`database/schema.sql` trả 403/404 và cookie có Secure.

Copy `.env.example` thành `.env`, giữ file này ngoài Git. `COUPONHUB_APP_URL` phải khớp URL thực; script local đặt URL theo cổng đang chạy và ưu tiên hơn `.env`. Không trộn `localhost` với `127.0.0.1`. Sau đổi biến môi trường của tiến trình, khởi động lại server; file `.env` được nạp mỗi request nếu chưa có biến tiến trình ghi đè.

## Database đang có dữ liệu

Sao lưu ra thư mục riêng **ngoài webroot**. Chỉ import `database/migrations/20261105_external_offers.sql` trong phpMyAdmin vào đúng database hiện tại. Migration chỉ tạo bảng hàng chờ `external_offers`, có thể chạy lại. Không import lại `schema.sql` hay `demo.sql` lên database đã dùng. Bản cài mới dùng schema đã có 21 bảng, 6 view và 21 procedure; demo vẫn là file riêng.

## ACCESSTRADE Việt Nam

```dotenv
ACCESSTRADE_ACCESS_KEY=your_publisher_access_key
```

Lấy **Publisher access key có quyền API coupon/offer**. Đây không phải secret OAuth, advertiser API hay API key dịch vụ khác. Code gửi `Authorization: Token ...` tới endpoint HTTPS cố định của Việt Nam. Hợp đồng tham khảo từ tài liệu chính thức: [Authentication](https://developers.accesstrade.vn/api-accesstrade-tai-lieu-tich-hop/authentication), [Offers](https://developers.accesstrade.vn/api-accesstrade-tai-lieu-tich-hop/get-promotional-information), [Coupon/merchant/category](https://developers.accesstrade.vn/api-publisher-vietnamese/lay-thong-tin-vouchers-coupons-deals/tim-kiem-danh-sach-thong-tin-khuyen-mai).

Admin → **Nguồn coupon** → lấy trang nguồn → xem nguồn → chọn cửa hàng/danh mục đã duyệt → xác nhận điều kiện còn thiếu → chuyển kiểm duyệt → duyệt tại **Kho coupon**. Mỗi lần lấy tối đa 50 offer; một offer có thể chứa nhiều mã. Số lượng/nhãn hàng thực tế tùy quyền tài khoản và dữ liệu nhà cung cấp; không hứa có đủ mọi thương hiệu đang minh họa trên UI.

- Đồng bộ chỉ cập nhật hàng chờ, không tự công khai hay sửa coupon đang có.
- Coupon nhập là `COMMUNITY/PENDING`, có ghi nguồn ACCESSTRADE. Không mạo nhận là `OFFICIAL` do shop tự đăng.
- Số tiền/đơn tối thiểu/trần giảm/ngày không rõ để trống. Không đoán điều kiện từ đoạn quảng cáo hay quy đổi coin thành tiền. Ngày chỉ có YYYY-MM-DD được hiểu theo giờ Việt Nam, ngày kết thúc đến 23:59:59; admin cần đối chiếu nguồn.
- Mã đã nhập/bỏ qua không được tự mở lại. Nhập trùng mã cùng shop, cùng khoảng hiệu lực bị chặn cả khi coupon cũ đã thu hồi. Thao tác nhập và đánh dấu đã nhập nằm trong một transaction.
- Bản review giữ fingerprint của nguồn. Nếu nguồn đổi trong lúc form đang mở, nhập bị chặn 409 để đối chiếu lại; không âm thầm nhận phiên bản mới chưa xem.
- Khi payload nguồn đổi sau lần nhập, hàng chờ báo **Nguồn đã thay đổi**. Admin đối chiếu và sửa/thu hồi chủ động. Một mục biến mất khỏi một trang kết quả **không đủ căn cứ tự thu hồi**: nguồn đang lọc trạng thái và phân trang. Chưa có cron/webhook tự thu hồi theo ACCESSTRADE; coupon vẫn tự hết hạn theo ngày đã lưu.
- Dữ liệu demo vẫn có nhãn minh họa. Không đổi chúng thành coupon thật bằng cách bỏ chữ DEMO. Coupon thật phải qua hàng chờ nguồn và kiểm duyệt.

CLI mặc định chỉ đọc, không ghi DB:

```powershell
php scripts/sync-accesstrade.php --resource=offers --page=1
php scripts/sync-accesstrade.php --resource=coupons --merchant=shopee --page=1
php scripts/sync-accesstrade.php --resource=merchants
php scripts/sync-accesstrade.php --resource=categories
php scripts/sync-accesstrade.php --apply --expected-database=dbcouponshubweb_v2 --actor=YOUR_ADMIN_ID --page=1
```

`--apply` cũng chỉ ghi hàng chờ. ID actor phải là admin đang hoạt động. Nếu API trả 401/403/429 hoặc cấu trúc khác tài liệu, dừng nhập, kiểm tra quyền tài khoản và mẫu response đã loại bỏ dữ liệu nhạy cảm. Không chuyển sang scrape hay tự tạo coupon khi nguồn lỗi.

## Cloudflare Turnstile

Tạo widget trong Cloudflare, khai báo đúng hostname của local/production, sau đó:

```dotenv
COUPONHUB_TURNSTILE_MODE=required
TURNSTILE_SITE_KEY=public_site_key
TURNSTILE_SECRET_KEY=server_secret_key
TURNSTILE_HOSTNAMES=localhost
```

Hostname không gồm protocol/cổng/path. Production chỉ khai báo hostname thực được cho phép. `auto` mặc định chưa có cả hai khóa thì giữ form local hoạt động; điền một khóa sẽ bật kiểm tra và từ chối nếu thiếu cấu hình. `off` dành cho phát triển local, không phải cách sửa lỗi production.

Bảo vệ đăng nhập, đăng ký (kể cả alias `/api/*.php`), yêu cầu OTP quên mật khẩu, gửi coupon cộng đồng và xin quyền shop. CSRF, kiểm tra quyền, giới hạn gửi vẫn chạy độc lập. OAuth dùng state/PKCE riêng; không biến CAPTCHA thành điều kiện nhận diện tài khoản. Server gọi Siteverify và kiểm tra `success`, `hostname`, `action`. Token dùng một lần, hết hạn sau 300 giây theo [Cloudflare Siteverify](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/). Form AJAX reset đúng widget sau khi gửi; tab auth ẩn chỉ render khi mở; lỗi mạng giữ nội dung và thông báo tại form.

## OAuth và Resend

Điền các biến đã có trong `.env.example`, xem thêm [AUTH_SETUP.md](AUTH_SETUP.md). Với URL local ở trên, callback đăng ký trên từng nhà cung cấp phải là:

| Nhà cung cấp | Callback |
|---|---|
| GitHub | `http://localhost:8080/CouponHub_UI/api/oauth/github-callback.php` |
| Google | `http://localhost:8080/CouponHub_UI/api/oauth/google-callback.php` |
| Facebook | `http://localhost:8080/CouponHub_UI/api/oauth/facebook-callback.php` |

Đổi toàn bộ sang domain HTTPS khi triển khai; tuân theo quy định callback/tester của dashboard tương ứng. Google cần Web OAuth client. Facebook cần Graph version được ứng dụng Meta hỗ trợ; không tự chọn một version cũ từ ví dụ. Facebook hiện yêu cầu liên kết từ **Tài khoản** sau khi đăng nhập local lần đầu; không tự ghép email chưa xác minh. GitHub/Google kiểm tra danh tính và email đã xác minh; khóa tài khoản/auth_version vẫn áp dụng sau OAuth.

Resend cần `COUPONHUB_MAIL_DRIVER=resend`, `RESEND_API_KEY`, `RESEND_FROM_EMAIL` thuộc domain đã được xác minh trong tài khoản Resend; xem [Send email](https://resend.com/docs/api-reference/emails/send-email). Đặt `COUPONHUB_OTP_SECRET` ngẫu nhiên tối thiểu 32 ký tự và giữ ổn định:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
php scripts/check_integrations.php --database
php scripts/check_provider_transport.php --network
```

Lệnh check_provider_transport chỉ GET endpoint công khai Google/GitHub để kiểm tra HTTPS/TLS, không dùng secret. Lệnh check_integrations chỉ in READY/MISSING, không in secret và không xác nhận khóa hợp lệ với provider. Nếu Windows thiếu CA bundle, cấu hình `curl.cainfo`/`openssl.cafile` trong php.ini theo đường dẫn CA tin cậy; giữ nguyên TLS verification.

## Swagger và Postman

Admin → **API & nghiệm thu** (`/admin/api-docs`) có Swagger UI local, không gửi schema sang validator bên ngoài. `/admin/api-spec` trả 119 operation lấy từ router, có token CSRF của phiên admin hiện tại. Tài liệu tĩnh trong `docs/api` không chứa cookie/token thật. Nhiều endpoint PHP là form trả HTML/302/303; không coi redirect là bằng chứng nghiệp vụ thành công, cần kiểm tra trạng thái dữ liệu.

```powershell
python scripts/build_api_assets.py
python scripts/build_postman.py
$env:COUPONHUB_RUN_DB_TEST='1'
python tests/run-integrations.py --browser
```

Runner yêu cầu MariaDB **riêng** `127.0.0.1:13379/couponhub_merge_test`, sẽ tạo lại fixture tại đó, từ chối `config/local.php`. Không chạy bằng DB thật. Bộ Postman dùng `--no-report-events`, không upload collection/kết quả lên tài khoản Postman. Cấu hình và tài khoản trong collection là demo local. Swagger dùng phiên hiện tại và có thể ghi dữ liệu thật khi bấm Execute; kịch bản thu hồi phải chạy trên database kiểm thử.

## Nghiệm thu sau khi có khóa thật

1. ACCESSTRADE: lấy được 1 trang thực; kiểm tra shop, điều kiện, ngày, link; nhập một mã vào hàng chờ, duyệt rồi thu hồi; đồng bộ lại không khôi phục mã đã thu hồi. Lưu kết quả đã che mã/secret nếu cần chia sẻ.
2. Turnstile: token hợp lệ thành công; thiếu/hết hạn/dùng lại/sai hostname hoặc action bị từ chối; thử lại form không mất nội dung. Kiểm tra cả popup login và trang con.
3. OAuth: callback thành công, hủy giữa chừng, callback dùng lại/sai state; liên kết nhầm tài khoản bị chặn; khóa user rồi thử lại; logout không còn truy cập trang riêng.
4. Resend: mailbox thực nhận OTP; OTP đúng chỉ dùng một lần, OTP sai/hết hạn không đổi mật khẩu; đổi mật khẩu xong phiên cũ bị vô hiệu; xác nhận delivery tại dashboard/email, không chỉ dựa HTTP 200.
5. Host HTTPS thật: private files không truy cập được, cookie Secure/HttpOnly/SameSite, callback đúng domain, email domain và API quyền thật. Đây là phần chưa thể xác nhận chỉ bằng API key trống và test local.

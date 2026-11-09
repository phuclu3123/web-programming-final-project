# Cấu hình API bên ngoài

Code dùng PHP/mysqli, session cookie, HTML/CSS/JS và Bootstrap hiện có. Không thêm JWT hay lưu access token của nhà cung cấp trong trình duyệt. Chưa có khóa thật nên kiểm thử transport giả lập và local không thay thế nghiệm thu với nhà cung cấp.

## Chạy local an toàn trước khi điền khóa

Chạy tại thư mục `CouponHub_UI`:

```powershell
.\scripts\serve-local.ps1 -Port 8080
```

Mở `http://localhost:8080/CouponHub_UI/`. Script dùng `router.php` để chặn `.env`, SQL, source, session và tài liệu nội bộ; giữ nguyên server đang chiếm cổng khác. Dừng bằng Ctrl+C. Nếu đã có server 8080, chọn cổng khác; script in URL tương ứng.

**Preview không có router không phù hợp để điền secret**: dùng `router.php` hoặc cấu hình web server chặn file riêng. Local ngày 09/11/2026 tại `http://localhost:8000/` đang dùng router; `.env` và `database/schema.sql` đã trả 403. Không suy ra mọi tiến trình trên cổng 8000 đều an toàn: kiểm tra lại khi thay server. Apache phải bật rewrite và áp dụng `.htaccess` của dự án. Khi triển khai HTTPS, kiểm tra trực tiếp `.env`/`database/schema.sql` trả 403/404 và cookie có Secure.

Nếu chưa có `.env`, copy `.env.example` rồi cấu hình; giữ file này ngoài Git. Local sau nâng cấp đã có `.env` với `COUPONHUB_DB=dbcouponshubweb_v3`: thêm khóa vào file này, không chép đè rồi đổi về DB cũ. `COUPONHUB_APP_URL` phải khớp URL thực; script local đặt URL theo cổng đang chạy và ưu tiên hơn `.env`. Không trộn `localhost` với `127.0.0.1`. Sau đổi biến môi trường của tiến trình, khởi động lại server; file `.env` được nạp mỗi request nếu chưa có biến tiến trình ghi đè.

## Database đang có dữ liệu

Sao lưu ra thư mục riêng **ngoài webroot**. Với bản cũ 23 bảng, làm theo [quy trình nâng cấp đầy đủ](DB_UPGRADE_20261109.md); chỉ chạy migration external offers sẽ không đủ tương thích code hiện tại. Với bản 22 bảng đã gộp lịch sử, xem [migration 21 bảng](DB_MIGRATION_21_TABLES.md). Không import lại `schema.sql` hay `demo.sql` lên database đã dùng. Bản cài mới dùng schema đã có 21 bảng, 6 view và 21 procedure; demo vẫn là file riêng.

## ACCESSTRADE Việt Nam

```dotenv
ACCESSTRADE_ACCESS_KEY=your_publisher_access_key
```

Lấy **Publisher access key có quyền API coupon/offer**. Đây không phải secret OAuth, advertiser API hay API key dịch vụ khác. Code gửi `Authorization: Token ...` tới endpoint HTTPS cố định của Việt Nam. Ưu tiên đúng nhánh tài liệu mà dashboard Publisher hiện tại liên kết: [API Publisher tiếng Việt](https://developers.accesstrade.vn/api-publisher-vietnamese), [Authentication](https://developers.accesstrade.vn/api-publisher-vietnamese/authentication), [Coupon/merchant/category](https://developers.accesstrade.vn/api-publisher-vietnamese/lay-thong-tin-vouchers-coupons-deals/tim-kiem-danh-sach-thong-tin-khuyen-mai).

Nhánh tiếng Anh trước đây được dẫn trong tài liệu này dùng cùng header xác thực và cùng URL coupon. URL trang tài liệu không phải API base URL. Trang tiếng Việt [khuyến mại đang hoạt động](https://developers.accesstrade.vn/api-publisher-vietnamese/lay-thong-tin-cac-khuyen-mai-dang-hoat-dong) có lưu ý chuyển sang phiên bản mới; đích hướng dẫn mới là công cụ nhúng dùng pubID, không phải một JSON feed được xác nhận thay thế trực tiếp. `resource=offers` hiện vẫn là `/v1/offers_informations`; `resource=coupons` là `/v1/offers_informations/coupon`. Chưa tự thay nguồn JSON bằng script nhúng hoặc coi pubID là API access key. Xem [đối chiếu tài liệu và kết quả thực tế](ACCESSTRADE_DOCUMENT_AUDIT_20261109.md).

Không coi HTTP 200 đơn lẻ là thành công: API campaign mới `/v1/cashback/campaigns` từng trả HTTP 200 nhưng nội dung báo `code=PX00401`, `message=Invalid token`. Trong đợt kiểm tra 09/11, API offers có lần đọc/chuẩn hóa được 50 mục nhưng các lần sau lại 403; người dùng xác nhận không đổi key trong lúc kiểm tra. Chưa kết luận nguyên nhân phía nhà cung cấp; chưa nghiệm thu nguồn ổn định hoặc hướng dẫn tạo lại key liên tục.

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
php scripts/sync-accesstrade.php --apply --expected-database=dbcouponshubweb_v3 --actor=YOUR_ADMIN_ID --page=1
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

Đổi toàn bộ sang domain HTTPS khi triển khai; tuân theo quy định callback/tester của dashboard tương ứng. Google cần Web OAuth client. Facebook cần Graph version được ứng dụng Meta hỗ trợ; không tự chọn một version cũ từ ví dụ. Các phương thức đăng nhập dùng cùng bản ghi `users` khi email có bằng chứng sở hữu. GitHub dùng email `verified` từ API; Google tự ghép email Gmail/Workspace đã xác minh. Facebook đăng nhập bằng ID đã kiểm tra. Facebook chưa liên kết sẽ chuyển ngay sang Google để chọn và xác thực tài khoản chính, rồi tự liên kết Facebook vào tài khoản đó. Không cần vào Hồ sơ hoặc nhận OTP email. Những lần sau Facebook đăng nhập thẳng bằng ID đã lưu. Không tự ghép theo tên hoặc email Facebook chưa được chứng minh. Khóa tài khoản, quyền và `auth_version` vẫn áp dụng.

Facebook mặc định chỉ yêu cầu `public_profile`, tương thích ứng dụng chưa bật quyền `email`. Chỉ thêm `COUPONHUB_FACEBOOK_REQUEST_EMAIL=1` sau khi bật quyền email cho ứng dụng Meta; nếu chưa bật, Meta có thể báo `Invalid Scopes: email`. Email Facebook chỉ là thông tin tham khảo; xác nhận bằng Google vẫn hoạt động khi Facebook không trả email. Hai bên không bắt buộc trùng email vì người dùng xác thực cả hai danh tính trong cùng luồng. Không bắt buộc bật quyền email Meta cho luồng này.

Xác nhận Facebook–Google dùng state, nonce, PKCE mới; danh tính Facebook chỉ giữ trong session phía server tối đa 10 phút, không lưu access token. Hủy hoặc hết hạn thì bỏ yêu cầu liên kết. Google callback xác thực xong mới resolve/create tài khoản Google và gắn Facebook trong **một transaction**. Nếu Facebook đã thuộc tài khoản khác hoặc tài khoản đã gắn Facebook khác, trả xung đột và rollback toàn bộ, không tự chuyển hoặc gộp dữ liệu. Thời hạn phiên cuối không vượt thời hạn của cả hai bằng chứng xác thực. Cấu hình Google phải sẵn sàng để hoàn tất Facebook lần đầu.

Resend **chỉ dùng cho quên mật khẩu**, không dùng cho đăng nhập hoặc liên kết OAuth. Cần `COUPONHUB_MAIL_DRIVER=resend`, `RESEND_API_KEY`, `RESEND_FROM_EMAIL` thuộc domain đã được xác minh trong tài khoản Resend; xem [Send email](https://resend.com/docs/api-reference/emails/send-email). Đặt `COUPONHUB_OTP_SECRET` ngẫu nhiên tối thiểu 32 ký tự và giữ ổn định cho khôi phục mật khẩu:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
php scripts/check_integrations.php --database
php scripts/check_provider_transport.php --network
```

Lệnh check_provider_transport chỉ GET endpoint công khai Google/GitHub để kiểm tra HTTPS/TLS, không dùng secret. Lệnh check_integrations chỉ in READY/MISSING, không in secret và không xác nhận khóa hợp lệ với provider. Nếu Windows thiếu CA bundle, cấu hình `curl.cainfo`/`openssl.cafile` trong php.ini theo đường dẫn CA tin cậy; giữ nguyên TLS verification.

## Google One Tap trong popup đăng nhập

Nhấn **Đăng nhập** mở dialog CouponHub và chỉ gọi Google One Tap khi khách đã đồng ý cookie tùy chọn. Đóng dialog hoặc chuyển sang
đăng ký/quên mật khẩu sẽ hủy prompt. Không tự mở One Tap khi chỉ vào trang chủ.
Hộp chọn tài khoản do Google/trình duyệt vẽ; trên desktop thường ở góc phải như bản tham khảo.
Với FedCM, vị trí và việc có hiện prompt do trình duyệt quyết định; website không ép được khi
người dùng đã tắt đăng nhập liên kết hoặc đang trong thời gian Google không hiện lại prompt.

```dotenv
COUPONHUB_GOOGLE_ONE_TAP=1
COUPONHUB_GOOGLE_CLIENT_ID=YOUR_WEB_CLIENT_ID.apps.googleusercontent.com
```

Trong Google Cloud, dùng OAuth client loại **Web application**, thêm **Authorized JavaScript origins**
`http://localhost:8000` cho server local đang chạy, và origin HTTPS của bản deploy sau này.
Origin không có đường dẫn `/CouponHub_UI` hay callback. Luồng nút Google chuyển hướng hiện có
vẫn cần `COUPONHUB_GOOGLE_CLIENT_SECRET` và callback khai báo đúng như bảng OAuth phía trên.
One Tap chỉ cần Web client ID; không đưa client secret/Firebase service-account key ra browser.

Hai endpoint POST `/api/oauth/google-one-tap/start` và `/api/oauth/google-one-tap` dùng CSRF của PHP,
kiểm tra origin và rate limit. Nonce/state gắn session, có hạn mười phút và chỉ dùng một lần.
Server kiểm tra chữ ký RS256, audience, issuer, expiry, nonce và email đã xác minh trước khi gọi repository.
Tài khoản mới có quyền USER; email Gmail/Workspace đã xác minh trùng tài khoản cũ sẽ tự liên kết, giữ nguyên dữ liệu và quyền lấy từ database. Google có email bên thứ ba chưa đủ bằng chứng sở hữu chỉ đăng nhập được nếu danh tính Google đã liên kết; không dùng email chưa đáng tin cậy để tự nhận tài khoản cũ. Không có bước OTP trong OAuth.
Session và chuyển hướng admin/merchant/user dùng cùng luồng hiện có. Không cần thêm bảng database.

Không có Client ID thì không tải SDK và không hiển thị prompt; biểu mẫu mật khẩu vẫn dùng được.
Không copy Client ID/secret từ ứng dụng tham khảo sang CouponHub nếu chưa cấu hình dự án Google phù hợp.
Kiểm thử local: `php tests/google-one-tap.php` và `node --test tests/google-one-tap.test.cjs`.
Test giao thức dùng khóa RSA sinh tại chỗ và transport được kiểm soát; việc hiện hộp Google thật,
đăng nhập bằng tài khoản thật và tạo/liên kết dữ liệu thật vẫn phải nghiệm thu sau khi điền cấu hình.

Tài liệu chính thức: [Display Google One Tap](https://developers.google.com/identity/gsi/web/guides/display-google-one-tap),
[JavaScript API](https://developers.google.com/identity/gsi/web/reference/js-reference),
[Verify ID token](https://developers.google.com/identity/gsi/web/guides/verify-google-id-token).

## Swagger và Postman

Cookie: thông báo ở góc trái, lựa chọn được lưu trên trình duyệt tối đa 180 ngày; nhắc một lần trong lượt truy cập mới. Khách có thể mở lại bằng **Quản lý cookie** ở cuối trang. **Chỉ cần thiết** không tải SDK đăng nhập nhanh tùy chọn và không chặn đăng nhập bằng các nút OAuth. Thu hồi đồng ý sẽ hủy prompt đang mở và bỏ qua callback đến muộn. Không xóa cookie phiên đăng nhập khi khách tắt cookie tùy chọn.

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

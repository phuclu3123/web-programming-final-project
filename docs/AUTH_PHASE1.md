# Hoàn thiện session, guard, chuẩn bị OAuth và khung Admin

> Hiện tại: xác thực Google/GitHub và OTP đã có backend. Cấu hình theo [AUTH_SETUP.md](AUTH_SETUP.md), kết quả rà soát mới tại [PHUC_PHASE2_REVIEW.md](PHUC_PHASE2_REVIEW.md). Chính sách phiên/route bên dưới thuộc bản cũ, không dùng để cấu hình hiện tại.

> SQL đã được gom vào [database/schema.sql](../database/schema.sql). Các thư mục routines, migrations, quang-reference và hướng dẫn import nhiều bước bên dưới thuộc lịch sử, đã bỏ. Khi cài mới chỉ theo [database/README.md](../database/README.md); không thực hiện lại các bước SQL cũ.

> Cập nhật 16/10/2026: dự án đã tích hợp nhánh Quang bằng mysqli. Xem [INTEGRATION_QUANG.md](INTEGRATION_QUANG.md) và [SQL tích hợp](../database/README.md) trước khi import/chỉnh endpoint. URL cũ vẫn giữ nhưng xử lý qua router chung; phần hướng dẫn bên dưới mô tả giai đoạn trước tích hợp.

Đây là phần nối tiếp đăng ký/đăng nhập. Các đoạn bổ sung có comment `SESSION`, `GUARD`, `REDIRECT`, `OAUTH` hoặc `KHUNG ADMIN`. Không thay đổi dữ liệu database và không tạo App trên Google/GitHub thay bạn.

## 1. SQL hiện tại

Database local `dbcouponshubweb_v2` đã cài đủ cấu trúc và routine; không cần import lại. Khi cài trên máy mới, tạo database trống và import duy nhất [schema.sql](../database/schema.sql) theo [hướng dẫn hiện tại](../database/README.md).

Các routine Auth nằm trong cùng file SQL này. Quyền shop hiện dùng membership ACTIVE, không chỉ primary owner. Phần mô tả code bên dưới được giữ làm lịch sử học tập; vị trí class mới xem [ARCHITECTURE.md](ARCHITECTURE.md).

## 2. Session / logout / redirect

### Nơi đọc code

- `app/core/Session.php`: mở phiên, ghi user, kiểm tra hết hạn, đọc lại trạng thái từ DB, điều hướng và hủy phiên.
- `app/controllers/UserController.php`: sau password_verify gọi Session::establish và trả redirect.
- `api/login.php`, `api/register.php`, `app/core/AuthApi.php`: HTTP/JSON và kiểm tra POST + CSRF dùng chung.
- `logout.php`: chỉ POST, kiểm tra token rồi hủy session/cookie, redirect 303 về index.php.
- `app/views/partials/account-menu.php`: tên user, link khu vực theo role, form đăng xuất.
- `login.php`: trang Auth chung; khách được mở popup, người đã đăng nhập được chuyển về trang hợp lệ.

### Quy tắc phiên đã chọn

| Nội dung | Hành vi |
|---|---|
| Cookie | COUPONHUBSESSID; HttpOnly; SameSite=Lax; Secure khi server nhận HTTPS |
| Sau đăng nhập | Đổi session ID, ghi user_id/user_role/user_name và thời điểm xác thực |
| Hết phiên | 30 phút không hoạt động hoặc tổng cộng 8 giờ; kiểm tra ở server khi đọc phiên |
| Tài khoản bị khóa/xóa | Đọc trạng thái từ DB ở request tiếp theo và bỏ trạng thái đăng nhập |
| Quyền bị thay đổi | Lấy role mới từ database, không tin role session cũ |
| Đăng xuất | Xóa dữ liệu session, cookie phiên, kết thúc phiên server |

Giới hạn 30 phút/8 giờ là mặc định triển khai lần này, không phải con số bắt buộc trong bài giảng. Điều chỉnh hai hằng trong Session.php nếu nhóm chốt chính sách khác.

### Điều hướng

- USER → `index.php`.
- MERCHANT → `shop.php`.
- ADMIN → `admin.php?section=dashboard`.
- Khách GET trang shop/admin → `login.php?return=...`.
- Đăng nhập thành công → quay lại return_url nếu trang đó được role hiện tại cho phép; nếu không thì về trang mặc định của role.
- Chỉ nhận các tên trang nội bộ đã biết. URL ngoài, đường dẫn dạng `//host`, đường dẫn lạ hoặc ký tự điều khiển đều không được dùng làm redirect.

JS dùng `result.redirect` để chuyển trang, không còn chỉ reload trang test. Sau đăng ký vẫn quay về form login, điền username và yêu cầu nhập mật khẩu lại.

### CSRF thay đổi hợp đồng API

Login/register giờ cần trường `csrf` bên cạnh các trường dữ liệu trước đây. PHP tạo hidden input trong form; `FormData` tự gửi kèm. Request thiếu/sai token trả 403 trước khi truy vấn DB. JS bỏ qua input hidden khi kiểm tra lỗi giao diện và khi tìm ô để focus.

Không gọi API bằng POST trần rồi kết luận hỏng vì 403: phải dùng form và cookie cùng phiên. Cookie phiên lưu trên server được trình duyệt gửi tự động với fetch cùng origin.

## 3. Guard phân quyền và quyền sở hữu

`Session::actor()` đọc `ch_auth_user` theo user_id đang có trong phiên, xác nhận ACTIVE/chưa xóa và so role.

- Không có phiên: 401 ở tầng guard. Trang HTML GET cần quyền được chuyển về Auth bởi bootstrap.
- Có phiên nhưng sai role: 403, kể cả gõ URL trực tiếp.
- `local_demo` mặc định false và đường giả actor demo đã bỏ khỏi guard. Kể cả config/local.php đặt local_demo=true cũng không cấp quyền demo.
- Shop POST tạo coupon cần role MERCHANT, CSRF và `ch_owned_merchant` chứng minh cửa hàng thuộc người hiện tại.
- Routine tạo coupon hiện có vẫn phải giữ kiểm tra ownership/trạng thái ngay trong transaction; kiểm tra PHP không thay cho kiểm tra cuối ở database.
- ADMIN có quyền kiểm duyệt các coupon; chọn ID coupon không phải phép cấp quyền cho USER/MERCHANT.

`AbstractController` nhận `RoutineGateway` thay vì bắt buộc Database cụ thể để kiểm tra controller bằng DB giả; Database hiện tại vẫn triển khai interface đó. Chưa chuyển mysqli sang PDO hoặc tích hợp router của nhánh Quang trong thay đổi này.

## 4. Khung Admin

Đường dẫn đều được ADMIN guard kiểm tra:

| Trang | Trạng thái |
|---|---|
| admin.php?section=dashboard | Tổng quan, lấy số liệu từ routine dashboard hiện có |
| admin.php?section=coupons | Giữ màn hình kiểm duyệt coupon, hàng chờ/lịch sử/chi tiết |
| admin.php?section=merchants | Khung cửa hàng với trạng thái ban đầu |
| admin.php?section=categories | Khung danh mục với trạng thái ban đầu |
| admin.php?section=campaigns | Khung chiến dịch với trạng thái ban đầu |
| admin.php?section=reports | Khung báo cáo với trạng thái ban đầu |

Sidebar và menu mobile có các link này, mục hiện tại được đánh dấu. Route section không hợp lệ trả 404. Các khung mới chỉ GET, không giả vờ đã có CRUD hoặc số liệu khi chưa nối nghiệp vụ. POST duyệt/từ chối chỉ ở mục coupons.

## 5. Chuẩn bị OAuth, chưa bật đăng nhập OAuth thật

Phần này đáp ứng bước chuẩn bị: config, callback, state/PKCE, nonce Google, công cụ kiểm tra và hợp đồng dữ liệu provider. Chưa trao đổi code lấy access token, chưa lấy profile nhà cung cấp, chưa tạo/liên kết tài khoản, chưa tạo session user bằng OAuth. Hai nút Google/GitHub trên form login đang disabled để không báo một khả năng chưa hoàn thiện.

### Nơi đọc code

- `config/oauth.php`, `.env.example`: tên biến môi trường và URL callback.
- `app/core/OAuthPreparation.php`: tình trạng cấu hình, tạo URL thử, lưu state 10 phút, single-use, PKCE S256 Google/GitHub và nonce Google.
- `oauth-setup.php`: trang kiểm tra trên localhost hoặc cho ADMIN đã xác thực.
- `api/oauth/prepare.php`: POST + CSRF, tạo URL callback probe hoặc URL nhà cung cấp.
- `api/oauth/google-callback.php`, `github-callback.php`: provider cố định; callback chung kiểm tra state.
- `database/schema.sql`: đã có bảng `oauth_accounts`; callback preparation chưa ghi bảng này.

### Biến môi trường

```text
COUPONHUB_APP_URL
COUPONHUB_GITHUB_CLIENT_ID
COUPONHUB_GITHUB_CLIENT_SECRET
COUPONHUB_GOOGLE_CLIENT_ID
COUPONHUB_GOOGLE_CLIENT_SECRET
```

Mặc định base URL là `http://localhost:8000/CouponHub_UI`. Nếu chạy PHP với `-t CouponHub_UI`, base URL phải là `http://localhost:8000` vì không có đoạn /CouponHub_UI trong URL.

PHP dùng `getenv()`. Chỉ copy .env.example thành .env KHÔNG tự nạp biến. Với PHP built-in server, đặt biến trong PowerShell rồi khởi động server cùng terminal:

```powershell
$env:COUPONHUB_APP_URL = 'http://localhost:8000'
# Điền các biến GOOGLE/GITHUB CLIENT_ID và CLIENT_SECRET riêng trong môi trường máy bạn.
php -S localhost:8000 -t CouponHub_UI
```

Với Apache/XAMPP, cấu hình biến môi trường cho tiến trình Apache rồi restart, hoặc triển khai bộ nạp env riêng ở bước tiếp theo. Không commit secret. `.env`, `.env.*` và oauth.local.php đã được ignore; .env.example chỉ có chỗ trống.

### Bạn tự đăng ký App

1. Tạo GitHub OAuth App trên tài khoản của bạn. Homepage URL là base URL; callback là `{base_url}/api/oauth/github-callback.php`.
2. Tạo OAuth client loại **Web application** trong Google Cloud Console / Google Auth Platform. Cấu hình consent screen, audience/test users khi ứng dụng còn ở chế độ thử nghiệm. Thêm **Authorized redirect URI** chính xác là `{base_url}/api/oauth/google-callback.php` (đúng giao thức, cổng và đường dẫn). Google đăng nhập chỉ yêu cầu scope `openid email profile`.
3. Điền `COUPONHUB_GOOGLE_CLIENT_ID`, `COUPONHUB_GOOGLE_CLIENT_SECRET` và hai biến GitHub tương ứng vào môi trường chạy PHP. Luồng hiện tại dùng Google OAuth trực tiếp bằng PHP; không yêu cầu Firebase.
4. Mở `oauth-setup.php`: trang chỉ hiện callback và trường thiếu, không in client secret.
5. Nhấn “Thử callback nội bộ”: không cần credentials, không gọi Google/GitHub. Response có prepared=true, authenticated=false.
6. Khi config đủ, có thể tự nhấn “Thử chuyển tới nhà cung cấp”. Callback kiểm tra state rồi báo rằng chưa bật bước đổi token; không gọi đây là đăng nhập thành công.

GitHub khuyến nghị state và PKCE S256 cho authorization-code flow; code preparation đã chuẩn bị hai phần đó theo [GitHub Docs](https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps). Google dùng authorization-code flow với `openid email profile`, state, nonce và PKCE S256 theo [Google OpenID Connect](https://developers.google.com/identity/openid-connect/openid-connect). Nonce được giữ trong session để bước xác minh ID token sau này đối chiếu; callback preparation hiện chưa xác minh ID token. Cách tạo client/callback xem [Google Web Server OAuth](https://developers.google.com/identity/protocols/oauth2/web-server).

### Cấu hình đã đổi về Google + GitHub

- Callback Google: `api/oauth/google-callback.php`; đã bỏ callback nhà cung cấp cũ.
- Bảng `oauth_accounts` trong `database/schema.sql` dùng provider `google/github`; ID Google là claim `sub`, lưu dạng `VARBINARY(255)` để phân biệt hoa/thường.
- Nếu bạn đã tạo bảng từ file SQL mẫu cũ, `CREATE TABLE IF NOT EXISTS` không tự cập nhật bảng đang có. Cần đối chiếu và tự sửa enum/độ dài ID trong database trước khi dùng; không đổi nhãn các tài khoản của nhà cung cấp cũ thành Google.
- Nút Google trở lại logo Google, giữ bố cục và CSS hiện tại. Chỉ trang `oauth-setup.php` có chức năng thử chuyển tới nhà cung cấp ở giai đoạn chuẩn bị.

### Hợp đồng provider cho bước sau

- Khóa nhận diện: provider + provider_user_id, không dùng email làm khóa OAuth.
- Provider: google/github; ID nhà cung cấp lưu dạng chuỗi phân biệt chính xác. Với Google dùng `sub`, không dùng email làm ID.
- Dữ liệu profile chuẩn hóa dự kiến: provider, provider_user_id, display_name, email (nullable), email_verified.
- Không tự gộp tài khoản chỉ vì email khớp; cần chứng minh email được xác minh hoặc người dùng đã đăng nhập đồng ý liên kết.
- Tài khoản mới role USER; không đọc role từ provider/client.
- Không lưu access token/code trong bảng users hoặc gửi secret/token về frontend. Khi triển khai thật, không để access log lưu query code/state của callback.
- Bảng users hiện password_hash NOT NULL: cần chốt cách tạo tài khoản OAuth (ví dụ hash ngẫu nhiên không dùng làm mật khẩu người dùng) trước bước tạo user. Bản preparation chưa giải quyết bằng cách INSERT dữ liệu giả.

## 6. Đã kiểm tra và giới hạn

Đã chạy:

```powershell
php CouponHub_UI/tests/auth-registration.php
php CouponHub_UI/tests/session-guards.php
node --check CouponHub_UI/assets/js/auth/auth.js
node --test CouponHub_UI/tests/auth-ui.test.cjs
```

- Tests với DB giả: đăng ký, hash, lỗi trùng, đăng nhập, ba role, chủ cửa hàng, khóa/xóa, hết phiên, redirect, CSRF/logout, OAuth state/hết hạn/replay/PKCE.
- Tests JS với DOM/fetch giả: gửi CSRF, không trim mật khẩu, redirect theo server, lỗi API/mạng/JSON, chống submit lặp, chuyển form sau đăng ký và chưa gửi OTP.
- Render sáu route Admin qua controller với DB giả, kiểm tra link và thông tin tài khoản/logout.
- HTTP thật trên PHP built-in server, không dùng database: cookie session, token, POST-only, input array sai, cross-origin, redirect khách, callback probe/replay, logout xóa cookie.
- Chưa test guard/routine với MySQL của bạn và chưa test OAuth với App thật. Bạn tự import SQL/cấu hình App rồi kiểm tra tiếp.
- Chưa bao gồm CRUD Admin mới, quên mật khẩu thật, OAuth token exchange/linking, rate-limit đăng nhập và đồng bộ kiến trúc với Quang.

## 7. Checklist bạn tự thử sau khi import SQL

1. USER đăng nhập → index có tên, nút đăng xuất; gõ admin.php/shop.php → 403.
2. MERCHANT đăng nhập → shop; tạo coupon với cửa hàng mình sở hữu; sửa merchant_id sang của người khác → 403.
3. ADMIN đăng nhập → dashboard; sáu link hoạt động; mục Coupon vẫn duyệt/từ chối được.
4. Đăng xuất → về index; vào lại trang cần quyền → Auth chung; nút Back không cấp lại quyền ở server.
5. Đang đăng nhập, thay status LOCKED/deleted_at hoặc role trong DB → request tiếp theo mất quyền tương ứng.
6. Vào admin.php?section=reports lúc chưa login → login → đăng nhập ADMIN → quay đúng Reports; đăng nhập USER → index.
7. Return URL ngoài ứng dụng không được chuyển tới, CSRF sai bị từ chối.
8. OAuth probe đi/về thành công nhưng không có user session; state dùng lại trả 403.

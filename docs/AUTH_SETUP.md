# Đăng nhập và khôi phục mật khẩu CouponHub

## Đã triển khai

- Email/username + mật khẩu tiếp tục dùng AuthService và users hiện tại.
- GitHub OAuth: đổi authorization code ở PHP, PKCE, state có thời hạn/dùng một lần, đọc danh tính và email đã xác minh từ GitHub.
- Google OAuth Web: kiểm tra chữ ký RS256 bằng PEM công khai từ Google, issuer, audience, expiry, nonce và email_verified; không tin payload JWT chỉ được decode.
- Tài khoản OAuth mới luôn có quyền USER. Danh tính đã liên kết đi vào tài khoản cũ; email trùng tài khoản local không được tự động gộp. Đăng nhập tài khoản local rồi vào **Hồ sơ → Tài khoản liên kết** để liên kết chủ động.
- Quên mật khẩu ngay trong popup: email → OTP 6 số → mật khẩu mới. Gửi bằng Resend; SMTP là lựa chọn riêng nếu cấu hình rõ ràng.
- OTP có hạn 10 phút, tối đa 5 lần nhập sai, dùng một lần. Gửi lại thu hồi token cũ. Session chỉ giữ HMAC của OTP và token reset ngẫu nhiên; DB giữ hash của token. Không có OTP trong response, log, localStorage hay Git.
- Đổi/reset mật khẩu tăng auth_version và thu hồi mọi token reset chưa dùng; request tiếp theo từ phiên cũ bị từ chối. Đăng xuất hủy phiên server và cookie hiện tại.
- Nạp `.env` ở project root trước khi đọc cấu hình; biến môi trường đã có được ưu tiên. Không tự ghi `.env` hoặc sao chép secret từ WEB_TCC.

Không cần import lại SQL hoặc tạo thêm bảng nếu đã dùng schema CouponHub hiện tại có `oauth_accounts` và `password_reset_tokens`. Không ghi hay seed database thật trong đợt triển khai này.

## Cấu hình để thử thật

1. Sao chép `.env.example` thành `.env` tại thư mục gốc CouponHub_UI, cạnh `index.php`.
2. Giữ đúng thông tin DB hiện tại. Đặt `COUPONHUB_APP_URL` bằng URL thực sự bạn mở trong trình duyệt, gồm thư mục con nếu có. Không trộn `localhost` và `127.0.0.1` trong một luồng.
3. Điền GitHub OAuth App Client ID/Secret. Callback chính xác:
   - `http://localhost:8000/api/oauth/github-callback.php` nếu chạy PHP tại root.
   - Nếu APP_URL là `http://localhost/CouponHub_UI` thì callback là `http://localhost/CouponHub_UI/api/oauth/github-callback.php`.
4. Google Cloud: tạo OAuth Client loại **Web application**, điền ID/Secret của client đó. Callback là `APP_URL + /api/oauth/google-callback.php`. Nếu ứng dụng đang ở chế độ Testing, thêm tài khoản thử theo cấu hình Google Console. Không dùng Firebase service-account key làm client secret.
5. Đặt `COUPONHUB_MAIL_DRIVER=resend`, `RESEND_API_KEY`, `RESEND_FROM_EMAIL` bằng sender/domain được xác minh trong Resend của bạn. Không dùng nguyên domain ví dụ. Sender thử `onboarding@resend.dev` bị giới hạn người nhận theo tài khoản Resend; dùng domain đã xác minh để thử cho nhiều người.
6. Tạo secret ngẫu nhiên một lần rồi điền `COUPONHUB_OTP_SECRET`, giữ ổn định giữa các lần chạy:

```powershell
C:/xampp/php/php.exe -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
C:/xampp/php/php.exe scripts/check_auth_config.php --db
```

Lệnh kiểm tra chỉ báo mục đủ/thiếu, không in giá trị secret, gửi email hay ghi database. Không đưa `.env`, API key hoặc client secret vào ảnh chụp/tin nhắn.

Chạy từ **project root** bằng `php -S localhost:8000 router.php`, hoặc dùng Apache với `.htaccess` root hiện có. `public/index.php` hiện là đường chuyển về ứng dụng chính, không phải document root độc lập chứa toàn bộ assets. Không chạy `php -S ... -t public` cho cấu trúc này.

Sau khi sửa `.env`, khởi động lại PHP server/Apache để cấu hình có hiệu lực nhất quán. Nếu cURL trên Windows báo lỗi CA certificate, cấu hình `curl.cainfo`/`openssl.cafile` trong php.ini theo CA bundle hợp lệ; không tắt SSL verification để thử OAuth hoặc Resend.

## Các ca bạn thử với cấu hình thật

1. Đăng nhập bằng email/mật khẩu, tải lại và mở tab khác: vẫn đúng tài khoản; Admin/Merchant vào đúng trung tâm. Đăng xuất rồi thử URL được bảo vệ.
2. GitHub/Google chưa liên kết: tài khoản mới có quyền USER. Tài khoản provider có email trùng tài khoản cũ phải đăng nhập tài khoản cũ rồi liên kết trong hồ sơ; thử lại đăng nhập provider sau khi liên kết.
3. Hủy ở nhà cung cấp, quay lại và đăng nhập lại; thử ở cả URL root hoặc subfolder bạn thực sự sử dụng.
4. Quên mật khẩu: nhận mail Resend thật, nhập sai một lần rồi nhập đúng; đăng nhập bằng mật khẩu mới. Xác nhận mật khẩu cũ và những tab đang đăng nhập trước đó không còn dùng được.
5. Thử 5 lần OTP sai, mã hết 10 phút, mã đã dùng, gửi lại sau 60 giây và dùng mã cũ. Email không tồn tại nhận cùng thông báo chung, không tạo tài khoản mới.

OTP gắn với phiên trình duyệt đã yêu cầu mã: nhập mã trong popup/tab cùng trình duyệt đó. Chỉ mở popup không gửi email. Nút nhà cung cấp chỉ được bật khi có đủ cấu hình, không còn trạng thái “sắp ra mắt” cố định.

## Session/cookie và cách chuyển từ Node sang PHP

Tham khảo đã đọc: WEB_TCC `authController.js`, `sessionService.js`, `emailService.js`, cấu hình reset và các test auth; bài PHP Lesson 7 về session/cookie, Lesson 11 về đăng nhập, Lesson 13 về phân quyền và Lesson 15 về OOP/MVC/AJAX trong thư mục bài giảng bạn cung cấp.

Giữ cách PHP thông thường: `session_start()`, `$_SESSION`, cookie chứa ID phiên không đoán được, `password_hash`/`password_verify`, mysqli prepared statement và transaction. Các controller/service/repository tiếp tục nằm trong kiến trúc hiện tại. Không chuyển ứng dụng sang Node, Firebase, JWT lưu phía client hoặc thêm framework.

- Cookie HttpOnly, host-only, SameSite=Lax; Secure khi canonical APP_URL là HTTPS hoặc request HTTPS. Không tin X-Forwarded-Proto tùy ý từ client.
- Session ID đổi khi đăng nhập; CSRF đổi theo phiên. State/PKCE/nonce chỉ ở server. Access/refresh token không đưa vào cookie, HTML, JSON hay lưu DB.
- Mặc định tài khoản local/GitHub tồn tại tối đa 30 ngày như WEB_TCC; có thể giảm `COUPONHUB_SESSION_LIFETIME` và `COUPONHUB_SESSION_IDLE`. Phiên Google bị giới hạn thêm theo hạn ID token, tương tự giới hạn phiên Firebase trong WEB_TCC. Đến hạn cần đăng nhập lại; chưa có silent refresh.
- `.env.example` chọn `storage/sessions` riêng để tránh GC của dự án PHP khác xóa phiên. Thư mục cần quyền ghi cho PHP. Cookie không chứa role đáng tin; quyền/trạng thái/auth_version được đọc lại từ DB.
- Có giới hạn đăng nhập/đăng ký/khởi tạo OAuth và OTP bằng file + flock tại thư mục cache không công khai. Cách này dành cho một PHP server; khi chạy nhiều máy cần kho rate-limit/session dùng chung.

Khác biệt có chủ ý với WEB_TCC: dùng Google OAuth trực tiếp thay cho Firebase; không tự gộp theo email, không lấy quyền Admin từ email hoặc claim của provider, không tạo email GitHub giả khi thiếu email đã xác minh, không in OTP để giả lập gửi mail.

## Kiểm chứng và giới hạn

- `tests/oauth-auth.php`: provider HTTP giả lập, RSA key tự tạo; state, PKCE, replay, hết hạn, hủy, email GitHub chưa xác minh, ngữ cảnh liên kết, chữ ký Google/audience/issuer/expiry/nonce.
- `tests/auth-complete-integration.php`: MariaDB riêng `couponhub_merge_test:13379`; reset OTP, 5 lần sai, expiry, resend, mail lỗi, auth_version, không tự gộp email, liên kết chủ động, tài khoản khóa và payload Resend. Mail được bắt bởi transport giả, không gửi email thật.
- `tests/auth-complete-http.py`: PHP HTTP thật + SMTP fixture localhost; CSRF, cooldown, response không lộ OTP/token, cookie persistent/HttpOnly/Lax, thu hồi hai phiên sau reset, OAuth start và cancellation. Không gọi OAuth/Resend thật.
- `tests/env-auth.php`: ưu tiên env, dấu nháy, giá trị trống, comment, lỗi không lộ secret và rate limit.
- Giữ các kiểm thử đăng nhập/phân quyền/root-subfolder, regression giao diện auth và nghiệp vụ hiện có.

Chưa xác nhận tài khoản nhà cung cấp thật, sender/domain Resend, email vào inbox hoặc cấu hình HTTPS của máy bạn: cần `.env` và thử thực tế ở bước trên. Không khẳng định đã nghiệm thu production. Rate limit một máy, GC/session storage, log truy cập callback và tải đồng thời cần được cấu hình phù hợp khi triển khai thật. Với request gửi mail đồng bộ, thời gian phản hồi có thể khác nhau dù nội dung phản hồi che sự tồn tại tài khoản.

Tài liệu chính thức đã đọc:

- [PHP session management](https://www.php.net/session.security.management) và [session cookie parameters](https://www.php.net/session-set-cookie-params).
- [GitHub OAuth web application flow](https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps).
- [Google backend ID-token validation](https://developers.google.com/identity/sign-in/web/backend-auth).
- [Resend Send Email API](https://resend.com/docs/api-reference/emails/send-email).

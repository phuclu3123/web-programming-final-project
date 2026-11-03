# Phúc đợt 4 — triển khai và nghiệm thu

Ngày rà soát: 04/11/2026. Nền: `668e160` trên `origin/main`, gồm các PR Quang #10/#11. Checkout local đã fast-forward từ bản nghiệm thu đợt 3; không ghi đè thay đổi cá nhân hoặc lấy lại các module từng hoãn.

## Phạm vi và cách làm

Workbook phân công chỉ cung cấp danh sách A4.01–A4.06 ở sheet Phúc ADMIN; không lấy đánh dấu hoàn thành hoặc chỉ dẫn kỹ thuật làm bằng chứng. Code và kiểm thử quyết định kết quả.

Giữ PHP/mysqli, class/repository hiện tại, form POST/redirect và session server. Tham khảo trực tiếp slide trong `drive-download-20261010T105110Z-1-001`:

- Lesson 7, slide 3–8: session lưu server, cookie giữ ID, `session_start()` trước output, hủy cookie bằng thời hạn quá khứ.
- Lesson 11, slide 4: nhận form → kiểm tra DB → lập session → chuyển trang theo kết quả.
- Lesson 13, slide 3: quyền gắn với tài khoản và phiên; ở ứng dụng, quyền/trạng thái còn được đọc lại từ DB.
- Lesson 15: giữ tổ chức class/OOP hiện có. Không thêm framework hay dịch vụ xác thực riêng.

Các ví dụ cũ về MD5/SQL không được dùng để thay thế `password_hash`/`password_verify` và prepared statement hiện tại.

## Danh sách A4

| Mục | Thực hiện | Giới hạn nghiệm thu |
|---|---|---|
| A4.01 GitHub OAuth | Luồng code/state/PKCE, email verified, callback và vai trò server được giữ và kiểm thử lại | Chưa đăng nhập tài khoản GitHub thật trong lượt này |
| A4.02 Facebook OAuth | Thêm cấu hình, route/callback, state một lần, đổi code server, introspection app/user/type/expiry, `/me` và appsecret proof | Chưa có ứng dụng Meta/credential để xác nhận thực tế; Graph version bắt buộc cấu hình |
| A4.03 Liên kết an toàn | Ràng buộc actor + auth_version khi bắt đầu/callback và trong transaction; unique provider identity/user-provider; không ghép theo email | Facebook cần liên kết lần đầu từ tài khoản đã đăng nhập; không tạo tài khoản bằng email chưa xác minh |
| A4.04 Bảo vệ action | Router role + POST + CSRF + Origin; kiểm tra actor trong transaction/routine; audit, hết quyền thì không ghi | Kiểm thử HTTP root và thư mục con, không thay thế kiểm tra hạ tầng production |
| A4.05 Tích hợp | Dùng main mới đã có PR Quang; một router/schema/session chung, giữ UI đăng nhập popup | Không tự đưa module ngoài phạm vi vào codebase |
| A4.06 Review xuyên luồng | Chạy lại đợt 3 và bộ đợt 4: ghi–duyệt–campaign–thu hồi–khóa/mở, tài khoản, OAuth/OTP, trực tiếp URL | OAuth/email thật và TLS/browser trên host thật vẫn là bước bàn giao cần làm |

**Phần code và nghiệm thu local đã qua; chưa đóng nghiệm thu OAuth/email thật.** Không kết luận hoàn thành nhà cung cấp thật chỉ từ fake HTTP. Chưa cấu hình `.env` trong lượt này.

## Các bảo vệ hiện tại

| Nội dung | Cách xử lý |
|---|---|
| Session/cookie | Server session, strict mode, cookie-only, HttpOnly/Lax; Secure khi canonical URL là HTTPS; đổi session ID và CSRF sau login; logout hủy server/cookie |
| Thời hạn | USER theo cấu hình, tối đa 30 ngày; ADMIN/MERCHANT tối đa 8 giờ, idle tối đa 30 phút; hết hạn được kiểm tra trong ứng dụng |
| Thu hồi phiên | `auth_version` được đối chiếu DB; đổi mật khẩu/reset và khóa/mở User làm phiên cũ mất hiệu lực, kể cả không truy cập trong khoảng bị khóa |
| Phân quyền | Mỗi request bảo vệ đọc role/status DB; ownership và trạng thái shop kiểm tra server; action trọng yếu giữ actor/đối tượng trong transaction |
| CSRF/Origin | POST có token, từ chối cross-site/Origin sai; canonical HTTPS Origin hoạt động khi proxy kết thúc TLS; không tin X-Forwarded-* do client gửi |
| Rate limit | Auth theo IP/account, reset cooldown; mọi POST hợp lệ thêm 120/IP/phút, 60/user/phút; trả 429 và Retry-After. File+flock dùng chung giữa session trên một server, lỗi ghi trả 503 |
| Request/HTTP | Body ghi tối đa 1 MiB tại router; no-store cho HTML/JSON động; nosniff, DENY frame, CSP giới hạn frame/base/object; không gọi đây là CSP đầy đủ cho scripts |
| JWT | Không phát JWT riêng. Google ID token RS256 được xác minh chữ ký/aud/iss/nonce/exp/iat/email_verified; Bearer giả không tạo phiên PHP |
| Access/refresh token | Token OAuth chỉ dùng trong request server để xác minh, không lưu browser/localStorage/session/DB. Không xin offline access hay giữ refresh token; hết phiên thì đăng nhập lại |
| Cache | Nội dung có session/CSRF không dùng shared cache. Tài nguyên tĩnh tiếp tục do server phục vụ. Không thêm Redis hoặc cache kết quả phân quyền |
| Mật khẩu/OTP | Prepared SQL, password_hash/verify; OTP 10 phút/5 lượt, mã băm có khóa trong session, token hash trong DB, tiêu thụ nguyên tử; không in mã/token trong lỗi |
| Gọi HTTPS ra ngoài | Endpoint chọn ở server, cURL kiểm chứng TLS, không redirect, timeout, chặn response trên 1 MiB ngay trong lúc nhận |
| Source/backup | Router chặn source/config/storage/dotfiles; Apache thêm chặn tải SQL, log, backup và tài liệu/archive đặt cạnh ứng dụng |

File rate limit phù hợp một máy PHP/XAMPP. Khi chạy nhiều instance cần một nơi lưu/cơ chế giới hạn dùng chung; cấu hình proxy/web server giới hạn kết nối/body trước PHP. Không bật tin cậy forwarded IP đại trà. Thư mục session/rate phải riêng tư, writable; các file limit đã hết hạn có thể dọn khi ứng dụng dừng để tránh xóa inode đang được process khác khóa.

Thời hạn USER 30 ngày giữ theo cấu hình trước đây của dự án. Đây là persistent session, không phải cơ chế remember-me có token xoay vòng. Khi mở public có thể hạ `COUPONHUB_SESSION_LIFETIME`/`IDLE`; không coi thời hạn dài là mặc định an toàn cho mọi môi trường. ADMIN/MERCHANT đã có giới hạn ngắn hơn ở server.

## Những lỗi đã xử lý

- Khóa rồi mở User trước đây chỉ đổi status, có thể làm phiên cũ hoạt động lại. Nay tăng auth_version và ghi audit trong transaction; cấm tự khóa mình, actor phải là Admin còn hoạt động.
- Toggle category, article save, cấp/thu hồi membership và Admin detach thiếu kiểm tra actor tại tầng ghi. Đã bổ sung; detach Merchant cũng giữ quyền/trạng thái shop và quan hệ trong transaction.
- Routine attach/moderate/resolve giữ khóa chia sẻ trên actor trong thời gian ghi để không quyết định theo quyền đã thay đổi giữa chừng.
- Bổ sung audit thay trạng thái User/category, lưu bài viết và gỡ quan hệ campaign; không log provider token/secret.
- Câu SQL bài viết so sánh string parameter có thể gây lỗi collation MariaDB. Dùng cờ số đã tính từ status hợp lệ, giữ UTC và thời điểm xuất bản đầu tiên.
- Origin cũ suy ra scheme từ backend HTTP nên có thể từ chối POST hợp lệ qua HTTPS proxy. Nay dùng canonical URL cấu hình.
- Rate limiter kiểm tra đầy đủ lỗi ghi, đóng file khi lock thất bại, đường dẫn tương đối quy về project; recovery bằng link cũ cũng được rate-limit.
- Form Admin từ chối trường dạng mảng trước khi ép kiểu, tránh `(int)array` vô tình chọn bản ghi 1. Lỗi SQL lưu bài viết không được đưa nguyên văn ra flash.

## Chạy nghiệm thu

`COUPONHUB_RUN_DB_TEST=1 python tests/run-phase4.py` yêu cầu MariaDB thử riêng `127.0.0.1:13379`, database `couponhub_merge_test`; từ chối `config/local.php`. Runner reset **chỉ database thử này**, dùng server HTTP riêng và dừng sau test. Không dùng database mặc định cổng 3306.

Runner bao gồm hồi quy đợt 3; các sửa Quang mới; OAuth Facebook giả lập; cookie/session/Origin; migration chạy hai lần có identity sẵn; ghi/quyền/audit thực với MariaDB; mọi route Admin ở root/subfolder; giới hạn request và thử 20 process tranh cùng một quota 7 lượt.

Kết quả thực tế:

- Runner toàn bộ qua tại `.runtime/phase4-review-1793726174678497700/`: toàn bộ hồi quy đợt 3, **52/52 JavaScript tests**, các ca PHP/DB/HTTP, migration hai lần không đổi snapshot tài khoản/coupon/pivot/identity, quota đồng thời **7 qua / 13 bị chặn**.
- HTTP kiểm tra **36 route Admin ở mỗi base URL** (`/` và `/CouponHub_UI`), User/Merchant/Guest không vượt quyền; mọi POST Admin từ chối CSRF sai. Bộ kiểm tra cuối bổ sung malformed array với token Admin đúng cũng bị chặn 422 trước action.
- Sau các tinh chỉnh cuối về hình dạng input Admin và metadata Facebook, PHP syntax và nhóm OAuth/session/router/HTTP liên quan đều qua; log `.runtime/phase4-final-1793726772815421500/`.
- Probe bằng **HttpClient cURL thật** tới endpoint GitHub công khai đã qua với TLS verification bật. Không gửi credential, không coi probe này là đăng nhập GitHub thật.
- Database thử được khôi phục fixture, các HTTP server thử đã dừng. Không thay database đang dùng, không bật tài khoản nhà cung cấp hoặc dịch vụ có phí.
- Chưa xác nhận UI trực quan trên mọi thiết bị, cấu hình Apache thực tế hoặc HTTPS trên host production.

## Áp dụng lên database đang có dữ liệu

1. Backup database đang sử dụng.
2. Nếu chưa có bản đợt 3: áp `database/migrations/20261031_phase3_risk_controls.sql` trước.
3. Áp `database/migrations/20261104_phase4_auth_guards.sql` vào đúng database. Thêm enum Facebook và cập nhật ba procedure, không seed hoặc thay tài khoản/coupon. Có thể cần thời gian khóa bảng khi ALTER, nên làm lúc ít truy cập.
4. **Không import lại schema.sql/demo.sql lên database có dữ liệu.** Bản cài trống dùng schema mới, demo tùy chọn.
5. Kiểm tra quyền thư mục session/cache và canonical `COUPONHUB_APP_URL`; production dùng HTTPS.

Không tự áp migration lên DB đang dùng trên máy người dùng trong lượt này.

## Cấu hình và nghiệm thu thật còn cần thực hiện

Điền `.env` riêng, không gửi secret vào chat hoặc commit Git:

```dotenv
COUPONHUB_APP_URL=https://your-host.example/CouponHub_UI
COUPONHUB_GITHUB_CLIENT_ID=
COUPONHUB_GITHUB_CLIENT_SECRET=
COUPONHUB_FACEBOOK_CLIENT_ID=
COUPONHUB_FACEBOOK_CLIENT_SECRET=
COUPONHUB_FACEBOOK_GRAPH_VERSION=
```

Graph version chọn đúng phiên bản được hỗ trợ trong Meta dashboard; không tự chọn “latest” hoặc suy ra từ sheet. Callback tương ứng APP_URL + `/api/oauth/github-callback.php` và `/api/oauth/facebook-callback.php`. Facebook chỉ xin public_profile, không cần quyền email vì không dùng email Facebook để đăng ký/ghép tài khoản. Đăng nhập CouponHub → Tài khoản → Liên kết Facebook → cho phép → đăng xuất → đăng nhập bằng Facebook. Không có tài khoản CouponHub thì đăng ký email trước.

Chạy `php scripts/check_auth_config.php --facebook --db` để kiểm tra cấu hình/schema **không in secret**, không gửi mail. Lệnh này không xác nhận credential đúng hoặc provider hoạt động. Công cụ `/oauth-setup.php` có probe nội bộ cũng không phải đăng nhập thật.

Cần thử với app role/tài khoản Meta được phép, kiểm tra cancel/deny, liên kết sai chủ, đăng nhập lại, callback chính xác root/subfolder và app review/quyền truy cập nếu mở cho người ngoài app. Chính sách và dashboard Meta quyết định khả năng mở public. Khi triển khai kiểm tra Set-Cookie Secure/HttpOnly/Lax, logout/back, POST qua proxy, email thật và log không lộ code/token; tắt lưu query callback nhạy cảm trong access log.

Nguồn kỹ thuật bổ sung: [PHP session security](https://www.php.net/manual/en/features.session.security.management.php), [Meta manual login flow](https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow/), [Meta debug_token](https://developers.facebook.com/docs/graph-api/reference/debug_token/), [GitHub OAuth flow](https://docs.github.com/en/apps/oauth-apps/building-oauth-apps/authorizing-oauth-apps). Trang Meta giới hạn truy cập lúc rà soát; đã tham khảo thêm mã SDK chính chủ đã lưu trữ của Meta về introspection, không cài SDK cũ hay coi đó là bằng chứng tương thích live.

# Rà soát giai đoạn 2 của Phúc — 22/10/2026

## Kết luận

**Phần code xác thực/tài khoản và các luồng tích hợp được kiểm tra bên dưới đã qua kiểm thử local sau khi sửa lỗi. Chưa đóng nghiệm thu toàn bộ giai đoạn 2: chưa cấu hình và thử Google/GitHub, Resend cùng môi trường HTTPS thực tế.**

Phạm vi lấy từ các yêu cầu gần nhất: xác thực tương đương WEB_TCC bằng PHP, popup đăng nhập, tài khoản, session/cookie, phân quyền và điều hướng. Checkout chưa có bảng phân công giai đoạn 2 đầy đủ của từng thành viên; báo cáo này không xác nhận những đầu việc nằm ngoài phạm vi đã đọc/kiểm tra. Không lấy tên nhánh hoặc lời ghi “đã xong” làm bằng chứng hoàn thành.

Nền Git trước đợt bổ sung: `572bc3a4a6c34e8aabc173b24413ca94f6f7befe`, nhánh `main`. Mã xác thực, bản sửa lỗi và kiểm thử được bàn giao cùng báo cáo này; cấu hình dịch vụ thật vẫn chờ nghiệm thu theo các mục bên dưới. Không thay đổi UI tổng thể hay chuyển công nghệ.

## Kết quả theo luồng

| Nội dung | Bằng chứng hiện tại | Trạng thái |
|---|---|---|
| Đăng ký, đăng nhập email/username, validation mật khẩu | Unit test, mysqli thật, HTTP thật; role/status từ client không cấp quyền | Đạt local |
| Popup đăng nhập/đăng ký/quên mật khẩu | Test DOM/fetch, endpoint thật, CSRF, lỗi mạng/server và điều hướng cùng origin | Đạt kiểm tra tự động; chưa nghiệm thu giao diện trên mọi thiết bị |
| Admin/Merchant và URL thư mục con | 6 tài khoản demo, JSON/HTML, `/` và `/CouponHub_UI`, URL cũ, return URL và truy cập sai quyền | Đạt local |
| Session/cookie/đăng xuất | Đổi ID khi login, persistent HttpOnly/Lax, timeout, đọc lại quyền/trạng thái/version từ DB; cookie Secure có điều kiện HTTPS | Đạt các ca local; HTTPS thật chưa thử |
| Hồ sơ/đổi mật khẩu | Không đổi role qua form; mật khẩu hiện tại bắt buộc, thu hồi token reset và các phiên cũ, từ chối cập nhật mật khẩu theo hash cũ | Đạt local |
| Google | RSA fixture ký token, kiểm tra chữ ký/audience/issuer/expiry/nonce/email_verified, không nhận token giả | Đạt test giao thức; chưa đăng nhập Google thật |
| GitHub | HTTP provider giả lập: code exchange, PKCE/state một lần, email verified, provider ID; HTTP start/cancel/replay thật | Đạt test giao thức; chưa đăng nhập GitHub thật |
| Tạo/liên kết OAuth | DB thật: USER mặc định, không tự gộp email, không chiếm danh tính tài khoản khác, giữ quyền Admin/Merchant từ DB | Đạt local |
| OTP quên mật khẩu | Mail bắt tại SMTP fixture, 10 phút/5 lần sai, một lần, gửi lại thu hồi mã cũ, mail lỗi thu hồi token | Đạt local |
| OTP đúng nhưng tài khoản/token không còn hợp lệ | DB hết hạn dù session còn hạn; khóa/xóa sau khi phát mã: không đổi mật khẩu hoặc auth_version | Đạt DB integration |
| Resend | Kiểm tra payload qua transport giả; không gọi API/gửi mail thật | Chờ cấu hình và thư vào inbox thật |
| SQL bàn giao | Schema 21 bảng, 6 view, 21 procedure; demo riêng, chặn import lặp và rollback lỗi seed | Đạt DB cô lập |
| Hồi quy module hiện có | Membership/ownership, coupon, duyệt, tìm kiếm, tương tác, lịch, thông báo, hồ sơ và các trang Admin | Đạt các suite bên dưới |

## Lỗi và thiếu sót đã xử lý trong lần rà soát

1. **Hủy liên kết OAuth từ Hồ sơ đi nhầm `/login`.** Tái hiện bằng HTTP test bị fail trước khi sửa. Callback có ngữ cảnh liên kết do server lưu nay trả về `/account`, giữ tài khoản hiện tại và hiển thị thông báo ở Hồ sơ. Không nhận URL chuyển tiếp từ callback.
2. **Callback không có state hợp lệ/đã dùng bị đổi từ 403 thành redirect.** Suite HTTP cũ phát hiện hồi quy. Nay từ chối trước khi đọc Auth/DB; probe và callback thật dùng lại đều trả 403, không tạo đăng nhập. Callback hủy của một yêu cầu còn hiệu lực vẫn có thông báo thân thiện.
3. **Công cụ kiểm tra cấu hình đánh dấu sai nguồn env và bỏ sót mail driver.** Env của process đủ cấu hình được chấp nhận dù không có `.env`; driver sai bị báo thiếu. `--db` kiểm tra các cột cần dùng và routine `ch_auth_user`, thay vì chỉ kiểm tra tên ba bảng.
4. **Test mysqli cũ yêu cầu schema trống nhưng thiếu chặn fixture.** Lần chạy trên demo gặp trùng `levels.min_points`; đây là xung đột dữ liệu thử, không phải lỗi nghiệp vụ. Bổ sung chặn `config/local.php`, xác nhận DB/port thực tế và schema chưa có user trước khi ghi. Chạy lại trên schema trống đã qua.

## Kiểm chứng đã chạy

- `tests/env-auth.php`, `tests/oauth-auth.php`, `tests/session-guards.php`, `tests/auth-registration.php`, `tests/account-service.php`, `tests/router-integration.php`, `tests/admin-phase2.php`.
- `node --test tests/auth-ui.test.cjs tests/bootstrap5-contract.test.cjs tests/campaign-clock.test.cjs tests/hero-carousel.test.cjs`: **21/21**.
- `tests/sql-install.py`: import schema/demo, quyền/tài khoản fixture, chặn import trùng, rollback khi seed lỗi.
- `tests/auth-complete-integration.php`: reset, trạng thái tài khoản, linking, phiên/quyền và payload mail trên MariaDB riêng.
- `tests/auth-complete-http.py`: PHP thật + SMTP localhost; reset, thu hồi hai phiên, cookie, CSRF, OAuth hủy/replay và hủy liên kết giữ Hồ sơ.
- `tests/mysqli-integration.php` + `tests/http-integration.py`: schema trống riêng, toàn bộ luồng tích hợp nền, route ba vai trò, probe/replay và logout chung.
- `tests/truc-merge.php`, `tests/truc-integration.php`, `tests/login-redirects.py`, `tests/quang-integration.php`, `tests/quang-http.py`: regression tích hợp và phân quyền.
- PHP syntax: **233 file**; kiểm tra lại controller/config script sau bản sửa cuối. JS syntax và `git diff --check` qua.

Các ca có ghi chỉ chạy tại `couponhub_merge_test`, port **13379**. Đã dùng fixture riêng theo yêu cầu của từng suite, không trộn schema-only với demo. Không seed/import/update database đang dùng trên port 3306. `scripts/check_auth_config.php --db` chỉ đọc cấu trúc của DB đang dùng và xác nhận đủ cột/routine xác thực, không gửi email hoặc in secret.

## Phần chưa được nghiệm thu

Lần kiểm tra cấu hình thực tế vẫn báo chưa có `.env`, canonical APP_URL, client ID/secret Google/GitHub và OTP secret. Mail mặc định đang là SMTP localhost, chưa phải Resend được cấu hình. Cần làm theo [AUTH_SETUP.md](AUTH_SETUP.md), không import lại database.

Trước khi đóng nghiệm thu xác thực:

1. Điền `.env` đúng máy, callback root/subfolder và domain gửi thư đã xác minh; chạy `php scripts/check_auth_config.php --db`.
2. Đăng nhập Google/GitHub thật, hủy/đăng nhập lại, email trùng và liên kết chủ động, kiểm tra quyền sau callback.
3. Nhận OTP qua Resend thật, thử mã sai/đúng/cũ/hết hạn, đăng nhập mật khẩu mới và xác nhận phiên cũ bị thu hồi.
4. Kiểm tra cookie Secure/callback trên HTTPS nếu triển khai cho khách; quyền ghi và vòng đời thư mục session/cache phải phù hợp máy chủ. File rate-limit/session hiện dành cho một máy, không phải nghiệm thu hạ tầng nhiều máy hay kiểm thử tải đồng thời.

Các test dùng transport/RSA giả chứng minh logic xử lý và các trường hợp từ chối; chúng không chứng minh credential, cấu hình callback nhà cung cấp, TLS của môi trường triển khai hoặc khả năng giao thư vào inbox. Không có cơ sở gán phần trăm hoàn thành hoặc gọi đây là nghiệm thu production.

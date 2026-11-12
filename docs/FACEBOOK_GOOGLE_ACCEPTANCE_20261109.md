# Nghiệm thu Facebook xác nhận bằng Google — 09/11/2026

## Hành vi

1. Facebook đã có trong `oauth_accounts`: đăng nhập thẳng vào đúng `users.id`.
2. Facebook chưa liên kết: kiểm tra code, app ID, token, user ID và thời hạn của Facebook; chuyển ngay sang Google OAuth để chọn tài khoản chính.
3. Google xác thực thành công: tìm hoặc tạo USER theo danh tính Google đã kiểm tra, gắn Facebook vào cùng tài khoản trong một transaction, rồi đăng nhập và quay về trang ban đầu theo quyền thật.
4. Các lần sau dùng Facebook trực tiếp. Không cần vào Hồ sơ, không có trang nhập email hoặc OTP trong đăng nhập.

Google/GitHub dùng cùng tài khoản email/mật khẩu đã có khi email đủ tin cậy và không xung đột với danh tính khác. Không thay mật khẩu, tên hồ sơ hoặc quyền khi gắn phương thức đăng nhập. Hai danh tính đã thuộc hai tài khoản khác nhau sẽ bị chặn; không tự chuyển liên kết hoặc gộp dữ liệu nghiệp vụ.

Facebook mặc định chỉ xin `public_profile`. Quyền email Meta là tùy chọn sau khi được bật cho ứng dụng; không bắt buộc để xác nhận bằng Google. Email Facebook không được coi là bằng chứng sở hữu email Google. Resend/SMTP chỉ phục vụ quên mật khẩu.

## Kiểm thử local

- `tests/facebook-google-link.php`: Facebook → Google với ID token RSA có chữ ký kiểm tra được; state/nonce/PKCE mới; giữ Facebook proof ở server và giới hạn thời gian; hủy, đổi phiên, sai nonce, hết hạn trước/trong lúc gọi provider, lỗi mạng và replay đều không gắn danh tính. Cấu hình email bị tắt trong bài thử này.
- `tests/oauth-unified-accounts.php`: trên MySQL cách ly, cả mật khẩu/Google/GitHub/Facebook trả cùng user ID; dữ liệu hồ sơ/mật khẩu/quyền giữ nguyên; tài khoản khóa không vượt guard; xung đột rollback cả user, OAuth identity và audit.
- Hai process MySQL đồng thời: Google/GitHub cùng email chỉ tạo một user; hai tài khoản Google tranh cùng Facebook ID chỉ có một chủ sở hữu và không để lại user dở dang.
- Bộ regression hiện có: auth-complete, phase4, OAuth, One Tap, session, router, provider, request-token, route assets và JavaScript.
- HTTP local: trang chủ 200; URL khởi tạo đúng nhà cung cấp và callback; Facebook không xin email mặc định; API OTP OAuth đã bị gỡ; callback giả bị từ chối trước khi gọi provider.

Bằng chứng riêng local: `.runtime/facebook-google-20261109/`. Không dùng dữ liệu thử trong database đang chạy của người dùng, không gửi email, không yêu cầu API key mới và không sửa schema.

## Còn cần người dùng nghiệm thu

Meta hiện ở Development theo xác nhận của chủ ứng dụng. Tài khoản ngoài các vai trò thử nghiệm được Meta cho phép có thể bị chặn ngay ở Facebook; PHP chưa nhận callback trong trường hợp này. Không xem kết quả kiểm thử giao thức và MySQL là bằng chứng Facebook đã phát hành công khai.

Với tài khoản thử nghiệm hợp lệ: chọn Facebook chưa liên kết → chọn đúng Google chính → trở về CouponHub → đăng xuất → chọn lại Facebook và kiểm tra vào thẳng cùng tài khoản. Có thể kiểm tra nhãn Google/Facebook trong mục Tài khoản liên kết. Không cần cấu hình Resend cho bài thử.

Chưa xác nhận hoàn tất luồng thật bằng trình duyệt của người dùng hoặc trên production HTTPS. Chưa push/deploy các thay đổi trong lượt này.

Tham khảo: [Google ID token verification](https://developers.google.com/identity/gsi/web/guides/verify-google-id-token), [Account linking](https://firebase.google.com/docs/auth/web/account-linking).

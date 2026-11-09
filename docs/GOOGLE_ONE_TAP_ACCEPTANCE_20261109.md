# Nghiệm thu Google One Tap — 09/11/2026

> Cập nhật sau nghiệm thu ban đầu: Google đã được điền cấu hình local; nội dung “chưa có Client ID” bên dưới là trạng thái ở lần thử đầu. Hiện SDK chỉ tải sau khi đồng ý cookie tùy chọn. Cách đồng bộ tài khoản và Facebook xác nhận bằng Google được mô tả tại [FACEBOOK_GOOGLE_ACCEPTANCE_20261109.md](FACEBOOK_GOOGLE_ACCEPTANCE_20261109.md); không dùng OTP email cho OAuth.

Yêu cầu: nhấn Đăng nhập mở dialog CouponHub, đồng thời gọi hộp chọn tài khoản Google
như bản tham khảo `toan_cao_cap_ueh_for_ace/frontend/src/components/Navbar.jsx`.
Không tự mở khi người dùng chỉ truy cập trang chủ. Google/FedCM quản lý hộp tài khoản thật;
không dựng bản sao giao diện hoặc dùng tài khoản giả để chứng minh đăng nhập.

## Phần đã làm

- Tải SDK chính thức khi mở login, khởi tạo một lần mỗi trang, không tự chọn tài khoản.
- Đóng dialog/chuyển sang đăng ký hoặc quên mật khẩu hủy prompt, kể cả khi chuẩn bị còn đang chờ.
- POST JSON response qua hai endpoint One Tap; credential gửi trong body có CSRF, không ghi token vào URL/storage/log.
- Nonce/state có hạn 10 phút, gắn PHP session, dùng một lần, số intent tối đa 5.
- Kiểm tra chữ ký RS256, issuer, audience, expiry, nonce và verified email trước khi gọi repository OAuth hiện có.
- Dùng cùng session, phân quyền và chuyển hướng sau đăng nhập; chỉ tự liên kết email khi có bằng chứng sở hữu đủ tin cậy, không tự gộp dữ liệu của hai tài khoản đang tồn tại.
- Không thay database; bộ SQL bàn giao vẫn dùng được. Không lấy API key từ ứng dụng tham khảo.

## Đã kiểm chứng local

- `php tests/google-one-tap.php`: 23 kiểm tra giao thức pass với khóa RSA sinh tại chỗ/cert transport được kiểm soát;
  gồm token hợp lệ, token sửa chữ ký, sai nonce/audience/issuer/expiry/email_verified,
  hết hạn phiên, phiên khác, phát lại và giới hạn intent. Không tạo dữ liệu giả trong DB thật.
- 17 nhóm PHP regression không dùng database pass, gồm OAuth Google/GitHub/Facebook, session/CSRF,
  router, rate limit, phân quyền và provider contracts.
- Regression JavaScript gồm luồng mở/hủy/đóng trong lúc đang chờ, giữ CSRF/return_url,
  chặn callback lặp và chuyển hướng ngoài origin.
- HTTP thực tại `http://localhost:8000`: GET endpoint POST trả 405, thiếu CSRF trả 403,
  origin ngoài trả 403; chưa có Client ID trả 503 cho cả prepare/credential endpoint.

Bằng chứng local: `.runtime/db-upgrade-20261109/one-tap-live-guards.json`,
`php-one-tap-regression.json` và `node-tests-with-one-tap.log`.

## Còn cần cấu hình và nghiệm thu thật

Local hiện chưa có Google Client ID, nên One Tap thật chưa xuất hiện. Thêm vào `.env`
`COUPONHUB_GOOGLE_CLIENT_ID`, giữ `COUPONHUB_GOOGLE_ONE_TAP=1`, khai báo origin local
`http://localhost:8000` trong Google Cloud. Giữ `COUPONHUB_DB=dbcouponshubweb_v3`.
Hướng dẫn và tài liệu Google: [PROVIDER_SETUP.md](PROVIDER_SETUP.md#google-one-tap-trong-popup-đăng-nhập).

Sau khi có cấu hình, kiểm tra hộp chọn trên Chrome, chọn tài khoản Google thật,
tạo/liên kết đúng tài khoản trong `oauth_accounts`, thử đăng nhập lại, đóng prompt,
đăng xuất, tài khoản bị khóa và bản HTTPS. Vị trí/việc hiện prompt còn phụ thuộc
Google, FedCM và lựa chọn đăng nhập liên kết trong trình duyệt.

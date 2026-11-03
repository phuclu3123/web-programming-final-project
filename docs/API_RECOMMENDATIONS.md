# API phù hợp với CouponHub

Tra cứu tài liệu chính chủ ngày 04/11/2026. Đây là đề xuất tích hợp tiếp theo, **chưa bật API mới hoặc nhập dữ liệu từ nhà cung cấp**. Thứ tự dưới đây là đánh giá cho nghiệp vụ và code PHP hiện tại.

## 1. ACCESSTRADE Publisher — ưu tiên về nội dung

Có API danh sách nhà cung cấp, ngành hàng và coupon, cùng thông tin thời gian, banner, điều kiện/giá trị giảm và liên kết. Endpoint coupon được tài liệu công bố: `GET https://api.accesstrade.vn/v1/offers_informations/coupon`. [Tài liệu chính chủ](https://developers.accesstrade.vn/api-publisher-vietnamese/lay-thong-tin-vouchers-coupons-deals/tim-kiem-danh-sach-thong-tin-khuyen-mai).

**Đề xuất cho CouponHub:** nhập thử một nguồn ưu đãi thực vào màn Admin xem trước; Admin ánh xạ merchant/category và xác nhận điều kiện trước khi duyệt. Cần tài khoản Publisher/quyền API; xác nhận credential, quota và quyền sử dụng dữ liệu trực tiếp với nhà cung cấp trước khi chạy. Không khẳng định nguồn luôn có đủ Shopee/Lazada hoặc mọi mã còn dùng được chỉ từ ví dụ tài liệu.

Phần import nên dùng một PHP CLI chạy theo lịch, prepared SQL và transaction hiện có. Giữ ID nguồn để chống nhập lặp; không dùng mã coupon làm ID nguồn duy nhất. Không đổi mã đã thu hồi thành ENABLED khi đồng bộ; không tự cấp ownership/nhãn “chính thức” cho thành viên. HTML mô tả bên ngoài phải được xử lý an toàn, URL/banner được validate; nguồn lỗi vẫn giữ dữ liệu đã duyệt và hiển thị thời điểm cập nhật. Đây là phương án thiết kế của dự án, không phải yêu cầu kỹ thuật của API.

## 2. Cloudflare Turnstile — ưu tiên chống bot

Phù hợp bảo vệ form đăng ký, quên mật khẩu và gửi coupon. Widget tạo token, PHP phải xác minh tại `POST https://challenges.cloudflare.com/turnstile/v0/siteverify`; token dùng một lần và hết hạn sau 5 phút. Tài liệu có ví dụ PHP cURL. [Hướng dẫn xác minh server](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/).

**Đề xuất:** thêm một service nhỏ dùng HttpClient hiện có, sitekey public/secret trong `.env`, kiểm tra success + hostname + action. Giữ CSRF/rate limit đang có. Dùng test key trong kiểm thử; khi provider lỗi, giữ nội dung form và báo thử lại, không âm thầm coi token hợp lệ. Chưa cấu hình key thì không gắn widget nửa vời hoặc làm hỏng luồng hiện tại.

## 3. Google Safe Browsing — kiểm tra link coupon cộng đồng

API đối chiếu URL với danh sách mối đe dọa đã biết. Phù hợp bổ sung tín hiệu khi Admin xét link ưu đãi do cộng đồng gửi. Google quy định Safe Browsing dành cho mục đích phi thương mại; khi vận hành có doanh thu cần xem Web Risk. [Tài liệu và giới hạn sử dụng](https://developers.google.com/safe-browsing/reference/rest).

**Đề xuất:** kiểm tra URL công khai trước khi duyệt; không gửi URL chứa token reset, OAuth code hoặc thông tin riêng tư. Không tự tải URL người dùng đưa vào, tránh biến chức năng này thành SSRF. Ghi nhận “phát hiện nguy cơ / chưa phát hiện / chưa kiểm tra”, không ghi “an toàn tuyệt đối”. Provider lỗi thì giữ chờ kiểm tra, không tự duyệt hay tự thu hồi coupon. Chỉ nên làm sau ACCESSTRADE/Turnstile nếu còn thời gian.

## Lựa chọn thực tế

Nếu chỉ thêm một API để tăng giá trị đề tài, chọn **ACCESSTRADE**: bổ sung nguồn coupon thật thay cho phụ thuộc hoàn toàn demo. Nếu ưu tiên bảo vệ bản public trước, chọn **Turnstile**. Safe Browsing là phần bổ sung sau; không cần làm đồng thời cả ba.

Resend đã có trong dự án để gửi OTP, nên chỉ cần cấu hình sender và nghiệm thu gửi thật; không thêm một nhà cung cấp email thứ hai nếu không có nhu cầu cụ thể. OAuth GitHub/Google/Facebook và Resend chưa được coi là đã nghiệm thu thực tế chỉ vì test giả lập qua.

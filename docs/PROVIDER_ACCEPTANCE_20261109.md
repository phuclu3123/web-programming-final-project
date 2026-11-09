# Kiểm định provider thật ngày 09/11/2026

**Cập nhật mới:** theo yêu cầu tiếp theo của người dùng, đã lấy được 50 mục thật và lưu vào hàng chờ database, kèm ảnh nguồn; chưa xuất bản. Xem [kết quả nhập nguồn](ACCESSTRADE_IMPORT_20261109.md). Các mô tả “chưa ghi database” bên dưới thuộc những lượt kiểm tra trước khi có yêu cầu nhập.

## Phạm vi

Kiểm tra key từ `.env` bằng các request HTTPS thật. Không ghi dữ liệu ACCESSTRADE vào database, không thay đổi mật khẩu hoặc cấp mã khôi phục thật. Không lưu key hay token Turnstile vào tài liệu này.

## Cập nhật sau khi đối chiếu tài liệu Publisher tiếng Việt

Kết quả dưới đây của lượt kiểm tra đầu tiên không phải trạng thái cuối cùng. Lượt sau có request lấy được dữ liệu nhưng lỗi xác thực vẫn tái diễn. Chi tiết tại [đối chiếu tài liệu ACCESSTRADE](ACCESSTRADE_DOCUMENT_AUDIT_20261109.md). Người dùng xác nhận không đổi key trong lúc kiểm tra. Chưa nghiệm thu nguồn ổn định.

Người dùng cũng đã cung cấp ảnh Turnstile `Success!` trên trình duyệt và xác nhận luồng quên mật khẩu hoạt động. Đây là bằng chứng do người dùng cung cấp, bổ sung cho kiểm tra phía server bên dưới; chưa thay thế kiểm tra replay/hết hạn trên trình duyệt.

## ACCESSTRADE: lượt kiểm tra đầu tiên chưa đạt

- GET offers, coupons và merchants bằng `AccesstradeService`: cả ba trả HTTP 403.
- GET chẩn đoán `https://api.accesstrade.vn/v1/offers_informations?limit=1&page=1&status=1`: HTTP 403, JSON `Invalid token: [REDACTED]`.
- TLS/cURL hoàn tất, không có lỗi transport. Header dùng `Authorization: Token ...` như tài liệu nhà cung cấp.
- Key cấu hình khớp chính xác giá trị trong `.env`; chỉ có một định nghĩa, không có biến tiến trình ghi đè, khoảng trắng, dấu ba chấm hoặc tiền tố Authorization thừa.
- Chưa xác định được lý do nhà cung cấp không chấp nhận khóa đang hiển thị trên dashboard. Cần đối chiếu API Access Key trong Trợ giúp → APIs với tài khoản Publisher hoặc hỏi ACCESSTRADE. Không kết luận thiếu quyền chỉ dựa vào HTTP 403.

## Turnstile: đạt kiểm tra phía server, còn thiếu luồng thành công qua trình duyệt

- Gọi Siteverify với secret thật và token sai: HTTP 200, `success=false`, `invalid-input-response`.
- HTTP local `http://localhost:8000`: site key hiện tại có trong form, secret không xuất hiện trong HTML.
- POST quên mật khẩu với CSRF hợp lệ nhưng thiếu token trả 422; token sai trả 403 trước khi gửi email.
- `.env`, SQL và bản HTML xem trước trong `.runtime` đều bị router chặn.
- 9 kiểm tra HTTP đạt. Chưa kiểm tra token hợp lệ, replay hoặc hết hạn bằng trình duyệt thật vì browser access host/native pipe không kết nối. Không coi cấu hình đã đủ hoặc việc chặn token sai là bằng chứng cho toàn bộ luồng thành công.

## Resend và email khôi phục

- Thêm `PasswordResetEmail` tạo HTML dạng bảng, style nội tuyến, thương hiệu CouponHub, mã sáu số và hướng dẫn ngắn gọn. Không dùng ảnh ngoài, script hay tracking pixel.
- Giữ bản text; SMTP dùng chung nội dung text, Resend gửi cả HTML và text.
- Mã OTP hết hạn sau 10 phút; luồng link cũ giữ 30 phút. Không sửa logic cấp/tiêu thụ token hoặc dữ liệu tài khoản.
- 21 kiểm tra email đạt: giữ số 0 đầu, escape URL trong HTML, chặn scheme không an toàn và mã sai định dạng. 25 kiểm tra giao thức provider hiện có tiếp tục đạt. Cú pháp ba file service đạt.
- Resend chấp nhận một thư xem trước đến địa chỉ người dùng chỉ định. Thư ghi rõ là bản xem trước, dùng dấu chấm thay mã, không cấp mã có hiệu lực. Có idempotency key cố định để tránh gửi lặp khi chạy lại.
- API chấp nhận chưa chứng minh thư đã vào inbox hoặc hiển thị giống nhau trên mọi mail client. Người dùng xác nhận mail trước đó đã nhận; bản HTML mới cần xem lại trên mail client thực tế.
- Các lần yêu cầu quên mật khẩu tiếp theo qua app sẽ dùng mẫu mới.

## Tài liệu nhà cung cấp

- [ACCESSTRADE Authentication](https://developers.accesstrade.vn/api-accesstrade-tai-lieu-tich-hop/authentication)
- [ACCESSTRADE: vị trí API Access Key trên dashboard](https://help.accesstrade.vn/knowledgebase/huong-dan-su-dung-api-cua-accesstrade-viet-nam/)
- [Cloudflare Siteverify](https://developers.cloudflare.com/turnstile/get-started/server-side-validation/)
- [Resend Send Email](https://resend.com/docs/api-reference/emails/send-email)

Chưa push hoặc deploy các thay đổi trong đợt kiểm tra này.

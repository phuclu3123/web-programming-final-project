# Lưu thử nguồn ACCESSTRADE ngày 09/11/2026

## Kết quả đã thực hiện

- Lấy trang đầu qua `AccesstradeService::fetch('offers', 1)` bằng key thật trong `.env`. Hai lần đầu 403, lần thứ ba HTTP 200.
- Nhận 50 mục; chuẩn hóa 50 mục, không bỏ qua mục nào. Không sử dụng fixture, dữ liệu demo hoặc tự tạo mã.
- 50 mục đều có mã, URL ảnh và thời điểm kết thúc. Theo ngày nguồn trả về tại thời điểm lấy, cả 50 còn trong khoảng hiệu lực; điều này không xác nhận mã áp dụng được khi thanh toán.
- Nguồn trả các trường cơ bản, không có các trường mức giảm có cấu trúc. Không suy diễn mức giảm, đơn tối thiểu hoặc trần giảm từ tên quảng cáo. Tất cả cần đối chiếu trước khi tạo coupon.
- Đã lưu qua `ExternalOfferRepository::stage()` vào `dbcouponshubweb_v3.external_offers` dưới Admin hiện có `id=1` (`admin_demo`); có audit `EXTERNAL_SYNC` do thao tác CLI được người dùng yêu cầu.
- Hàng chờ từ 0 lên 50, ID 1–50, trạng thái `PENDING`, `coupon_id=NULL`. Đọc lại cả 50 payload và fingerprint: khớp bản lấy từ API.
- Bảng `coupons` giữ 40 bản ghi. So sánh checksum toàn bộ bản ghi trước/sau: không thay đổi. Chưa xuất bản coupon mới, không sửa quyết định kiểm duyệt cũ.

## Hình ảnh và giao diện

- Danh sách Admin có thumbnail 72×72, ngày kết thúc và nhãn cần đối chiếu mức giảm.
- Trang đối chiếu có ảnh nguồn 152×152. URL ảnh được kiểm tra bằng `safeHttpUrl`, escape khi render, không gửi referrer.
- Ba ảnh mẫu tải trực tiếp từ CDN nguồn: HTTP 200, JPEG hợp lệ, kích thước 300×300, 225×225 và 1080×1080.
- Render bằng bản ghi thật: trang 1 có 20 dòng/20 ảnh, trang 2 có 20/20, trang 3 có 10/10; trang chi tiết có ảnh. Chưa xác nhận hiển thị trong trình duyệt của người dùng vì browser access host/native pipe không kết nối.
- Kiểm tra cú pháp hai view đạt; 25 kiểm tra hợp đồng provider đạt. Không có test JavaScript `integrations*.test.cjs` khớp tên, nên không coi lượt chạy đó là kiểm thử JS đã đạt.

Mở app sau khi đăng nhập Admin: `/admin/integrations`, chọn **Xem nguồn** để đối chiếu từng mục. Dữ liệu này chưa nằm trong danh sách coupon công khai.

## Bản sao lưu và bằng chứng

Thư mục riêng **ngoài webroot**: `../CouponHub_ACCESSTRADE_20261109/`.

- `before-import-20261109_124735.sql`: backup đầy đủ database trước khi nhập, có routine/trigger/event; dump kết thúc thành công trước khi ghi DB.
- SHA-256 backup: `99bdfa01a188cf5b4e0807fe0223d16533c397d798288e0f93a44428a3486229`.
- `provider-response.json`, `normalized.json`: bản phản hồi/chuẩn hóa thật dùng trong lần nhập này, không chứa API key.
- `fetch-report.json`, `import-report.json`, `image-checks.json`, `ui-verification.json`: kết quả kiểm tra.
- `Nguon_coupon_da_luu.html`: bản chỉ đọc từ các dòng bảng đã render bằng dữ liệu DB thật; không chứa form, CSRF hoặc API key.
- Không đưa backup, payload nguồn hay cấu hình bí mật lên GitHub. Không khôi phục toàn bộ backup lên DB đang có cập nhật mới nếu chưa đối chiếu.

Các lần gọi API tiếp theo vẫn có thể gặp 403. Việc lưu thành công một snapshot không đồng nghĩa nguồn đã ổn định.

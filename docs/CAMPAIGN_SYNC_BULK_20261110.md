# Đồng bộ chiến dịch và duyệt nhiều

## Sử dụng

- Admin → Nguồn ACCESSTRADE → **Chiến dịch** (`/admin/integrations/campaigns`). Bấm **Đồng bộ chiến dịch**; mỗi trang nguồn có tối đa 20 mục, một lượt chọn tối đa 5 trang / 100 mục.
- Chọn checkbox hoặc **Chọn tất cả trang này**, chọn **Duyệt hiển thị** / **Bỏ qua**, rồi áp dụng. Mỗi trang hàng chờ hiển thị tối đa 50 mục. Kết quả thành công/thất bại hiển thị riêng từng dòng; tải lại để lấy trạng thái mới.
- Chiến dịch được duyệt xuất hiện ở mục **Chương trình từ đối tác** trên `/campaigns`, với liên kết HTTPS tới đối tác. Mục này có phân trang riêng; các bộ lọc shop/danh mục/kênh dành cho chiến dịch nội bộ không áp dụng cho nguồn đối tác chưa được ánh xạ.
- Admin → **Kiểm duyệt coupon** có chọn nhiều coupon PENDING để duyệt/từ chối. Lý do từ chối chung cần 10–1.000 ký tự; các chức năng sửa/thu hồi từng coupon vẫn dùng luồng hiện tại.

## Database và hosting

Backup DB đang dùng rồi áp dụng **`database/migrations/20261110_external_campaigns.sql`**. Bản 21 bảng, kể cả SQL bàn giao tại commit `601e0b1`, cần migration này. Sau nâng cấp có **22 bảng, 6 view, 21 procedure**; bảng mới `external_campaigns` lưu nguồn, phiên bản và quyết định duyệt. Không import lại schema/demo vào DB đang có dữ liệu.

Không cần key mới: tiếp tục dùng `ACCESSTRADE_ACCESS_KEY` phía server trong `.env`. Không thêm secret vào JavaScript. Script/schema baseline cài mới đã có bảng mới; database đang hoạt động chỉ dùng migration.

## Hợp đồng và kiểm soát

- Đọc `GET https://api.accesstrade.vn/v1/campaigns?limit=20&page=N`, header `Authorization: Token <access_key>`. Không gọi `/v1/cashback/campaigns` (NEW).
- Các trang nguồn phải lấy xong trước khi ghi hàng chờ; lỗi mạng không tạo một nửa đợt đồng bộ. Tên/ảnh/URL/ngày lấy từ nguồn; ngày kết thúc null được hiển thị là chưa công bố, không tự đặt ngày hết hạn. Hoa hồng publisher không được diễn giải thành mức giảm coupon.
- Đây là chương trình đối tác, không tự tạo tài khoản shop, coupon Official hoặc gắn chủ sở hữu giả. `approval=unregistered` của ACCESSTRADE là trạng thái publisher tham gia chương trình, độc lập với việc admin duyệt hiển thị thông tin.
- POST đều qua Admin guard, CSRF, giới hạn request và kiểm tra input phẳng. `selection` là chuỗi JSON gồm `{id, fingerprint}`; giới hạn 100 mục, không chấp nhận ID trùng hoặc kiểu dữ liệu mơ hồ.
- Mỗi mục có transaction và nhật ký riêng. Mục hợp lệ vẫn được xử lý nếu mục khác lỗi; không báo cả lô thành công khi còn lỗi. Bản ghi thay đổi nội dung từ lúc chọn phải xem lại; không tự phục hồi mục bỏ qua/thu hồi. Nội dung nguồn đã đổi sau duyệt bị ẩn công khai cho đến khi duyệt lại.
- Duyệt coupon hàng loạt giữ kiểm tra mã trùng, trạng thái PENDING, nội dung đã chọn, khóa merchant → coupon, nhật ký và cộng điểm qua procedure hiện tại. Gửi lặp không cộng điểm lại.

Các test và ảnh kiểm chứng được lưu ngoài repository trong `CouponHub_DevTools`; không đưa lại Python/test fixtures vào bản web. Kiểm chứng local không thay cho quyền API và nghiệm thu HTTPS trên hosting.

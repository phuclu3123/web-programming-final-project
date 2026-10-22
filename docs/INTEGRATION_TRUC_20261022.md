# Merge nhánh Trúc — 22/10/2026

Nguồn: `origin/Truc` tại `7f740b8` (ba commit UI và lọc chiến dịch sau bản đã tích hợp trước). Đã merge thật vào main, giữ bản sửa đăng nhập `7ef4e65`. Main trên remote đã bị đưa về lịch sử của Trúc; bản tích hợp nối lại cả hai lịch sử, không force-push và không bỏ cập nhật của nhóm. Chưa merge nhánh Quang trong đợt này.

## Giao diện được giữ và thống nhất

- Giữ các chỉnh sửa giao diện Trúc ở cộng đồng, danh mục, coupon đã gửi, danh sách/chi tiết/lịch chiến dịch và CSS liên quan.
- Header chỉ có một nút **Đăng nhập**, mở popup trên trang đang xem. Đăng ký nằm trong popup, dùng cùng AuthService, CSRF và form thật. Các URL login/register trực tiếp mở cùng popup trên trang khám phá.
- Popup dùng native dialog để có focus trap, Escape, đóng và trả focus; không phụ thuộc hai phiên bản Bootstrap đang tồn tại trong các layout. API lấy từ action của form nên không bị sai URL trên trang chi tiết nhiều cấp.
- Giữ chuông thông báo theo UI Trúc, thay ba thông báo viết cứng bằng dữ liệu kiểm duyệt/yêu cầu shop/báo cáo thật theo actor. Thêm trang `/notifications` và POST `/notifications/read`; kiểm tra đăng nhập và CSRF ở server. Không nhận user_id hay role từ client.
- Trạng thái đã đọc lưu **trong phiên đăng nhập**, giữ khi chuyển trang/tải lại nhưng không đồng bộ giữa thiết bị hoặc sau đăng xuất. Chưa thêm bảng notification, email/push hay thông báo thời gian thực. Đây là giới hạn có chủ ý của đợt ghép, không giả lập dữ liệu thật.

## Phần backend Trúc có thay đổi

Trúc bổ sung truy vấn danh sách chiến dịch theo danh mục/kênh, tham số lọc coupon trong chiến dịch, danh mục đang bật cho dropdown và `reviewed_at` cho danh sách mã đã gửi. Các thay đổi dùng mysqli/prepared statement, không có migration mới và không sửa AuthService.

Các lỗi đã sửa sau merge:

1. Form thời gian gửi `status=RUNNING` nhưng controller chỉ đọc `phase=ACTIVE`; chuẩn hóa về hợp đồng phase hiện có. Bỏ lựa chọn kết thúc vì truy vấn công khai chỉ dành cho chiến dịch chưa kết thúc.
2. Preview chưa truyền category/channel dù danh sách đã lọc; truyền cùng bộ lọc và đếm đúng số coupon phù hợp. Form thay bộ lọc trở về trang 1, không giữ preview cũ làm phát sinh 404.
3. Khôi phục phân trang chiến dịch và khai báo đầy đủ interface repository.
4. Form lọc chi tiết bị xóa trong khi JavaScript vẫn tìm nó; khôi phục form và kiểm tra lọc AJAX, chống phản hồi cũ ghi đè kết quả mới, cập nhật count/CTA, thông báo lỗi tại chỗ. Không JS vẫn có GET form.
5. Lịch dùng `start_at/end_at` trong khi DB trả `starts_at/ends_at`; sửa tên cột và giờ UTC → Việt Nam. Tháng/năm được validate ở controller và lọc trong SQL theo khoảng giao nhau, gồm chiến dịch kéo dài qua tháng; không giới hạn im lặng ở 24 chiến dịch đầu.
6. Sửa typo `campaign-bentSo-side` gây vỡ bố cục chiến dịch trang chủ.
7. Truy vấn coupon chiến dịch kiểm tra cả trạng thái công khai của chiến dịch và shop chủ quản; campaign draft/hết hạn/shop khóa không trả coupon. Kênh BOTH vẫn có mặt khi chọn ONLINE hoặc OFFLINE.
8. Thông báo mẫu và “Xem tất cả” dẫn tới route chưa tồn tại đã được thay bằng luồng thật. Đánh dấu đã đọc chỉ đổi giao diện sau khi server xác nhận.

Không đổi schema/procedure, không seed hay sửa dữ liệu đang dùng. Bộ SQL bàn giao trước vẫn dùng được; các kiểm tra có ghi chạy trên MariaDB riêng ở cổng 13379.

## Nghiệm thu

- PHP lint toàn bộ source đang dùng; JS syntax, Auth UI, session/role/CSRF, Router và đăng ký.
- `tests/truc-merge.php`: filter/count/phân trang campaign, kênh BOTH, draft/hết hạn/shop khóa, ranh giới tháng Việt Nam và thông báo/read receipt theo actor; transaction được rollback.
- `tests/truc-integration.php`: quy trình đóng góp/kiểm duyệt, favorites và quyền shop hiện có.
- `tests/login-redirects.py`: HTTP tại document root và `/CouponHub_UI`; một popup, không nút đăng ký riêng, các trang nội dung, cả 6 demo login, đích Admin/Merchant, notification page, CSRF và giữ trạng thái đã đọc.
- Trình duyệt: mở/đóng popup, chuyển đăng ký trong popup, đăng nhập vào Admin, chuông/thông báo thật và đánh dấu đã đọc, lọc coupon chiến dịch bằng AJAX. Kiểm tra không tràn chiều ngang tại viewport 390px.

Lệnh kiểm tra ghi DB chỉ chạy với `COUPONHUB_DB=couponhub_merge_test`, `COUPONHUB_PORT=13379`; cấu hình local không được ghi đè sang database thật. Trạng thái demo cần được khởi tạo sạch trước khi chạy các kiểm tra có assertion số lượng cố định.

# Nghiệm thu đợt 3 — 31/10/2026

## Kết luận và phạm vi

**21/21 mục P0 đợt 3 đạt trong phạm vi code review và kiểm thử local dưới đây. Đủ điều kiện bắt đầu phát triển đợt 4.** Đây không phải xác nhận production, thử tải hay xác nhận tài khoản OAuth/Resend thật.

Danh sách đầu việc tham khảo từ `CouponHub_PhanCong_ChiTiet_4Dot_TheoVaiTro.xlsm`: sheet `02 - Trúc USER`, `03 - Phúc ADMIN`, `04 - Quang SHOP DB`, các dòng U3/A3/M3. Không sửa workbook, chạy macro hoặc lấy trạng thái tick trong bảng làm bằng chứng; không dùng thuật toán/kỹ thuật gợi ý trong bảng để quyết định code.

Giữ cách PHP trong bài giảng: form POST/redirect, `session_start`/`$_SESSION`, session và cookie ở Lesson 7; tiến trình đăng nhập ở Lesson 11; kiểm tra quyền ở Lesson 13; tổ chức class/OOP ở Lesson 15. Tiếp tục dùng mysqli, tham số truy vấn và transaction của dự án; không thêm framework, dịch vụ Node hoặc thư viện mới.

Nguồn Git đã fetch/review:

- Trúc: `fa1501f35bc670a3c6061eb44e0b888a5639c260` — lọc nền tảng và banner campaign.
- Quang: `f1a8a28` — hai commit mới sau nền `df119f8`, gồm tracking, gợi ý coupon, tiến độ điểm, các module mở rộng và định dạng source.
- Nền ứng dụng trước đợt này: `df119f8`. Tích hợp Trúc trước, sau đó Quang; chạy kiểm thử chung sau khi ghép cả hai.

## Danh sách nghiệm thu P0

| Mục | Kết quả đã xác nhận |
|---|---|
| U3.01 Danh sách campaign | Đang/sắp diễn ra, lọc và phân trang; loại DRAFT/DISABLED, hết hạn, owner shop khóa/xóa |
| U3.02 Chi tiết campaign | Banner tùy chọn, mô tả, giờ Việt Nam; coupon qua pivot, không sao chép bản ghi |
| U3.03 Lọc trong campaign | Danh mục + nền tảng/shop + kênh cùng khớp một coupon ACTIVE; count/preview/detail nhất quán |
| U3.04 Campaign hết hạn | Trang chi tiết có trạng thái kết thúc, không cung cấp mã trong campaign; coupon còn hạn vẫn xuất hiện ngoài campaign |
| U3.05 Yêu thích | Lưu/bỏ lưu theo actor, cặp user/coupon duy nhất, giữ trạng thái qua request |
| U3.06 Bình chọn | Một vote hiện hành, đổi WORKING/NOT_WORKING không tạo vote thứ hai |
| U3.07 Báo mã sai | Báo cáo vào hàng OPEN, không tự thu hồi hoặc xóa coupon |
| A3.01 Thu hồi coupon | Action riêng, lý do 10–500 ký tự, actor/time/note và lịch sử; chặn public ngay |
| A3.02 Khóa merchant | Bắt buộc lý do, kiểm tra Admin ở tầng ghi, audit; phiên Merchant cũ không vượt được trạng thái shop |
| A3.03 Mở khóa shop | Có lý do/audit riêng; không hồi sinh coupon đã thu hồi |
| A3.04 Xử lý report | RESOLVED/DISMISSED có actor/time/note; không xử lý lặp, không tự xóa coupon |
| A3.05 Tạo campaign | Admin tạo campaign nền tảng với tên, mô tả, banner, thời gian và trạng thái; thay trạng thái được kiểm tra quyền/audit |
| A3.06 Gắn/gỡ coupon | Nhiều campaign dùng chung một coupon; gỡ chỉ xóa quan hệ; không nhân bản coupon |
| A3.07 Hết hạn campaign | Không ghi vào `ends_at` hay publication của coupon |
| M3.01 Effective status | Dùng view chung; OFFICIAL của shop chưa APPROVED không thành ACTIVE; các nguồn tìm kiếm/chi tiết/campaign cùng tuân theo |
| M3.02 Chặn write khi shop khóa | Coupon, campaign, hồ sơ kiểm tra trạng thái và quyền ở server; kiểm tra HTTP với phiên đã đăng nhập trước khi khóa |
| M3.03 Chống hồi sinh mã thu hồi | Chặn sửa Merchant, nút disable, sửa Admin và gọi lại duyệt; giữ dữ liệu/lịch sử |
| M3.04 Hạn campaign độc lập | Coupon còn hạn không bị EXPIRED do campaign kết thúc |
| M3.05 Query User | Search/filter, hiệu lực, favorites/votes/report theo actor; chống lộ dữ liệu giữa người dùng |
| M3.06 Query Admin | Hàng chờ và ghi có phân quyền/CSRF; thu hồi, trạng thái shop/campaign và xử lý report |
| M3.07 Duplicate | Chuẩn hóa hoa/thường/khoảng trắng; chặn cùng shop và thời gian chồng nhau; cho phép shop khác và hai khoảng chỉ tiếp giáp |

P1 không dùng làm điều kiện chặn đợt 4: khóa User đã có trong nền; campaign Merchant cũng đã có và được kiểm tra tạo/sửa/banner, ownership và không gắn coupon shop khác.

## Những lỗi đã sửa khi tích hợp

- Trúc dùng `UPDATE` thay vì `INSERT` khi tạo campaign, số tham số không khớp; banner sửa không được ghi xuống DB.
- Truy vấn cột `platform` không tồn tại, đổi vị trí tham số làm hỏng caller cũ, truyền tham số lọc nhưng hàm chi tiết không dùng. Nay dùng `merchant_id` của schema chung, options lấy từ DB; không thêm bảng/enum nền tảng giả.
- Bỏ mất ảnh mặc định khi banner trống; thiếu validate đường dẫn; preview/tab/phân trang và nút reset mất bộ lọc nền tảng. Nay chặn scheme/path không hợp lệ, giữ base URL thư mục con; ảnh tải lỗi có fallback một lần.
- Merchant edit hoặc disable có thể ghi đè trạng thái ARCHIVED/EXPIRED. Đã khóa các đường ghi này; stored procedure duyệt cũng từ chối trạng thái terminal.
- Bổ sung action thu hồi riêng, kiểm tra quyền ở tầng ghi trạng thái shop/campaign, lý do và audit khóa/mở shop, giữ quyền/trạng thái trong transaction. Sửa hồ sơ shop không còn khoảng trống giữa kiểm tra quyền và UPDATE.
- Chi tiết campaign hết hạn trước đây chỉ trả 404; nay cho phép đọc trạng thái kết thúc khi yêu cầu rõ ràng, đồng thời giữ danh sách/preview mặc định loại campaign hết hạn.
- Giữ bản sửa Trúc khi giải quyết xung đột định dạng view từ Quang. Sửa một assertion HTML của Quang để kiểm tra text hiển thị sau chuẩn hóa whitespace; không bỏ kiểm tra giá trị hay escape HTML.

### Quy ước thu hồi trên schema hiện tại

Không thêm một mô hình trạng thái thứ hai. Thu hồi dùng `publication_status='ARCHIVED'`, **không** đặt `deleted_at` và **không** thay ngày hiệu lực. `reviewed_by/at/note`, `coupon_status_history` và activity `COUPON_REVOKE` ghi người xử lý, thời điểm và lý do. Xóa mềm vẫn là action riêng có `deleted_at`; hai thao tác phân biệt được trong dữ liệu/audit.

Không có đường Merchant bật lại trạng thái terminal. Không bổ sung chức năng khôi phục mã bị thu hồi trong đợt này; nếu cần sau này phải là hành động Admin độc lập, có quy tắc riêng.

## Phần Quang giữ và phần chưa đưa vào đợt 3

Giữ tracking VIEW/COPY/SHARE qua routine chung, throttle session, không nhận user ID từ client; lỗi analytics không làm trang chi tiết hợp lệ bị hỏng. Gợi ý liên quan/cá nhân dùng coupon ACTIVE và favorites của đúng actor; tiến độ cấp độ lấy mốc trong DB. Nội dung gợi ý và tín hiệu tương tác không được coi là xác nhận coupon đáng tin hay dùng được.

Chưa tích hợp các công cụ best-deal/matching/stack-checker, yêu cầu tìm mã và đổi thưởng từ nhánh Quang. Chúng không thuộc P0 đợt 3; hai module sau cần bảng mới chưa nằm trong schema bàn giao, và đổi thưởng còn liên quan cam kết phát quà thật. Đã bỏ các link tương ứng khỏi bản ghép để không tạo điều hướng hỏng. Source vẫn có trong lịch sử `origin/Quang`, không bị mất.

Không áp dụng thay đổi demo về tên cấp độ, mức điểm và ngưỡng thưởng theo brief của nhánh. Giữ chính sách dữ liệu đã có; bảng phân công chỉ làm danh sách công việc, không tự tạo chính sách nghiệp vụ mới. Các module chưa tích hợp không được báo là đã nghiệm thu.

## Bằng chứng kiểm thử

Lệnh chạy chung: `COUPONHUB_RUN_DB_TEST=1 python tests/run-phase3.py` khi MariaDB thử riêng đang chạy tại `127.0.0.1:13379`. Không chạy với DB thật. Runner kiểm tra port và từ chối `config/local.php`; từng nhóm dùng schema trống hoặc demo đúng yêu cầu.

- PHP syntax toàn bộ `app/` và `config/`; **28/28 JavaScript tests**.
- Unit/contract: auth, OAuth chữ ký/state, session/role, đăng ký, tài khoản, router/Admin, tracking, gợi ý coupon và tiến độ cấp độ.
- `phase3-integration.php`: campaign/banner, phối hợp bộ lọc, thu hồi, khóa/mở shop, actor, report, favorite/vote, duplicate theo shop/thời gian, pivot attach/detach và hết hạn độc lập.
- `phase3-http.py`: form thật và session/CSRF ở root và `/CouponHub_UI`, banner/default, filter, trạng thái hết hạn, thu hồi và shop khóa; crawl các trang công khai/tài khoản. Log hai server không có PHP Warning/Fatal/Parse.
- Schema/demo và rollback; áp migration trên schema cũ, chạy lại hai lần không đổi bản ghi User/coupon rồi kiểm thử giai đoạn 3 trên DB đã nâng cấp.
- Regression Trúc, Quang, mysqli, các trang Admin, đăng nhập sáu tài khoản ở hai base URL, đổi mật khẩu/thu hồi phiên, OTP với SMTP fixture và OAuth HTTP cancellation/replay.

Lượt chạy chung đã qua, log cục bộ tại `.runtime/phase3-review-1793455480803809200/`. Các ca biên duplicate và attach/report bổ sung sau đó cũng đã qua trên fixture sạch. Không dùng kết quả test giả lập OAuth để khẳng định đã đăng nhập nhà cung cấp thật.

## Trước khi chạy bản mới trên máy nhóm

**Máy đã có dữ liệu cần backup rồi import riêng [20261031_phase3_risk_controls.sql](../database/migrations/20261031_phase3_risk_controls.sql) vào đúng database.** Script thay view hiệu lực và procedure kiểm duyệt; không seed, xóa bảng hay sửa tài khoản/coupon. Chưa tự áp dụng vào database đang dùng trên máy này.

Máy cài mới dùng `schema.sql` mới rồi `demo.sql` nếu cần. Không import lại schema hoặc demo lên DB đã có dữ liệu. Không cần các migration phase8/phase10 của nhánh Quang cho bản tích hợp này.

Đợt 4 tiếp tục OAuth/email thật, cấu hình triển khai, rà UI/responsive và hoàn thiện theo danh sách của nhóm. Các kiểm thử hiện tại chạy trên PHP 8.1/MariaDB 10.4.32 local; chưa nghiệm thu mọi trình duyệt/thiết bị, tải đồng thời hay môi trường production.

# Nghiệm thu chung đợt 4 — 04/11/2026

## Nguồn và phạm vi

Ở lượt 04/11 trước đó, đã fetch lại `Truc`, `main`, `Quang` và kiểm tra remote refs. Khi ấy `origin/Truc` ở `fa1501f35bc670a3c6061eb44e0b888a5639c260`, đã là tổ tiên của checkout `main` local `c5661ae`, nên không tạo merge giả. Các cập nhật Trúc đến `5583caa` của lượt tiếp theo được review/tích hợp tại [TRUC_APP_REVIEW_AND_DESIGN.md](TRUC_APP_REVIEW_AND_DESIGN.md).

Workbook `CouponHub_PhanCong_ChiTiet_4Dot_TheoVaiTro.xlsm` chỉ dùng xác định đầu việc U4/A4/M4, không chạy macro, không lấy ô tiến độ làm bằng chứng và không lấy giải pháp kỹ thuật trong sheet làm yêu cầu. Giữ cách tổ chức PHP/mysqli, form POST, session server và class/repository của ứng dụng, theo Lesson 7, 11, 13, 15 trong tài liệu giảng viên. Không thêm framework hoặc JWT riêng.

## Ma trận 14 đầu việc P0

| Mục | Kết quả trong bản tích hợp | Bằng chứng / giới hạn |
|---|---|---|
| U4.01 Thu hồi/khóa shop ở public | Code và kiểm thử local đạt | DB/HTTP đợt 3 kiểm tra detail/search/campaign, khóa/mở không phục hồi mã thu hồi. HTTP đợt 4 bổ sung saved list và mã không còn được cung cấp để sao chép. |
| U4.02 Empty/error/404/network/form | Code và kiểm thử local đạt | HTTP error contracts, ID sai trả 404, empty search, kiểm tra form/interaction. Chrome mô phỏng offline khi lưu: báo lỗi, giữ nội dung, mở lại nút để thử lại. |
| U4.03 Responsive các luồng chính | Đạt trong phạm vi đã đo | Chrome tại 320/390/768/1440 px; homepage, category, community/detail, campaign/detail, auth, empty/404, đóng góp/saved/account, Merchant list/forms. Kiểm tra viewport, tràn trang, header không cắt/chồng và menu mở được. Không thay thế thử mọi thiết bị/trình duyệt. |
| A4.01 GitHub OAuth | Code/local đạt; nghiệm thu thật còn chờ | State/PKCE, callback, verified identity và session có hồi quy. Cần chủ dự án điền `.env` và đăng nhập GitHub thật. |
| A4.02 Facebook OAuth | Code/local đạt; nghiệm thu thật còn chờ | Kiểm tra response/introspection giả lập, state, app/user/expiry. Cần app Meta, Graph version, callback và tài khoản được phép để kiểm chứng thật. |
| A4.03 Liên kết tài khoản | Code và kiểm thử local đạt | Liên kết tường minh, không tự ghép email, unique identity, không thay chủ identity, tài khoản khóa bị chặn. Facebook cần tài khoản CouponHub có sẵn để liên kết lần đầu. |
| A4.04 Quyền ghi Admin | Code và kiểm thử local đạt | Router role/CSRF/Origin, kiểm tra actor trong transaction/routine, malformed input, stale/demoted actor, audit. Kiểm tra toàn bộ 36 route Admin tại root và thư mục con. |
| A4.05 Tích hợp chung | Code và kiểm thử local đạt | Một schema/router/session, đăng nhập popup và redirect theo role; hồi quy Trúc/Quang. Remote mới được fetch và ancestry được xác nhận. |
| A4.06 Xuyên luồng | Local đạt; bàn giao host thật còn chờ | Submit/moderate/campaign/revoke/lock/unlock, account/OAuth/OTP, root/subfolder và URL trực tiếp được chạy lại. TLS/proxy, OAuth và thư thật cần cấu hình triển khai. |
| M4.01 SQL smoke riêng | Đạt trên database dùng một lần | Chỉ `couponhub_merge_test` tại `127.0.0.1:13379`; test từ chối override local không an toàn. Không ghi vào database đang dùng. |
| M4.02 Bàn giao DB | Code và kiểm thử local đạt | Cài trống schema rồi demo riêng, guard DB không rỗng, rollback lỗi seed, migration chạy lặp hai lần bảo toàn bản ghi. Hướng dẫn tại README, DEMO_DATA và PHUC_PHASE4_REVIEW. |
| M4.03 Audit | Code và kiểm thử local đạt | Người thao tác/thời điểm/lý do của revoke, lock/unlock, xử lý report và thay đổi trọng yếu được kiểm tra ở DB; giữ dữ liệu, không xóa lịch sử để đổi trạng thái. |
| M4.04 Merchant hiểu mã bị thu hồi | Đã sửa thiếu sót UI và bổ sung kiểm thử | Danh sách chính và `/shop.php` hiện lý do đã escape; danh sách chính có thời điểm. Không hiện Edit/Stop cho mã terminal, direct edit bị chặn; phân biệt “Bị thu hồi” và Merchant “Đã tắt”. |
| M4.05 Form/empty/mobile Merchant | Đã sửa và kiểm tra local | Header form/list wrap; coupon table thành hàng đọc được trên mobile. Không có shop được phép đăng thì không dẫn đến form select trống; hiển thị hướng dẫn quyền shop. |

Không kết luận toàn bộ đợt 4 đã nghiệm thu thực tế 100% khi A4.01/A4.02 chưa đăng nhập nhà cung cấp thật. `.env` và `config/local.php` chưa có tại thời điểm rà soát; không tự tạo credential hoặc lấy secret từ dự án khác.

## P1 đã kiểm tra thêm

- U4.04: bình luận ghi DB, validation/rate limit theo session/actor; bổ sung DB test xác nhận `HIDDEN` không nằm trong kết quả public. Không tuyên bố có hệ thống chống spam nâng cao.
- U4.05: hồ sơ và saved list có hồi quy; cập nhật hồ sơ không nhận role/status/provider identity do client tự gửi.
- M4.06: VIEW/COPY có event/throttle và kiểm thử, không suy ra doanh số/chuyển đổi mua hàng.

## Sửa lỗi phát hiện trong lượt này

1. Merchant list thiếu lý do thu hồi và vẫn gợi ý thao tác không hợp lệ: bổ sung dữ liệu review/status shop vào query, hiển thị lý do/thời điểm, ẩn action không hợp lệ. Backend guard hiện có vẫn là nơi quyết định quyền ghi.
2. Legacy `/shop.php` thiếu nhãn/lý do thu hồi và không phân biệt publication disabled với moderation approved: sửa trình bày, không đổi dữ liệu hoặc procedure.
3. Không có shop APPROVED vẫn mở form tạo coupon trống: redirect 303 về danh sách với thông báo hướng dẫn.
4. Header mobile bị cắt dù document không tràn vì `overflow-x:clip`: sửa chiều rộng và khoảng cách header; ẩn shortcut search trên mobile, giữ chuông, đăng nhập/tài khoản và menu. Bổ sung assertion bounding rect thay vì chỉ tin scrollWidth.
5. Danh sách coupon Merchant khó đọc trên màn hình hẹp: bố cục từng hàng có nhãn; tiêu đề/nút trên form và campaign list cho phép xuống dòng.

## Cách chạy và giới hạn

Với MariaDB thử đã được dựng riêng trên cổng 13379:

```powershell
$env:COUPONHUB_RUN_DB_TEST = '1'
python tests/run-phase4.py --browser
```

Runner có reset fixture và migration; **không đổi cổng/schema sang database đang sử dụng**. Chạy browser cần Chrome và Python package `websocket-client` đã có trên máy, không dùng hồ sơ Chrome cá nhân. Hai cổng thử 8098/9228 phải trống; test không tái sử dụng tiến trình khác. Bỏ `--browser` nếu chỉ chạy bộ PHP/JS/SQL/HTTP; việc bỏ này cũng bỏ bằng chứng responsive/offline thực trong Chrome.

Kiểm thử HTTP dùng root và `/CouponHub_UI`; SMTP thử nội bộ không gửi mail thật. Sau lượt chạy, fixture được khôi phục và các tiến trình server/Chrome/database do lượt thử sở hữu được đóng. Không dừng MySQL/XAMPP đang dùng của người phát triển.

Đối với database đã có dữ liệu: backup rồi chỉ áp migration còn thiếu theo [PHUC_PHASE4_REVIEW.md](PHUC_PHASE4_REVIEW.md). **Không import lại schema.sql hoặc demo.sql.** Migration phase 4 vẫn chưa tự áp vào database đang dùng trong lượt này.

## Việc còn cần chủ dự án nghiệm thu

Điền `.env` riêng rồi kiểm tra GitHub/Facebook thật, trường hợp cancel/deny, liên kết đúng chủ và đăng nhập lại. Kiểm tra Resend/SMTP thật, HTTPS/cookie Secure, canonical URL và POST qua proxy trên host dự kiến. Tham khảo [AUTH_SETUP.md](AUTH_SETUP.md) và [PHUC_PHASE4_REVIEW.md](PHUC_PHASE4_REVIEW.md); không gửi secret vào chat/Git. Chưa coi commit/push là bằng chứng deploy.

## Kết quả chạy thực tế

- `.runtime/phase4-review-1793757040264664100/`: hồi quy đợt 3, nhóm OAuth/security/repository mới, migration phase 4 chạy hai lần bảo toàn bản ghi và DB acceptance đều qua. Hồi quy JavaScript **56/56**; DB test bổ sung xác nhận bình luận `HIDDEN` không được trả ra public.
- Lượt tổng hợp dừng tại assertion mới kiểm tra keyword vì nó khớp cả từ khóa hợp lệ trong ô tìm kiếm. Đã sửa assertion để xét article kết quả, copy action và link detail. Không đổi logic tìm kiếm để chiều theo test.
- `.runtime/phase4-final-tail-1793757501960042900/`: reset fixture rồi chạy lại HTTP đã sửa và toàn bộ bước còn lại; **đều qua**. HTTP root/subfolder xác nhận escape lý do thu hồi, ẩn action terminal, detail 404, saved không cung cấp mã, phân biệt DISABLED; toàn bộ **36 route Admin trên mỗi base URL** kiểm tra quyền/CSRF/input; cookie/Origin/body limit/OAuth rejection/throttle/session/logout qua. **20 process cùng quota 7: 7 được nhận, 13 bị chặn**.
- Chrome ở lượt cuối: **68 lượt trang/kích thước**, menu mobile và offline favorite đều qua. Screenshot community và Merchant list tại 390 px đã được mở để kiểm tra trực quan. Đã khôi phục fixture và đóng các tiến trình thử do runner sở hữu.

Các log/screenshot trong `.runtime` là artefact local bị Git ignore, không đưa hồ sơ Chrome/session vào repository. Kết quả gồm lượt tổng hợp và lượt chạy tiếp phần assertion đã sửa, không mô tả đây là một lượt duy nhất chạy xanh liên tục.

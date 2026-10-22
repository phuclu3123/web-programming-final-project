# Review tích hợp Quang và Admin phase 2 — 22/10/2026

## Phạm vi và nguồn

- Bản nền: `main` / `Phuc` tại `8ec6e85`.
- `origin/Quang`: `3e38b3bbec8aada79afa7f64684c8c9d692a71ac`.
- Nhánh bổ sung: `origin/feature/admin-brief-phase2`, `bd9e24be6b8d6f5fd4ab760f6f3d41f6ee7d8de2`; nối tiếp Quang bằng một commit quản lý coupon/danh mục Admin.
- Hợp nhất nhánh bổ sung để giữ lịch sử cả hai nhánh. Giải quyết Auth/Router/routes bằng cách giữ cả tài khoản mới và thông báo, phân quyền, popup đăng nhập hiện có. Không phục hồi `app.zip` đã bị xóa ở bản nền.
- Không sửa schema/API cũ để làm test qua. Bổ sung endpoint COPY và hồ sơ theo nhánh, giữ các hợp đồng API đang dùng. Không import hoặc ghi dữ liệu vào DB thật `dbcouponshubweb_v2`.

## Các phát hiện đã sửa

| Vấn đề | Tác động và cách sửa |
| --- | --- |
| Hồ sơ dùng actor từ `ch_auth_user` không có phone | Đọc hồ sơ đầy đủ theo ID phiên, giữ quyền từ Auth; số điện thoại được điền lại đúng. |
| `updateProfile` trả true với tài khoản bị khóa | Kiểm tra ACTIVE khi xác nhận kết quả; dữ liệu không đổi vẫn được chấp nhận. |
| Đổi mật khẩu còn giữ liên kết reset cũ | Hash mới, tăng auth_version và thu hồi các token chưa dùng trong cùng transaction; CAS hash so sánh binary. |
| Bộ lọc REJECTED dùng effective_status | Tách trạng thái quản trị theo moderation, không thay đổi view công khai. |
| Danh sách Admin chỉ lấy 100 bản ghi | Phân trang 25, đếm tổng và giữ bộ lọc; kiểm tra truy cập cả coupon cũ ngoài 100 bản ghi. |
| Mọi lần lưu coupon đều xóa vote | Không sửa gì thì không ghi lịch sử giả và không xóa vote; sửa tiêu đề/danh mục giữ vote; đổi điều kiện đặt lại xác minh và có audit. |
| Form datetime làm mất giây | Giữ giây trong datetime-local; validation chấp nhận chính xác cả phút và giây, vẫn chuyển VN sang UTC. |
| Thao tác ghi mới chỉ tin guard HTTP | Kiểm tra Admin ACTIVE trong transaction sửa/hết hạn/xóa coupon và sửa/xóa danh mục; giữ khóa đọc quyền đến commit. |
| Dữ liệu mảng và thông báo lỗi DB | Chặn dữ liệu mảng/UTF-8 lỗi ở service mới; không đưa lỗi SQL thô ra flash ở các thao tác mới. |
| Danh mục trùng name hoặc slug | Kiểm tra cả hai khóa; chỉ xóa danh mục không có coupon/bài viết tham chiếu, kể cả dữ liệu đã lưu trữ. |
| Bộ nhớ throttle COPY tích lũy theo phiên | Dọn entry cũ, giới hạn 100 entry, giữ nguyên khóa coupon ID; chỉ cập nhật throttle sau khi DB ghi thành công. |

Đã kiểm tra việc sửa coupon không thể đổi merchant, trạng thái duyệt hay người thao tác bằng hidden input; coupon trùng mã và chồng thời gian bị chặn, thao tác lỗi không để lại sửa dở hoặc audit giả. Xóa coupon là xóa mềm có lý do và lịch sử; không xóa vật lý.

## Bằng chứng kiểm thử

Môi trường: PHP 8.1.25, MariaDB 10.4.32; DB dùng cho test **chỉ** `couponhub_merge_test` tại `127.0.0.1:13379`. Các server PHP thử dùng cổng riêng và session.save_path riêng. Test ghi dữ liệu phải chạy với cấu hình test rõ ràng và fixture riêng.

- `tests/sql-install.py`: schema/demo tách riêng, đăng nhập fixture, role/ownership, chặn import lặp và rollback khi seed lỗi.
- `tests/quang-integration.php`: quyền ghi tại repository, Admin bị khóa, giữ/reset vote đúng tình huống, audit và rollback, mã trùng, phân trang >100, danh mục có tham chiếu, cập nhật hồ sơ, CAS mật khẩu, thu hồi reset token và phiên cũ, guest COPY/throttle/coupon không khả dụng.
- `tests/quang-http.py`: request thật tại `/CouponHub_UI`; GET/POST, CSRF/Origin, User/Merchant/guest bị chặn, form sửa và validation 422, không đổi shop/trạng thái trái phép, hết hạn/xóa mềm, danh mục có tham chiếu, JSON COPY không giả actor, hồ sơ và hai phiên cũ mất hiệu lực khi đổi mật khẩu.
- `tests/login-redirects.py`: cả 6 tài khoản, JSON và form thường, root và subfolder, role isolation, safe return, thông báo/read receipts, một popup chung và không thêm nút đăng ký header.
- `tests/truc-merge.php`, `tests/truc-integration.php`: regression bộ lọc chiến dịch, BOTH channel, phân trang, lịch UTC/VN, quyền truy cập dữ liệu không công khai, favorites theo actor và quy trình gửi/kiểm duyệt.
- `tests/account-service.php`, `tests/admin-phase2.php`, `tests/auth-registration.php`, `tests/session-guards.php`, `tests/router-integration.php`: validation, đăng ký, guard và hợp đồng route/form.
- `node --test tests/*.test.cjs`: 20 test qua, gồm Bootstrap 5 contract, popup auth, carousel và đồng hồ chiến dịch.
- Lint PHP cho app/config, kiểm tra cú pháp JavaScript và `git diff --check`.
- Kiểm tra Chrome trực tiếp bản thử: một nút đăng nhập mở native dialog; giao diện popup vẫn hoạt động sau nâng Bootstrap. Kiểm tra này không thay thế kiểm thử toàn bộ trình duyệt/thiết bị.

## Giới hạn và rủi ro còn lại

- COPY là tương tác, **không phải số người dùng duy nhất**. Giới hạn 15 giây theo phiên có thể bị vượt bằng phiên mới/bot. Chưa có rate limit tập trung, bot detection hay kiểm thử tải lớn; không dùng số COPY làm căn cứ thưởng điểm hoặc tài chính.
- Các kiểm thử CAS/rollback xác nhận đường đi và điều kiện stale write; chưa chứng minh mọi lịch thực thi đồng thời dưới tải. InnoDB có thể từ chối một transaction khi deadlock; thao tác lỗi phải thử lại. Chưa có bộ retry transaction tổng quát.
- Reset vote khi điều kiện thay đổi không thu hồi điểm đã thưởng trước đó. Giữ chính sách điểm hiện hữu; không tự đặt cơ chế trừ điểm. Điểm thưởng có khóa chống thưởng lặp trong quy trình hiện có.
- Không có migration DB mới. Môi trường dùng schema cũ thiếu routine/view vẫn phải cài schema chuẩn theo hướng dẫn; không tự reset DB đang có dữ liệu.
- Bootstrap tải từ CDN. Đã kiểm tra các hợp đồng markup và popup thực tế; chưa kiểm tra đủ Safari, mọi độ rộng màn hình hoặc CDN mất kết nối.
- Các template cũ dưới `app/views/user` và assets tương ứng có thay đổi từ Quang nhưng không phải luồng route chính hiện tại. Không kích hoạt ứng dụng song song từ ZIP.
- Push Git là tích hợp mã nguồn; không phải bằng chứng đã deploy hoặc nghiệm thu máy chủ production.

## Chạy lại

```powershell
# Chỉ thực hiện sau khi MariaDB thử riêng đang chạy tại port 13379.
$env:COUPONHUB_RUN_DB_TEST='1'
python tests/sql-install.py
$env:COUPONHUB_DB='couponhub_merge_test'
$env:COUPONHUB_PORT='13379'
C:/xampp/php/php.exe tests/quang-integration.php
# Test trên đã thay đổi fixture thử; tạo lại trước bộ test HTTP.
python tests/sql-install.py
$env:COUPONHUB_RUN_HTTP_TEST='1'
python tests/quang-http.py
node --test tests/*.test.cjs
```

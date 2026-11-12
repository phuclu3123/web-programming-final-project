> **Ghi chú cập nhật 07/11/2026:** Mục bên dưới mô tả nghiệm thu lịch sử với bảng `article_comments`. Baseline hiện tại gộp vào `comments`, xem [DB_MIGRATION_21_TABLES.md](DB_MIGRATION_21_TABLES.md).

# Bài viết và bình luận — 06/11/2026

## Chức năng đã triển khai

- Bài viết được đọc từ `articles` trong MySQL. Có 8 bài biên tập: 3 hướng dẫn về điều kiện, so sánh tiết kiệm, kiểm chứng cộng đồng; 5 bài mới về ăn uống, mua sắm, xem phim, làm đẹp và du lịch. Đây là nội dung hướng dẫn, không phải chương trình khuyến mãi hoặc đánh giá khách hàng được dựng lên.
- Slug `/guides/...` chuyển đến bài đã xuất bản tương ứng ở `/articles/...`. Khi quản trị viên lưu trữ bài, nội dung và API bình luận không còn công khai. Trang góc đọc chỉ đưa vào các bài đang được xuất bản.
- Người chưa đăng nhập đọc được bình luận. Tài khoản USER, MERCHANT và ADMIN đang hoạt động được gửi bình luận; tên và người gửi được lấy từ session/DB, không nhận danh tính từ trình duyệt.
- Chủ bình luận được xóa bình luận của mình. ADMIN ẩn/hiện tại `/admin/article-comments`; bình luận đã xóa không thể hiển thị lại. Các thao tác ghi nhật ký vào `activity_logs`.
- AJAX dùng `XMLHttpRequest`, `FormData` và JSON. Chỉ hiển thị bình luận sau khi máy chủ xác nhận. Khi mất mạng giữ nội dung, giữ request ID để thử lại; gửi lại cùng nội dung/ID không tạo bản ghi trùng. HTML/JavaScript trong bình luận được hiển thị như văn bản.
- Giới hạn 2.000 ký tự, CSRF, kiểm tra Origin, trạng thái tài khoản và bài viết; dùng bộ giới hạn bình luận chung theo tài khoản/IP. Có phân trang theo ID, mỗi lần 20 bình luận. Form HTML vẫn gửi/xóa và chuyển lại bài viết khi không dùng AJAX.

## Bám theo bài giảng

Đã đọc `PHP-MySQL-Lesson 15.pptx`, gồm các slide 2–3, 8, 21 về OOP/MVC; 22–24 về AJAX, `XMLHttpRequest`, trạng thái HTTP và PHP truy vấn MySQL trả kết quả cho trình duyệt. Đã xem cả hình minh họa code trong slide 23–24. Các slide JSON/XML của `PHP-MySQL-Lesson (13).pptx` cũng được tham khảo.

Luồng triển khai: giao diện Bootstrap/HTML → XMLHttpRequest → Router + session/CSRF → ArticleCommentController → ArticleCommentRepository → prepared statements MySQL → JSON → cập nhật DOM bằng `textContent`. Không thêm framework frontend hay thay cơ chế đăng nhập hiện có.

AJAX nội bộ ở đây khác với việc tiêu thụ Webservice bên ngoài. Các tích hợp OAuth/Resend/ACCESSTRADE/Turnstile đã có vẫn giữ nguyên; việc nghiệm thu tài khoản nhà cung cấp thật còn phụ thuộc cấu hình riêng trong [PROVIDER_SETUP.md](PROVIDER_SETUP.md). Không dùng tính năng bình luận để tuyên bố đã kiểm thử các dịch vụ bên ngoài đó.

## Database và cài đặt

Database đang dùng đã được sao lưu riêng bên ngoài webroot trước khi chạy migration. Chỉ thêm bảng `article_comments` qua `database/migrations/20261106_article_comments.sql`, đã kiểm tra chạy lại an toàn; không import lại schema/demo và không thay bảng `comments` của coupon. Bản cài mới có 23 bảng, 6 view và 21 procedure.

Nội dung biên tập được đăng bằng `scripts/publish-editorial.php --apply --expected-database=DATABASE --author-id=ACTIVE_ADMIN_ID`. Lệnh dùng transaction, kiểm tra admin và database, ghi nhật ký, bỏ qua slug đã có để bảo toàn những bài được sửa/lưu trữ. File nội dung chỉ phục vụ nhập bài; giao diện không dùng nó để giả lập dữ liệu khi database trống.

Hướng dẫn cập nhật cho các máy khác: [database/README.md](../database/README.md). Tài khoản demo, nếu cần để thử tại máy phát triển: [DEMO_DATA.md](DEMO_DATA.md). Không nhập demo lên database đã có dữ liệu.

## Nghiệm thu với dữ liệu thật

Đã đăng nhập `user_demo` có role USER trên server local được bảo vệ, gửi bình luận bằng Chrome/XHR vào `dbcouponshubweb_v2`, đối chiếu nguyên văn UTF-8 với MySQL. Bình luận ID **1**, trạng thái `VISIBLE`, ghi rõ `[Kiểm thử chức năng]`; đây là thao tác kiểm thử công khai, không đóng giả trải nghiệm mua hàng. Phiên đọc ẩn danh mới cũng đọc được bản ghi này. Không chèn bình luận mẫu vào database đang dùng.

Database có **8 bài viết** sau khi đăng nội dung. Ngoài `articles`, `activity_logs` và bảng mới, checksum của **20 bảng nghiệp vụ ban đầu** giữ nguyên, gồm tài khoản, coupon, chiến dịch, điểm và bình luận coupon. Bản sao lưu nằm trong `%LOCALAPPDATA%/CouponHub/backups`, không đưa vào Git hoặc webroot.

## Kiểm định tự động

Các kiểm thử ghi dữ liệu hàng loạt hoặc kiểm tra phân trang chỉ chạy trên `couponhub_merge_test` ở `127.0.0.1:13379`; không dùng database đang phục vụ giao diện.

```powershell
$env:COUPONHUB_RUN_DB_TEST='1'
python tests/sql-install.py
python tests/article-comments-http.py
# Với các biến COUPONHUB_HOST/PORT/DB/USER/PASSWORD trỏ đúng database thử:
C:/xampp/php/php.exe tests/article-comments-concurrency.php
python tests/article-comments-browser.py
```

`tests/run-integrations.py --browser` ghép các kiểm thử này với bộ hồi quy hiện có. OpenAPI và Postman đã được cập nhật từ 124 operation trong router; Swagger có hợp đồng body/request ID và phân quyền của các route mới. Bằng chứng chi tiết được giữ riêng trong `.runtime`, không chứa trong thư mục công khai.

Kết quả cuối cùng được ghi ở `docs/api/acceptance-summary.json` sau khi hoàn tất lượt hồi quy.

K?t qu?: **64 ki?m tra HTTP/MySQL**, **8 ti?n tr?nh PHP ??ng th?i ch? t?o 1 b?nh lu?n**, Chrome ki?m tra AJAX/JSON/m?t m?ng/t?i l?i/x?a t?i 4 k?ch th??c, **128 test JavaScript**, **280 request Postman / 296 assertion / 0 l?i**, v? **176 ki?m tra trang/k?ch th??c to?n ?ng d?ng**. PHP lint v? ki?m tra whitespace ??t. Ki?m tra Swagger c? ghi c?ng 119 operation ?? ???c s?a ?? ??c s? l??ng route hi?n t?i (124); l??t ch?y l?i to?n b? Chrome ?? ??t. Log th?t b?i ban ??u v?n ???c gi? ri?ng, kh?ng ghi ?? b?ng ch?ng.

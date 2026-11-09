# Rà soát source và cấu trúc CouponHub, 09-10/11/2026

**Cập nhật tiếp ngày 10/11/2026:** theo yêu cầu giữ repo chỉ chứa source web, toàn bộ `tests/` và script Python/Shell/PowerShell/benchmark được chuyển ra `../CouponHub_DevTools/`, có manifest/checksum. CI mới chỉ kiểm tra cú pháp, không chạy regression. Các kết quả test dưới đây thuộc đợt trước khi tách; xem [QUALITY_CHECKS.md](QUALITY_CHECKS.md) cho cách dùng bộ riêng.

## Phạm vi

Rà source PHP/JS, bổ sung comment có ý nghĩa, bỏ comment trang trí, tách prototype và tài liệu tải về khỏi ứng dụng, cập nhật hướng dẫn phát triển/deploy. Không push, merge hoặc triển khai hosting. Không chạy migration/seed hay ghi dữ liệu vào database đang dùng.

Các thay đổi OAuth, cookie, email, database và giao diện đã tồn tại trước đợt này được giữ nguyên; không quy toàn bộ diff với HEAD thành thay đổi của lần clean.

## Comment và MVC

- Dùng `//` cho comment PHP/JS một dòng; không ghép `// #`. Dùng `/* ... */` khi phù hợp và giữ PHPDoc có thông tin kiểu cho IDE.
- Bổ sung trách nhiệm ở controller, repository, cấu hình, view/partial và module JS; chỉ giải thích flow ở OAuth, OTP, transaction, fingerprint và chống gửi trùng.
- Bỏ các dòng phân cách bằng dấu gạch và comment cấu hình DB gắn cứng thông tin máy cũ.
- Chuyển truy vấn danh sách shop đã duyệt từ `Admin/IntegrationController` sang `MerchantRepository::approvedForImport()`.
- Chuyển truy vấn slug sau bình luận từ `ArticleCommentController` sang `ArticleCommentRepository::articleSlug()`.
- Giữ nguyên SQL và điều kiện của hai truy vấn trên. Không thêm bảng, service chuyển tiếp hay backend riêng.
- Giữ wrapper PHP/API cũ vì router/form/callback đang dùng. Giữ `assets/` và `public/` vì đều có caller thực tế.
- Chẩn đoán kết nối trong health endpoint tiếp tục ở đó; không có SQL nghiệp vụ trong view.

Đối chiếu token PHP với bản sao trước sửa đã xác nhận phần chỉnh comment không thay đổi mã xử lý; bỏ qua comment, định dạng và xuống dòng HTML. Bốn file chuyển truy vấn được kiểm chứng thêm bằng test MySQL/HTTP.

## File đã chuyển ngoài repo

Nơi lưu: `../temp/CouponHub_cleanup_20261109/` tính từ gốc ứng dụng. `moved-files.json` ghi nguồn, đích, dung lượng và SHA-256 từng file. Không xóa vĩnh viễn.

| Nhóm | File | Nơi lưu trong gói |
|---|---:|---|
| Prototype User dùng mảng dữ liệu | 12 | `archived/app/views/user/` |
| CSS/JS chỉ dùng bởi prototype | 2 | `archived/app/assets/` |
| Mảnh HTML Bootstrap trong config | 1 | `archived/config/boostrap.md` |
| Bài giảng tải về và ZIP | 16 | `archived/drive-download-20261010T105110Z-1-001*` |
| Python bytecode cache | 3 | `archived/tests/__pycache__/` |
| Build LaTeX tạm | 11 | `archived/Latex/_build/` |
| Hình/trung gian LaTeX tạm | 428 | `archived/Latex/tmp/` |

Tổng 473 file, 152.783.045 byte (khoảng 145,7 MiB). Mỗi file được kiểm tra hash sau khi chuyển. Đây là sắp xếp lại, không phải giải phóng lượng dung lượng đó khỏi ổ đĩa. Source LaTeX, draw.io, PDF hoàn chỉnh, ảnh chạy thật và migrations lịch sử vẫn giữ. Thư mục `.runtime/` đã được ignore và chặn qua router; bằng chứng test vẫn nằm riêng tại đó.

`before-edit/` giữ các file trước sửa, gồm mẫu env trước khi làm sạch; gói temp này là riêng tư, không đưa vào Git hoặc webroot. Khi phục hồi, đối chiếu manifest và chỉ chuyển lại phần cần thiết, không ghi đè thay đổi mới.

## Cấu hình và bàn giao

- Hoàn chỉnh `.env.example` với placeholder trống cho khóa thật, không thay `.env` đang chạy.
- Thêm ignore cho cache, file tải về và build LaTeX; giữ source báo cáo/PDF hoàn chỉnh.
- Cập nhật README, trang bắt đầu, danh mục docs và quy ước comment.
- [DEPLOYMENT.md](DEPLOYMENT.md) thống nhất document root ở gốc chứa `index.php`, asset đã có trong Git, DB snapshot và env riêng.
- Chặn thêm `Latex/`, `temp/`, `tmp/` trong router/Apache; `.htaccess` chặn thêm đuôi source/công cụ không phục vụ qua web.
- Gói SQL bàn giao cũ chưa chứa 50 mục ACCESSTRADE. Cần xuất và kiểm chứng snapshot mới khi chốt đợt bàn giao; không xuất lại trong lần clean này.
- Tái tạo đặc tả OpenAPI và danh mục route trong lúc chạy Postman để khớp các route hiện tại, gồm Google One Tap.

## Kiểm thử đã chạy

Bằng chứng local ở `.runtime/cleanup-20261109/`; kết quả đầu/cuối được lưu riêng.

| Bộ kiểm tra | Kết quả |
|---|---|
| Node regression | 151 test, không thất bại |
| PHP regression theo CI | 19 chương trình kiểm thử, không thất bại |
| Merchant/MySQL | 38 kiểm tra PASS |
| User/MySQL | PASS danh mục, lọc chiến dịch, lưu mã, gửi/duyệt và riêng tư |
| Ràng buộc nghiệp vụ | 57 kiểm tra PASS |
| Role/auth guards | 33 kiểm tra PASS |
| Auth hoàn chỉnh | 37 kiểm tra PASS |
| Tài khoản OAuth hợp nhất | 22 kiểm tra PASS |
| Nguồn ngoài/transaction | 35 kiểm tra PASS |
| Nguồn ngoài qua HTTP | 15 kiểm tra PASS |
| Bình luận bài viết qua HTTP | 67 kiểm tra PASS |
| Bình luận đồng thời | 8 worker: một ID, một lần tạo, một hàng MySQL |
| Postman CLI local | 280 request; 296 assertion PASS, 0 thất bại |
| PHP lint | 289 file, không lỗi cú pháp |
| JS syntax | 30 file ứng dụng và 36 file test, không lỗi |
| Token PHP phần sửa comment | 157 file, không đổi token xử lý ngoài phạm vi đã tách truy vấn |
| Private routes qua router local | 9 đường dẫn trả 403, body không chứa nội dung riêng |
| Font Awesome qua HTTP | CSS và 2 webfont trả 200 |
| Asset vendor trong Git | 7 file bắt buộc có đủ, không rỗng |
| Giá trị secret từ `.env` trong text đủ điều kiện phát hành | Không phát hiện; không kiểm tra lịch sử Git trong đợt này |
| Link tài liệu bàn giao và `git diff --check` | PASS |

Test ghi chỉ chạy trên MariaDB mới do runner sở hữu tại `127.0.0.1:13379/couponhub_merge_test`, có guard port/datadir và tắt server sau khi xong. Provider keys được loại khỏi môi trường test; không gửi email, không gọi OAuth/ACCESSTRADE thật.

Hai datadir MariaDB tạm do lần kiểm thử này tạo đã được xóa sau khi xác nhận tiến trình dừng và đường dẫn thuộc Temp. Các file gốc chuyển vào gói lưu trữ không bị xóa.

Kết quả lint toàn source, kiểm tra giá trị secret, HTTP đường dẫn riêng và asset được lưu tại `.runtime/cleanup-20261109/final-audit.json`. Đối chiếu file này cùng log khi nghiệm thu.

## Giới hạn

Đây là nghiệm thu code, database test và HTTP local. Không phải kiểm chứng giao diện bằng trình duyệt thật, nhà cung cấp ngoài hay hosting HTTPS. Tại thời điểm hoàn tất lần clean chưa có commit/push mới; kết quả xuất bản Git được xác nhận ở đợt riêng sau đó. Trước khi push cần kiểm tra staged files và secret lần nữa; trước khi deploy cần snapshot DB mới và nghiệm thu theo [DEPLOYMENT.md](DEPLOYMENT.md).

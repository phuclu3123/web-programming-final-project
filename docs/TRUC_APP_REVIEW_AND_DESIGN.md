# Trúc: tích hợp chức năng và hoàn thiện UI

## Nguồn code

Fetch `origin/Truc` từ `fa1501f` lên `5583caa` trên nền local `b996218`. Đã đọc thay đổi controller/service/view/script, không lấy commit message hoặc tests của tác giả làm bằng chứng duy nhất. Nhóm thay đổi mới: lỗi inline của hồ sơ/mật khẩu; phản hồi bình luận; category picker chung; search responsive; lịch tháng với cập nhật bằng fetch.

Đã giữ sửa thu hồi Merchant, bảo vệ session/CSRF/quyền và header mobile của bản local khi giải quyết xung đột `integration.css`. Không nhập code React/Tailwind hoặc thay quy trình xác thực. Nhánh Quang cũng được fetch để quan sát refs; commit hiệu năng mới riêng của Quang không được tự merge trong yêu cầu tích hợp Trúc này.

## Lỗi sửa sau review

- Lịch mới duyệt từng ngày của toàn bộ thời gian chiến dịch: giới hạn vào grid đang xem, tránh xử lý không giới hạn với chiến dịch kéo dài nhiều năm.
- Mốc kết thúc lịch được xử lý exclusive, nhất quán với query overlap; kết thúc đúng 00:00 giờ Việt Nam không đánh dấu thêm ngày kế tiếp.
- Tháng kết thúc vào Chủ nhật không thừa một tuần; mũi tên không dẫn ra ngoài khoảng năm controller chấp nhận (2020–2035).
- Response calendar cũ hoàn tất body muộn không thay kết quả mới; giữ focus trường/nút sau cập nhật, lỗi mạng giữ trang và URL cũ.
- Dữ liệu ngày lễ bên ngoài được kiểm tra kiểu, ngày hợp lệ và quốc gia; array hoặc ngày vô nghĩa không gây warning/exception khi render.
- Hồ sơ/mật khẩu: bổ sung kiểm chứng thực qua HTTP về lỗi theo trường, escape giá trị, trạng thái form chỉ dùng một lần, không in lại mật khẩu và không nhận role/status từ client.

## Skill đã tải

Đã dùng helper của `skill-installer` để cài vào thư mục skill cá nhân, không đưa các bộ này vào webroot, không thêm runtime dependency cho PHP. Các bộ có thể được Codex nhận diện tự động từ lượt chat tiếp theo; lượt triển khai này đọc trực tiếp nội dung đã cài.

| Bộ | Nguồn | Áp dụng |
|---|---|---|
| UI UX Pro Max | [nextlevelbuilder/ui-ux-pro-max-skill](https://github.com/nextlevelbuilder/ui-ux-pro-max-skill) | Thứ tự ưu tiên contrast, keyboard, feedback, responsive; search theo sản phẩm rồi đánh giá độ phù hợp. |
| Taste | [Leonxlnx/taste-skill](https://github.com/Leonxlnx/taste-skill) | Phân biệt public page và app screen, inventory dữ liệu, xem render và sửa; app sheet `framed-sheet`, seed `1395432148`, giữ palette/type hiện có. |
| Awesome Design | [FuturizeRush/awesome-design-skill](https://github.com/FuturizeRush/awesome-design-skill) | Dùng như danh mục lựa chọn; không cài hàng loạt hoặc mặc định framework theo gợi ý. |
| Design Motion Principles | [kylezantos/design-motion-principles](https://github.com/kylezantos/design-motion-principles) | Hướng Jakub cho polish, Emil cho thao tác thường xuyên; motion ngắn và reduced-motion. |

Đây là nguồn tham khảo, không phải chứng nhận thiết kế hay bằng chứng app đúng. Các tiêu chí trái yêu cầu người dùng không được áp dụng máy móc: giữ font hệ thống hỗ trợ tiếng Việt, màu xanh và banner kính đã chọn; không thêm scroll scenes/stagger toàn trang, testimonial hoặc số liệu giả. Kết quả đầu tiên của Pro Max đề xuất palette tím và testimonial không phù hợp; không persist hay áp dụng kết quả đó. Tìm hẹp lại đúng nhóm e-commerce, giữ quyết định thương hiệu của dự án.

## Quyết định UI

Mục tiêu của màn hình công việc là biết việc gì cần xử lý tiếp theo. Điều hướng Admin/Merchant đặt cạnh nội dung trên desktop, thu vào disclosure native trên mobile; hoạt động được khi thiếu JavaScript. Hàng đợi kiểm duyệt đứng trước số tổng; link hàng đợi mở đúng bộ lọc thật. Merchant chọn cửa hàng bằng GET và nút Xem, thay cho bắt đổi trang ngay khi vừa chọn bằng bàn phím.

Số liệu dùng `$stats`/repository hiện có, số 0 giữ nguyên. Không tạo biểu đồ/đà tăng/doanh số suy đoán. Số hoạt động và tương tác được nhóm theo ý nghĩa thay vì lặp bảy ô KPI. Các trang con dùng chung nav nên không phải quay dashboard để tìm chức năng khác.

Một lớp CSS `refinement.css` dùng token chung cho ink/muted/surface, giữ Bootstrap; font hệ thống, độ đậm 600–700, input 16px, action thông thường 44px và margin nhất quán. Heading, độ rộng đoạn văn và khoảng cách bài hướng dẫn được chỉnh lại; giữ kệ đọc đã có. Danh mục bỏ chỉ số trang trí, nói rõ việc tìm ưu đãi theo nhu cầu. Không tải ảnh/font mới; bảo toàn banner và tài sản đã có.

Motion mới chỉ là phản hồi trạng thái và dialog xuất hiện 180ms/6px; có `prefers-reduced-motion`. Không bắt nội dung đợi animation; các tác vụ thường xuyên không có entrance riêng. Hero giữ hành vi đã được người dùng chỉ định; không thêm nút dừng.

## Phạm vi nghiệm thu

Runner tổng hợp `tests/run-phase4.py --browser` dùng database dùng một lần `couponhub_merge_test` tại `127.0.0.1:13379`, reset chỉ fixture thử. `COUPONHUB_FULL_UI_AUDIT=1` mở rộng Chrome để đọc public, User, Merchant, Admin và chụp cả desktop/mobile. Kiểm tra root/subfolder, phân quyền/CSRF, session/cookie, rate limit, database/migration và hồi quy Trúc/Quang được giữ.

`tests/calendar-render.php` kiểm tra lịch tháng, timezone/end-exclusive, chiến dịch dài, escape, giới hạn năm và input ngày lễ. `tests/deal-calendar.test.cjs` kiểm tra network failure, HTML response, focus và response cũ về muộn. Browser còn thử category dialog/search/close-focus và menu workspace thực tế, bên cạnh offline favorite.

Kiểm tra bằng Chrome headless không thay thế Safari/iOS, máy cấu hình yếu, screen reader hoặc hạ tầng production.

## Bằng chứng đã chạy

- Runner tổng hợp tại `.runtime/phase4-review-1793813715854349500/` đã qua: hồi quy đợt 3, schema/demo/migration, role redirects, auth/OTP/OAuth giả lập, audit/DB, HTTP root và thư mục con, 36 route Admin trên mỗi base, malformed input, CSRF/Origin, thu hồi coupon và phiên. Quota 7 với 20 process: 7 nhận / 13 chặn. Migration phase 4 lặp hai lần bảo toàn dữ liệu fixture.
- HTTP bổ sung xác nhận lỗi inline của hồ sơ/mật khẩu, escape input, không in lại mật khẩu, không đổi được role/status và lỗi form không lưu dai qua các lần GET.
- Bộ JavaScript cuối **90/90**; gồm bốn ca hành vi calendar mới. PHP calendar-render qua các ca timezone, exclusive-end, cuối tháng, chiến dịch dài và JSON ngày lễ sai kiểu/ngày. PHP lint đã qua trên app/config; các view sửa cuối được lint lại.
- Chrome toàn app **164 lượt trang/kích thước** trên 41 đường dẫn tại 320/390/768/1440 px; offline favorite, category search/empty/close-focus, menu workspace, notification panel, Escape thực và reduced-motion được kiểm tra. Lượt `.runtime/phase4-browser-1793814998808150100/` bổ sung kiểm tra nhãn danh mục không bị ép vào cột index đã bỏ. Sau sửa specificity banner, chạy lại 68 lượt public/User/Merchant và 4 lượt riêng campaign; các assertion đều qua. Đã mở ảnh banner cuối ở 320/390 px tại `.runtime/phase4-browser-1793815235298881100/` để xác nhận tiêu đề và mô tả không còn bị ép theo bố cục desktop.
- Đã mở screenshot desktop/mobile của các luồng chính, contact sheet toàn bộ các trang, và các ảnh full-page do Taste tạo. Vòng xem ảnh phát hiện chuông bị ẩn, cột index mobile và banner chỉ dành 55% cho chữ; đã sửa và kiểm tra lại. Không dùng scrollWidth đơn lẻ làm bằng chứng giao diện đẹp/không bị cắt.
- Taste inspector chạy tại ba chiều rộng trên community và bài viết; report tại `.runtime/taste-review-*/`. Các lỗi chữ dưới 11px, nhãn mã tương phản 2.3:1 và font Inter khai báo nhưng không tải đã được xử lý. Cảnh báo bổ sung về chữ phụ được làm tối/tăng kích thước. **Không tuyên bố inspector 0 FAIL**: quy tắc loại shadow trên nút/logo identity và các quy tắc bố cục app screen không hoàn toàn áp dụng cho banner, catalog dài và góc đọc. Giữ kính/đổ bóng của nút nổi theo yêu cầu, nhận diện logo thương hiệu và thứ tự đọc dài; các cảnh báo phong cách này không phải chứng nhận a11y đã đạt.

Các artefact `.runtime` bị Git ignore; không commit session, Chrome profile hoặc secret. Không có schema/migration mới trong lượt này và không thay database đang dùng. Commit chỉ là bản tích hợp local, không phải xác nhận deploy.

## Những gì chưa thể chốt

OAuth GitHub/Facebook thật, mail Resend/SMTP thật và HTTPS/proxy/cookie trên host thật vẫn cần `.env` riêng và lượt nghiệm thu thực tế của chủ dự án. Không tự lấy secret từ dự án khác. Xem [PHASE4_ACCEPTANCE.md](PHASE4_ACCEPTANCE.md) và [AUTH_SETUP.md](AUTH_SETUP.md). Database đang dùng không được reset hoặc tự áp migration trong lượt này; database có dữ liệu cần backup rồi migration-only.

# Tích hợp có chọn lọc nhánh Trúc — 16/10/2026

Nguồn đã fetch: `origin/Truc` tại `4677557d75aa475ff00fc7bb1030087dd8cc19a1`. Đã xem cả source được track và nội dung `app.zip` (bản zip cũng chỉ chứa nhóm trang User). Tích hợp thủ công trên bản đang làm của `main`; không merge đè, checkout nhánh, reset, stash, commit hoặc push. Các thay đổi UI/Auth/kiến trúc trước đó được giữ.

Bản sao trước khi chỉnh: `../../.integration/truc-20261016-161636/before-integration.zip`. Source Trúc để đối chiếu nằm cạnh bản sao, ngoài repository; không đưa thêm bản sao ứng dụng vào project.

## Quyết định chọn lọc

| Phần của Trúc | Xử lý trong dự án chung |
|---|---|
| Trang chủ, header/footer, modal Auth riêng | Giữ phong cách trang chủ hiện tại, header dùng chung, avatar gọn và thanh kính sát mép phải; không chép CSS 3.000 dòng hoặc thêm luồng Auth |
| Danh mục có thẻ lớn, nút xem tất cả | Thêm `/categories`, lấy danh mục đang bật và số coupon ACTIVE từ repository; card theo token hiện tại, không dùng danh mục/mã viết cứng |
| Gợi ý tìm kiếm khi gõ | Lấy tối đa 5 gợi ý từ kết quả backend vừa tải của trang chủ, không gọi thêm API hoặc tìm trong mảng mẫu; chọn gợi ý mở đúng `/coupons/{id}` |
| Tim/yêu thích ngay trên thẻ | Thêm nút lưu trên trang chủ; trạng thái từ bảng favorites theo actor, POST có CSRF qua InteractionService. Có form hoạt động khi tắt JS, không dùng localStorage |
| Thẻ dẫn đến chi tiết coupon | Tiêu đề và liên kết chi tiết là anchor thật; nút copy/lưu độc lập, không dùng onclick trên toàn card |
| Chi tiết, điều kiện, đi tới cửa hàng | Chỉnh bố cục theo UI hiện tại; chỉ mở promotion_url thật đã kiểm tra http/https, không dẫn mọi coupon tới Shopee |
| Sao đánh giá, bình luận mẫu và form báo cáo alert | Giữ xác minh WORKING/NOT_WORKING/CONDITION_CHANGED, bình luận và báo cáo từ DB. Không nhập sao 4/5, số lượt hoặc bình luận viết cứng |
| Chiến dịch và chi tiết chiến dịch | Thêm danh sách `/campaigns`, lọc đang diễn ra/sắp bắt đầu, phân trang. Dùng campaign/campaign_coupons thật; ID sai trả 404, không tự chọn chiến dịch khác |
| Đóng góp mã và “Mã của tôi” lưu localStorage | Giữ quy trình gửi DB → PENDING → Admin duyệt/từ chối. Làm lại danh sách đóng góp thành card, phân biệt moderation_status với effective_status, hiển thị lý do từ chối thật |
| Trang yêu thích riêng | Dùng tiếp `/saved-coupons` có tìm kiếm/bộ lọc/phân trang và bỏ lưu; không tạo trang hoặc nguồn dữ liệu thứ hai |
| Ảnh Unsplash/logo CDN và dữ liệu ưu đãi giả | Không nhập vào DB hoặc dùng làm nội dung mặc định. Dùng dữ liệu demo đã có và icon SVG của dự án |

## Logic được siết lại

- Các đường đọc campaign công khai cùng kiểm tra PUBLISHED, chưa hết hạn và shop chủ quản APPROVED/chưa xóa. Shop bị khóa không còn lộ campaign qua trang danh sách, chi tiết hoặc portal. Campaign nền tảng không có owner shop tiếp tục hoạt động.
- Từ chối coupon qua CouponService phải có lý do từ 10 đến 1.000 ký tự, đồng nhất với quy trình form duyệt cũ. Người gửi thấy “Bị từ chối” kể cả publication đang DISABLED.
- URL khuyến mãi phải là http/https, không chứa credentials; view cũng chặn URL cũ không hợp lệ. Không chèn URL tùy ý vào JavaScript inline.
- Trạng thái đã lưu được đọc một lần cho cả lưới, theo đúng user_id. Nút chỉ đổi trạng thái sau khi backend xác nhận; lỗi mạng không giả báo thành công. Admin không dùng nút lưu User trên trang chủ.
- Gợi ý tìm kiếm dùng textContent/anchor, hỗ trợ ArrowDown/Escape, đóng khi rời ô tìm kiếm hoặc cuộn; lớp gợi ý không bị hero cắt mất.
- Sửa URL trang chủ khi ứng dụng chạy ngay document root: trả `/` thay vì href rỗng dẫn lại trang hiện tại. Cách chạy trong `/CouponHub_UI` tiếp tục giữ base path.

## Cấu trúc và database

Controller/repository/interface mới tiếp tục theo [ARCHITECTURE.md](ARCHITECTURE.md). Asset bổ sung ở `assets/css/discovery.css`, `assets/js/discovery.js`; tích hợp thẻ tại `views/partials/coupon-card.php`. Trang chủ giữ layout hiện tại, các trang mới dùng `layouts/main.php` và các partial header/footer chung.

Không thêm bảng, procedure, kết nối hoặc session. Không thay `database/schema.sql`, không import dump Trúc, không chạy seed lên database đang dùng. **Không cần import SQL thêm**. Collation giữ `utf8mb4_vietnamese_ci`; mã kỹ thuật vẫn theo quy ước [RULES.md](../database/RULES.md).

## Kiểm tra

- PHP syntax các controller/repository/view bị ảnh hưởng; JS syntax; Auth registration, session/role/CSRF/OAuth guard, Router reflection và bộ JS Auth hiện có.
- MariaDB cô lập port 13379: import schema mới, chạy seed demo rồi `tests/truc-integration.php`. Kiểm tra đếm danh mục, filter/page campaign, shop bị khóa, draft/hết hạn, favorites khác actor, gửi/duyệt/từ chối và URL sai.
- HTTP trên server kiểm thử: guest và CSRF, lưu/bỏ lưu cả JSON và form không JS, trạng thái giữ sau tải lại, không lẫn User; bình luận có HTML được escape, vote và báo cáo lưu thật; gửi mã → riêng tư/chờ duyệt → Admin từ chối → người gửi nhận đúng lý do.
- Kiểm tra trực tiếp trình duyệt cho danh mục, gợi ý/keyboard, bookmark, chi tiết coupon, chiến dịch và màn hình nhỏ. Các request ghi trong đợt kiểm thử dùng database cô lập, không đưa nội dung test vào `_v2`.

OAuth Google/GitHub vẫn ở giai đoạn chuẩn bị; tích hợp Trúc không biến phần này thành đăng nhập OAuth thật. Các ưu đãi `DEMO_` tiếp tục là dữ liệu thử, không phải mã phát hành bởi thương hiệu.

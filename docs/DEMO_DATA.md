# Tài khoản và dữ liệu chạy thử

Database local: **`dbcouponshubweb_v2`**. Dữ liệu được tạo qua AuthService, quy trình đăng ký/duyệt shop, CouponService và repository hiện có; dùng chung session, role, membership, lịch sử kiểm duyệt và quy tắc điểm của ứng dụng.

## Đăng nhập

Có thể nhập **username hoặc email** trên trang `login.php`.

| Vai trò | Username | Email | Mật khẩu ban đầu |
|---|---|---|---|
| Admin | `admin_demo` | `admin.demo@couponhub.test` | `AdminDemo!2026` |
| Merchant — sàn thương mại | `merchant_shops` | `merchant.shops@couponhub.test` | `MerchantDemo!2026` |
| Merchant — dịch vụ | `merchant_services` | `merchant.services@couponhub.test` | `MerchantDemo!2026` |
| Merchant — phong cách/du lịch | `merchant_lifestyle` | `merchant.lifestyle@couponhub.test` | `MerchantDemo!2026` |
| User | `user_demo` | `user.demo@couponhub.test` | `UserDemo!2026` |
| User cộng đồng | `user_community` | `user.community@couponhub.test` | `UserDemo!2026` |

Mật khẩu trong DB được hash bằng bcrypt qua luồng đăng ký thật. Đây là tài khoản local có mật khẩu công khai để thử nghiệm; không triển khai nguyên bộ tài khoản này lên môi trường công khai. Email `.test` không nhận thư.

Đăng nhập Admin chuyển tới `admin.php?section=dashboard`; Merchant tới `shop.php`; User tới `index.php`. Các trang `/admin`, `/merchant`, `/dashboard` dùng cùng tài khoản và session.

## Quyền quản lý shop

| Merchant | Các shop được quản lý |
|---|---|
| `merchant_shops` | Shopee, Lazada, Tiki, Coolmate |
| `merchant_services` | Grab, be, ShopeeFood, Highlands Coffee |
| `merchant_lifestyle` | Traveloka, CGV, Hasaki, FPT Shop |

Mỗi shop có yêu cầu CREATE đã được Admin duyệt, trạng thái APPROVED và membership ACTIVE đúng chủ quản lý. Shop có slug `demo-<nền-tảng>`; phần mô tả ghi rõ không đại diện thương hiệu thật.

## Bộ dữ liệu ban đầu

- 6 tài khoản, 12 shop, 12 membership, 8 danh mục.
- 32 coupon: 24 mã Official theo nền tảng và 8 tình huống nghiệp vụ.
- Đủ 6 loại ưu đãi: phần trăm, số tiền, miễn phí vận chuyển, mua/tặng, quà tặng, hoàn tiền; đủ kênh ONLINE/OFFLINE/BOTH.
- 26 coupon đang hoạt động, 1 sắp có hiệu lực sau 7 ngày, 1 hết hạn, 2 chờ duyệt, 1 bị từ chối, 1 tạm tắt. Mã bị từ chối được lưu `REJECTED/DISABLED` theo luồng ứng dụng hiện tại nên view `effective_status` hiển thị DISABLED; hàng chờ Admin vẫn phân biệt REJECTED.
- 2 chiến dịch PUBLISHED: một chiến dịch đa nền tảng của Admin và một của shop Shopee.
- 6 lượt lưu, 6 bình chọn, 4 bình luận, 2 báo cáo chờ Admin xử lý.
- 4 cấp độ, 3 quy tắc điểm; hai coupon cộng đồng được duyệt phát sinh điểm theo procedure, không gán điểm tùy ý.

**Tất cả mã có tiền tố `DEMO_`, tiêu đề có `[DEMO]` và điều kiện ghi rõ chỉ dùng thử. Các mức giảm là tình huống minh họa, không phải mã có thể đổi trên Shopee/Lazada/Grab hoặc nền tảng thật.** Mã đang hoạt động có hiệu lực khoảng 90 ngày tính từ lúc nạp; riêng hai mã sắp hết hạn là 3 và 5 ngày. Khi hết hạn, hệ thống xử lý theo thời gian thật.

## Nạp trên máy local

Sau khi đã import `database/schema.sql` vào database trống và cấu hình kết nối, chạy tại thư mục `CouponHub_UI`:

```powershell
C:\xampp\php\php.exe scripts/seed_demo.php --confirm-demo
```

Lệnh chỉ chấp nhận host local và database `dbcouponshubweb_v2` (hoặc `couponhub_merge_test` để kiểm thử cô lập). Không import thêm SQL mẫu. Công cụ CLI này không xóa database, bảng hoặc dữ liệu.

Danh tính cố định theo username/email, slug shop/danh mục và mã coupon. Chạy lại trên bộ dữ liệu còn nguyên sẽ không tạo trùng. Các bản ghi đã có được giữ nguyên: không reset mật khẩu, nội dung, lịch hiệu lực hay trạng thái đã sửa. Các mục mẫu còn thiếu có thể được thêm lại, kể cả tương tác mẫu đã xóa; vì vậy chỉ chạy khi cần khởi tạo/bổ sung bộ demo, không chạy tự động mỗi lần mở web. Nếu gặp lỗi giữa chừng, dừng và kiểm tra lỗi trước khi chạy tiếp; các bước nghiệp vụ có transaction riêng, toàn bộ lệnh seed không phải một transaction duy nhất.

Riêng coupon lịch sử đã hết hạn được tạo hợp lệ rồi đặt lại mốc thời gian trên chính bản ghi mới và lịch sử của nó. Form gửi mã thật vẫn giữ quy tắc không cho gửi coupon hết hạn. Không tạo OAuth giả, token reset giả hoặc dữ liệu xác minh doanh nghiệp thật.

Fixture nằm tại `scripts/demo_fixture.php`, công cụ nạp tại `scripts/seed_demo.php`. Muốn điều chỉnh bộ mẫu cho lần cài mới, sửa fixture; không nhét INSERT dữ liệu cá nhân vào `database/schema.sql`.

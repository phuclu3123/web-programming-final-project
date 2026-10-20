# CouponHub — rà soát và cập nhật bản main (21/10/2026)

> **Phạm vi:** Làm trên bản sao từ `couponhub-main-review.zip` do nhóm xuất từ `origin/main`. Không thực hiện push/merge GitHub, không chạy thay đổi CSDL. Tài liệu đối chiếu: `docs/brief.md` và *Mã nguồn tham khảo.pdf* của giảng viên (đặc biệt PHP OOP, MVC, AJAX/JSON). Theo xác nhận mới nhất của nhóm, **Bootstrap 5.3.8 được giảng viên cho phép**.

## 1. Những thay đổi đã thực hiện trong bản bàn giao

| Mục tiêu | Mã nguồn liên quan | Kết quả |
|---|---|---|
| Thống nhất Bootstrap 5.3.8 | `app/views/layouts/main.php`, 29+ view và JS liên quan | Đổi CDN 4.6.2 → 5.3.8; chuyển data-bs-toggle/data-bs-dismiss, btn-close, modal JS API, một số class tiện ích (`mr-*` → `me-*`, `badge-*` → `bg-*`, `form-row`, `btn-block`, …). Giữ jQuery chỉ để tương thích các AJAX client hiện tại. |
| Phần hồ sơ của cả ba role | `AccountController`, `AccountService`, `UserRepository`, `app/views/account/profile.php`, menu / routing | Thêm `GET/POST /account`, sửa họ tên/số điện thoại (email read-only). Ràng buộc id được lấy từ phiên đăng nhập hợp lệ, không từ client. |
| Đổi mật khẩu trong tài khoản | `POST /account/password`, `AccountService`, `UserRepository` | Bắt buộc mật khẩu cũ, quy tắc độ mạnh, xác nhận; tăng `auth_version` và yêu cầu đăng nhập lại để vô hiệu hóa phiên cũ. |
| Ghi nhận sự kiện sao chép coupon | `CouponEventController`, `CouponEventService`, `CouponEventRepository`, `assets/js/coupon-events.js`, giao diện coupon | AJAX `POST /api/coupons/{id}/events` gửi JSON và nhận JSON, CSRF/Origin ở Router; gọi procedure đã có `sp_record_coupon_event`. Giới hạn mỗi coupon tối đa 1 sự kiện / 15 giây / session. Chỉ gửi sau khi sao chép thành công. **Không thay schema.** |
| Kiểm thử hồi quy | `tests/account-service.php`, `tests/bootstrap5-contract.test.cjs`, bổ sung case vào `tests/http-integration.py` | Bổ sung kiểm tra chính sách mật khẩu, render Bootstrap và endpoint. |

## 2. Đối chiếu yêu cầu kỹ thuật

| Tiêu chí | Bằng chứng trong mã nguồn | Đánh giá |
|---|---|---|
| Bootstrap 5.3.8 | `layouts/main.php`, `layouts/head.php`, `auth/page.php` cùng dùng CDN 5.3.8 | **Đã chỉnh sửa đồng bộ về phiên bản tải**; cần kiểm tra giao diện thực tế trên trình duyệt |
| PHP OOP | Class, interface, constructor, autoload; các service và repository | **Có** |
| MVC | `app/core/Router.php`, `app/controllers`, `app/models`, `app/views` | **Có cấu trúc MVC**, thêm Service + Repository; không bắt buộc mô phỏng chính xác từng file giảng viên |
| AJAX + JSON | `assets/js/discovery.js`, `public/js/coupon-detail.js`, `assets/js/coupon-events.js`; các `/api/*` | **Có luồng mã nguồn**; xác minh DB sẽ làm trên XAMPP |
| Tiêu thụ Webservice | `app/services/HolidayService.php` gọi Nager.Holidays bằng cURL và giải mã JSON | **Có triển khai**, chưa kiểm chứng Internet/response khi chạy |
| MySQL/MariaDB | `app/core/Database.php` (mysqli), stored procedures và `database/schema.sql` | **Có**, chưa kiểm thử runtime DB tại đây |
| CSRF, quyền người dùng | `Router::dispatch()`, `Auth::requireRole()`, SQL chuẩn bị | **Có cơ chế kiểm soát**; cần kiểm tra theo luồng thực tế |

### 2.1. USER — theo `brief.md`

- Đã có mã: đăng ký/đăng nhập/đăng xuất, tìm kiếm/lọc, chi tiết, favorite, vote, comment, report, đóng góp coupon, lịch sử điểm và Trust Score.
- Bản này **bổ sung** trang chỉnh sửa họ tên/số điện thoại và đổi mật khẩu. Chưa có chức năng thay email, avatar hoặc xác minh email khi thay đổi.
- Nghiệp vụ hoàn tất chỉ có thể xác nhận khi chạy với CSDL và tài khoản `USER`.

### 2.2. MERCHANT — theo `brief.md`

- Đã có route và code cho dashboard, merchant profile, coupon CRUD thuộc phạm vi Merchant, campaign và gắn/bỏ coupon.
- Bản này **nối sự kiện `COPY` từ giao diện về procedure**, tạo dữ liệu cho thống kê lượt sao chép. Sự kiện `VIEW`, `SHARE` chưa được xác minh có ghi nhận đầy đủ.
- Độc quyền (`Exclusive Deal`) riêng biệt với trạng thái Official chưa được xác nhận là một workflow độc lập.

### 2.3. ADMIN — theo `brief.md`

- Đã có routes cho dashboard, user lock/unlock, merchant review, Category thêm/bật tắt, Coupon duyệt/từ chối, Campaign, Report, Article.
- **Còn thiếu hoặc chưa thấy luồng quản trị đầy đủ**: sửa tên/xóa Category; Admin sửa/xóa/buộc hết hạn Coupon (so với `Approve / Reject / Edit / Delete / Expire` trong brief). Không mở rộng ở bản này vì phải xác minh yêu cầu xử lý trạng thái/vòng đời và script SQL cùng nhóm trước khi thay đổi dữ liệu.

### 2.4. Các tính năng không mặc định bắt buộc

Các mục ở *Future Development* như Smart Coupon Matching, Reward Marketplace hoàn chỉnh, AI recommendation không được đánh giá là thiếu ở phiên bản đầu. OAuth Google/GitHub trong README hiện ghi là giai đoạn chuẩn bị; không xem là đăng nhập thực tế đã hoàn thành.

## 3. Kiểm thử đã chạy

| Test | Kết quả |
|---|---|
| PHP syntax | **PASS** — 200 file PHP, 0 lỗi cú pháp |
| JavaScript syntax | **PASS** — 16 file JS, 0 lỗi cú pháp |
| `php tests/router-integration.php` | **PASS** — tồn tại handler, route matching |
| `php tests/session-guards.php` | **PASS** — session, role guard, CSRF, OAuth state (fake DB) |
| `php tests/account-service.php` | **PASS** — validation thông tin và chính sách mật khẩu (không DB) |
| `node tests/auth-ui.test.cjs` | **PASS** — 7 checks |
| `node tests/campaign-clock.test.cjs` | **PASS** — 4 checks |
| `node tests/hero-carousel.test.cjs` | **PASS** — 5 checks |
| `node tests/bootstrap5-contract.test.cjs` | **PASS** — 3 checks |
| PHP local HTTP smoke | `/login` 200, `/register` 200, `/about` 200, asset coupon events 200 |
| `/api/health` local | **503** — không có kết nối MySQL trong môi trường kiểm tra |
| `php tests/auth-registration.php` | **Không thể chạy** — CLI PHP tại đây thiếu `mysqli` extension (`mysqli_sql_exception` không được khai báo) |
| DB integration / HTTP full / visual regression | **Chưa chạy** — cần MySQL, dữ liệu thử và trình duyệt trên XAMPP |

**Quan trọng:** Các test tĩnh và route không thay thế được kiểm thử end-to-end. Không kết luận 100% chức năng đã hoàn chỉnh.

## 4. Checklist nghiệm thu trên XAMPP

1. Dùng **database riêng** cho bản thử; không import `schema.sql` hoặc `demo.sql` vào CSDL có dữ liệu.
2. Chạy `/api/health` và xác nhận JSON `success=true` trước khi test tính năng có DB.
3. Kiểm tra nút modal báo cáo coupon, modal bỏ lưu, xác nhận duyệt bằng chứng trùng, thông báo tự ẩn và input-group trên desktop/mobile.
4. USER: sửa profile, thử sai mật khẩu hiện tại, đổi mật khẩu thành công, đăng nhập lại, thử email không sửa được, kiểm tra truy cập trái quyền.
5. MERCHANT: đăng coupon, chỉnh sửa/ngừng, gắn campaign; copy coupon từ trang chủ và trang chi tiết → sau đó đối chiếu `coupon_events`/dashboard.
6. ADMIN: kiểm duyệt, quản lý Merchant, category, campaign, report và article; xác minh các nghiệp vụ còn thiếu nêu trên với nhóm.
7. Bật DevTools → Network: xem POST `api/coupons/{id}/events` (request/response JSON), kiểm tra CSRF và không tăng thống kê khi nhấn copy quá nhanh.
8. Kiểm tra tiêu chí 5.3.8 trong tab Network, tránh tải đồng thời Bootstrap 4 và 5.

## 5. Cách áp dụng bản sửa an toàn

**Không chép ZIP đè lên nhánh `main` trực tiếp.** Tạo branch mới từ `main` đã cập nhật trên máy và chạy `git apply --check` với file patch đi kèm, sau đó `git apply`; kiểm tra bằng `git diff` và chạy test trên XAMPP. Review rồi mở PR cho nhóm.

Một bộ lệnh ví dụ trong Git Bash (đường dẫn patch phải thay bằng vị trí lưu thực tế):

```bash
git fetch origin
git switch main
git pull --ff-only origin main
git switch -c feature/couponhub-bootstrap53-account-events
git apply --check /c/duong-dan/CouponHub_changes.patch
git apply /c/duong-dan/CouponHub_changes.patch
php tests/router-integration.php
php tests/session-guards.php
php tests/account-service.php
node tests/bootstrap5-contract.test.cjs
git status
git diff --check
```

**Khuyến nghị:** trước khi merge, cần kết quả kiểm thử bằng chính môi trường PHP/MySQL/XAMPP của nhóm, đặc biệt các endpoint mới liên quan quyền tài khoản và thống kê Merchant.

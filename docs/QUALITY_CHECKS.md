# CouponHub — kiểm thử và CI

## CI cho Pull Request

`.github/workflows/quality.yml` chạy PHP lint, Node.js tests và các PHP regression tests **không cần MySQL** ở mọi Pull Request, cũng như khi push lên `main`/`Phuc`/`Quang`.

- PHP 8.3 với extensions `mysqli`, `mbstring`, `openssl`.
- Node.js 22.
- `node --test tests/*.test.cjs`.
- `tests/public-header-notifications-phase6.php`: test với fake Repository, không cần DB.
- `tests/coupon-interaction-errors-phase4.php` và `tests/oauth-setup-access-phase5.php`: kiểm tra hợp đồng HTTP status không cần DB.

CI này **không chứng minh** toàn bộ chức năng và database đã chạy đúng. Không dùng `config/local.php` hoặc `.env` có secret trong CI.

Kết quả dọn source 09-10/11/2026, gồm kiểm thử MariaDB riêng và Postman CLI, được ghi tại [CODE_CLEANUP_20261110.md](CODE_CLEANUP_20261110.md). Việc này không thay thế nghiệm thu provider hoặc hosting HTTPS thật.

## Kiểm thử tích hợp trên XAMPP trước khi merge

1. Dùng DB kiểm thử riêng phù hợp với schema và stored procedures hiện tại; **không chạy các script seed có thể ghi/xóa dữ liệu trên DB đang sử dụng**.
2. Chạy các bài PHP/HTTP tích hợp theo README và hướng dẫn trong `tests/`; rà soát điều kiện tiên quyết từng script trước khi chạy.
3. Kiểm tra thủ công các vai trò Guest/User/Merchant/Admin, trang chủ, danh mục, coupon, thông báo, dấu đã đọc, login/logout và OAuth setup.
4. Kiểm tra console/network và responsive tại desktop, tablet, mobile.
5. Nếu thay đổi luồng notification, kiểm tra đồng thời badge chưa đọc, trang `/notifications`, POST `/notifications/read`, sự kiện cho Admin và User.

**Chưa bật tự động MySQL integration trong workflow**, vì việc dựng schema/routines và dữ liệu fixture phải được chuẩn hóa trước; cần thêm job có database service trong đợt sau.

## Nghiệm thu chung đợt 4

Xem [PHASE4_ACCEPTANCE.md](PHASE4_ACCEPTANCE.md) để biết ma trận U4/A4/M4, bằng chứng đã chạy và các bước OAuth/email/host thật còn chờ. `tests/run-phase4.py --browser` bổ sung Chrome headless tại 320/390/768/1440 px và xử lý mất mạng; chỉ chạy khi đã opt-in database thử riêng cổng 13379. Bỏ `--browser` không bao gồm kiểm tra trình duyệt này.

`COUPONHUB_FULL_UI_AUDIT=1` mở rộng kiểm tra trình duyệt sang các trang công khai, User, Merchant và Admin; kiểm tra thêm category dialog, chuông thông báo mobile, menu workspace, reduced-motion và Escape/focus. Bản review Trúc mới và cách dùng các skill thiết kế được ghi ở [TRUC_APP_REVIEW_AND_DESIGN.md](TRUC_APP_REVIEW_AND_DESIGN.md).

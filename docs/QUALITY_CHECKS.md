# CouponHub — kiểm thử và CI

## CI cho Pull Request

`.github/workflows/quality.yml` chạy PHP lint, Node.js tests và các PHP regression tests **không cần MySQL** ở mọi Pull Request, cũng như khi push lên `main`/`Quang`.

- PHP 8.3 với extensions `mysqli`, `mbstring`, `openssl`.
- Node.js 22.
- `node --test tests/*.test.cjs`.
- `tests/public-header-notifications-phase6.php`: test với fake Repository, không cần DB.
- `tests/coupon-interaction-errors-phase4.php` và `tests/oauth-setup-access-phase5.php`: kiểm tra hợp đồng HTTP status không cần DB.

CI này **không chứng minh** toàn bộ chức năng và database đã chạy đúng. Không dùng `config/local.php` hoặc `.env` có secret trong CI.

## Kiểm thử tích hợp trên XAMPP trước khi merge

1. Dùng DB kiểm thử riêng phù hợp với schema và stored procedures hiện tại; **không chạy các script seed có thể ghi/xóa dữ liệu trên DB đang sử dụng**.
2. Chạy các bài PHP/HTTP tích hợp theo README và hướng dẫn trong `tests/`; rà soát điều kiện tiên quyết từng script trước khi chạy.
3. Kiểm tra thủ công các vai trò Guest/User/Merchant/Admin, trang chủ, danh mục, coupon, thông báo, dấu đã đọc, login/logout và OAuth setup.
4. Kiểm tra console/network và responsive tại desktop, tablet, mobile.
5. Nếu thay đổi luồng notification, kiểm tra đồng thời badge chưa đọc, trang `/notifications`, POST `/notifications/read`, sự kiện cho Admin và User.

**Chưa bật tự động MySQL integration trong workflow**, vì việc dựng schema/routines và dữ liệu fixture phải được chuẩn hóa trước; cần thêm job có database service trong đợt sau.

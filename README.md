# CouponHub

Ứng dụng web PHP OOP theo MVC, dùng MySQL/MariaDB qua `mysqli`. Một front controller và một router phục vụ khách, User, Merchant và Admin.

## Bắt đầu

1. Clone source đúng commit được nhóm chốt.
2. Nếu chưa có `.env`, copy `.env.example` rồi điền cấu hình riêng. Không chép đè `.env` đang hoạt động.
3. Chuẩn bị database theo [database/README.md](database/README.md). Database đã có dữ liệu chỉ nâng cấp theo migration phù hợp, không import lại schema/demo.
4. Tại thư mục này, chạy:

```bash
php -S localhost:8000 router.php
```

Mở `http://localhost:8000/`. Không chạy PHP server thiếu `router.php` khi đã có secret trong `.env`.

## Môi trường và dependency

- PHP 8.3 khớp CI; bật `mysqli` với mysqlnd, `mbstring`, `curl`, `openssl`.
- MySQL/MariaDB tương thích schema, view và stored procedure của dự án.
- Không có bước `npm install` hoặc `composer install`: repo hiện không có package manifest tương ứng.
- Font Awesome và Swagger UI nằm sẵn trong `assets/vendor/`, bao gồm giấy phép và webfont.
- Bootstrap được tải qua CDN. Node và Python phục vụ kiểm thử/công cụ phát triển, không phải runtime bắt buộc của hosting.

## Tài liệu đang dùng

| Nội dung | Tài liệu |
|---|---|
| Kiến trúc và trách nhiệm từng tầng | [ARCHITECTURE.md](docs/ARCHITECTURE.md) |
| Cài mới, nâng cấp database | [database/README.md](database/README.md) |
| Hướng dẫn clone và deploy cho nhóm | [DEPLOYMENT.md](docs/DEPLOYMENT.md) |
| OAuth, email, Turnstile, nguồn coupon | [PROVIDER_SETUP.md](docs/PROVIDER_SETUP.md) |
| Comment và vị trí đặt code mới | [CODE_CONVENTIONS.md](docs/CODE_CONVENTIONS.md) |
| Kiểm thử và giới hạn nghiệm thu | [QUALITY_CHECKS.md](docs/QUALITY_CHECKS.md) |
| Danh mục tài liệu hiện hành/lịch sử | [docs/README.md](docs/README.md) |

## Cấu trúc chính

```text
index.php           Front controller
api/                Wrapper tương thích URL PHP cũ
app/                Core, controller, service, repository, model, view, nội dung biên tập
config/             Cấu hình và route
assets/             CSS, JS, ảnh, thư viện đã đóng gói
public/             Asset của các module tích hợp; không phải document root
database/           Schema cài mới, demo riêng và migration
storage/            Cache, log và dữ liệu runtime được bảo vệ
scripts/            Công cụ CLI có chủ đích
tests/              Kiểm thử; test ghi DB yêu cầu database riêng
docs/               Tài liệu kỹ thuật
Latex/              Source báo cáo, sơ đồ và PDF hoàn chỉnh
```

`admin.php`, `shop.php`, `login.php`, các callback trong `api/` là wrapper cần giữ để tương thích URL. Chúng không tạo một backend thứ hai. Thư mục gốc chứa `index.php` là gốc ứng dụng; áp dụng `.htaccess` khi chạy Apache/LiteSpeed.

Các template User cũ dùng mảng dữ liệu và asset tương ứng đã được lưu ngoài repo trong đợt dọn 09-10/11/2026. Chức năng hiện tại lấy dữ liệu qua controller/repository và database; không truy cập trực tiếp `app/views/*.php`.

## Kiểm tra nhanh

```bash
node --test tests/*.test.cjs
php scripts/check_auth_config.php --db --facebook
php scripts/check_integrations.php --database
```

Hai lệnh PHP chỉ kiểm tra cấu hình/schema và không gửi email hoặc chứng minh API key hoạt động. Chỉ chạy test thay đổi dữ liệu với database tách biệt theo hướng dẫn trong từng runner.

## Thành viên

- Trương Ứng Quang: Merchant/Shop và cơ sở dữ liệu.
- Lữ Võ Hoàng Phúc: Admin, xác thực và tích hợp API.
- Từ Trương Thanh Trúc: User và UI/UX.

Phân công trên là phạm vi phụ trách; lịch sử commit/PR là minh chứng đóng góp thực tế. Không đưa `.env`, SQL dữ liệu thật, session hoặc backup vào Git.

# Kiểm thử CouponHub

Thư mục này chứa test được duy trì cho ứng dụng, không phải log hay bản thử giao diện. Kết quả chạy, cookie và dữ liệu runtime phải nằm ở `.runtime/`, không commit vào đây.

## Không cần database

```bash
node --test tests/*.test.cjs
php tests/router-integration.php
php tests/provider-integrations.php
php tests/google-one-tap.php
php tests/facebook-google-link.php
php tests/password-reset-email.php
```

Danh sách PHP regression đầy đủ nằm trong `.github/workflows/quality.yml`. Một số test dùng repository/HTTP transport được kiểm soát để kiểm tra logic lỗi, trạng thái và bảo mật; chúng không xác nhận tài khoản provider thật.

## Có thay đổi dữ liệu

Các bài `*-integration.php`, test HTTP, concurrency và Postman chỉ chạy theo guard của từng file trên DB dùng một lần `127.0.0.1:13379/couponhub_merge_test`. Đọc điều kiện trước khi bật `COUPONHUB_RUN_DB_TEST=1`; một số runner xóa và tạo lại fixture trong DB test này.

- `sql-install.py`: schema, demo riêng và guard chống import chồng dữ liệu.
- `quang-integration.php`, `truc-integration.php`: Merchant và User.
- `phase3-integration.php`, `phase4-integration.php`: ràng buộc nghiệp vụ, quyền và thu hồi.
- `oauth-unified-accounts.php`: liên kết provider về một tài khoản, xung đột và dữ liệu được giữ.
- `external-offers-database.php`, `external-source-http.py`: nhập nguồn, kiểm duyệt và thu hồi.
- `article-comments-http.py`, `article-comments-concurrency.php`: bình luận lưu thật và chống gửi trùng.
- `postman-acceptance.py`: collection nội bộ, không dùng collection để ghi vào production.

Không đổi các guard sang DB đang dùng. Không dùng `.env` production hoặc `config/local.php` cá nhân cho runner tích hợp. Các test trình duyệt là công cụ riêng và không được tự coi là đã chạy chỉ vì test Node/PHP thành công.

Nghiệm thu thực tế gần nhất: [báo cáo dọn source](../docs/CODE_CLEANUP_20261110.md). Hướng dẫn hosting: [DEPLOYMENT.md](../docs/DEPLOYMENT.md).

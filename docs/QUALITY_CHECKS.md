# Kiểm chứng CouponHub

## Source web trên GitHub

Repo chỉ giữ mã ứng dụng, asset, SQL và tài liệu kỹ thuật. Toàn bộ `tests/`, script Python/Shell/PowerShell và benchmark CJS đã được chuyển sang bộ `CouponHub_DevTools` riêng, cùng manifest/checksum. Không xóa mất các bộ kiểm thử.

`.github/workflows/quality.yml` hiện chỉ kiểm tra cú pháp PHP và JavaScript, cùng điều kiện không đưa công cụ hỗ trợ trở lại repo. CI này không chạy regression hoặc database test và không chứng minh chức năng đã nghiệm thu.

## Bộ DevTools riêng

Bộ local nằm tại `../CouponHub_DevTools/` tính từ gốc source web. Đây là bộ công cụ bàn giao riêng, không tự có khi clone repo. `verify.py --project PATH --unit` tạo một workspace kiểm chứng tách biệt, ghép source hiện tại với bộ test và script hỗ trợ rồi chạy regression tại đó; không chép test ngược vào repo web.

Các runner MySQL/HTTP/Postman vẫn giữ guard `127.0.0.1:13379/couponhub_merge_test` và yêu cầu opt-in. Chúng chỉ được dùng trong môi trường test riêng, không dùng DB thật. Provider transport kiểm soát trong test không thay thế OAuth, email, Turnstile hoặc ACCESSTRADE thật.

## Bằng chứng trước khi tách

[CODE_CLEANUP_20261110.md](CODE_CLEANUP_20261110.md) ghi lại 151 test JS, 19 chương trình PHP, test MySQL/HTTP riêng và Postman 280 request/296 assertion đã chạy ở đợt trước. Không coi đây là lần chạy kiểm thử mới sau khi tách công cụ.

Trước khi triển khai cần nghiệm thu HTTPS, quyền các vai trò, dữ liệu và provider theo [DEPLOYMENT.md](DEPLOYMENT.md).

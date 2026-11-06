# Nghiệm thu API và nghiệp vụ — 06/11/2026

**Đạt kiểm định local trong phạm vi chức năng hiện có. Chưa nghiệm thu đăng nhập/gửi mail/coupon với tài khoản nhà cung cấp thật và host HTTPS production.**

Các phần mới giữ PHP/mysqli, HTML/CSS/JS, Bootstrap và session hiện có. Swagger UI là công cụ tài liệu API được phục vụ local, có phiên bản/giấy phép/hash tại `assets/vendor/swagger-ui/PROVENANCE.json`.

## Kết quả kiểm thử

| Lớp kiểm tra | Kết quả |
|---|---|
| Hồi quy ứng dụng đợt 3–4 | Qua các bộ PHP/service/repository/procedure/HTTP; 93 test JavaScript qua |
| Quyền admin | Toàn bộ 43 route operation admin chặn Guest/User/Merchant sai quyền; POST chặn CSRF sai, cả root và subfolder |
| Postman CLI 1.80.0 | **271 request, 287 assertion, 0 lỗi**; thêm kiểm tra checksum để xác nhận các lệnh bị từ chối không đổi nghiệp vụ |
| Hợp đồng provider bằng transport được tiêm vào | 25 kiểm tra Turnstile, ACCESSTRADE, Resend; không dùng khóa thật |
| Database nguồn coupon/thu hồi | 34 kiểm tra: nhập lặp, quyền actor, rollback, nguồn thay đổi khi mở form, thu hồi terminal, không tự khôi phục |
| HTTP nguồn coupon | 14 kiểm tra qua route thực: review → pending → duyệt → công khai → thu hồi → 404; kiểm tra cả form không JS và JSON |
| HTTP middleware Turnstile | 38 kiểm tra root/subfolder: markup, cấu hình, token thiếu/quá dài và CSRF; không gọi Cloudflare thật |
| Chrome | **176 lượt trang/kích thước**, 320/390/768/1440; user/merchant/admin/public và màn tích hợp; có dữ liệu nhiều thương hiệu trong DB kiểm thử |
| Tương tác trình duyệt | Swagger Execute POST thật có session/CSRF; lỗi mạng khi lưu coupon giữ nội dung, mở lại nút; form nhập giữ dữ liệu và focus lỗi; reduced motion, Escape và trả focus |
| Rate limit | Kiểm thử đồng thời 20 process: 7 được nhận, 13 bị từ chối đúng giới hạn |
| Kiểm tra mã | 225 file PHP và 4 file JS tích hợp/auth hợp lệ cú pháp; `git diff --check` không lỗi whitespace |
| HTTPS ra ngoài | PHP cURL GET thành công endpoint công khai Google signing keys/GitHub public API, giữ TLS verification; không phải OAuth login thật |

Chứng cứ local cuối: `.runtime/integrations-acceptance-1793902930199673400/results.json`. Kết quả hồi quy đợt 4 đã qua trong cùng đợt làm được tái sử dụng từ `.runtime/integrations-acceptance-1793901170508988900/phase4-regression.log`; các ca tích hợp, Postman và Chrome được chạy lại sau các sửa đổi tương ứng. Log/ảnh chi tiết nằm trong `.runtime`, không commit token/cookie kiểm thử. Chỉ database riêng `couponhub_merge_test` ở cổng 13379 bị tạo lại; fixture được khôi phục cuối đợt.

Các con số trên là độ bao phủ của các bộ kiểm tra này, không phải khẳng định mọi tổ hợp đầu vào hay mọi nhánh của 119 operation đã được thử hết. OpenAPI lấy toàn bộ method/path từ router; endpoint form HTML/redirect được phân biệt với JSON.

## Kết luận về vai trò và nghiệp vụ

| Phần | Đã kiểm tra trong code và bộ nghiệm thu |
|---|---|
| Tài khoản | Username/email login, đăng ký bỏ qua role giả từ client, đổi mật khẩu, OTP/reset dùng một lần, logout, TTL/idle và auth_version vô hiệu phiên cũ |
| OAuth | State một lần/hết hạn, PKCE Google/GitHub, Google signature/claims, danh tính/email đã xác minh, Facebook explicit link và kiểm tra token/app; không lưu token provider trong JS |
| User | Tìm/chi tiết/chia sẻ coupon, mã của mình, lưu/xác minh/bình luận/báo cáo/thông báo; không được gọi trung tâm admin/merchant bằng cách đổi link hoặc body |
| Merchant | Xin quyền/nhận quản lý shop, hồ sơ shop, official coupon, sửa/tắt, chiến dịch, gắn/gỡ coupon, điều kiện thời gian; kiểm tra membership và shop được duyệt, chặn thao tác shop khác |
| Admin | Duyệt/khóa user và shop, thu hồi membership, kiểm duyệt/sửa/hết hạn/thu hồi/xóa mềm coupon, danh mục, bài viết, báo cáo, chiến dịch và liên kết coupon; kiểm tra actor mới nhất trong transaction |
| Coupon thu hồi | `ARCHIVED` giữ ngày, người xử lý, lý do và lịch sử; mất khỏi public/search/campaign, mã đã lưu không còn nút lấy mã; merchant sửa/tắt hay admin duyệt lại không làm sống lại bản ghi |
| Chiến dịch | `DISABLED` tạm tắt, admin có thể chủ động mở lại; merchant không vượt quyết định admin. `ARCHIVED` kết thúc vĩnh viễn; không xuất bản lại. Không xuất bản campaign đã hết hạn hoặc chủ shop không còn hợp lệ |
| ACCESSTRADE | Nguồn thật qua hàng chờ MySQL, không tự mock; manual mapping/điều kiện, pending trước duyệt; khóa dòng, chống nhập lặp, kiểm fingerprint của bản review; đồng bộ không ghi đè coupon đã công khai/thu hồi |

Tạm tắt/lưu trữ **chiến dịch chỉ ẩn bộ sưu tập**, không tự thu hồi các coupon còn hợp lệ của nhiều shop ở nơi khác. Thu hồi coupon là nghiệp vụ riêng. Hết hạn chiến dịch và hết hạn coupon được kiểm tra riêng. Giữ lịch sử không đồng nghĩa xóa các giao dịch điểm đã ghi trước đó; không tự thêm chính sách trừ điểm hồi tố ngoài nghiệp vụ hiện có.

Ứng dụng dùng session cookie, không cần thêm access/refresh JWT riêng cho monolith PHP. JWT ID token của Google được kiểm trong luồng OIDC; không dùng nó thay cookie ứng dụng. Cookie HttpOnly/SameSite, Secure theo HTTPS; có CSRF, Origin/body guard và giới hạn gửi. Giới hạn bằng file/flock hiện phù hợp máy chủ đơn; host thực vẫn cần kiểm tra quyền thư mục/session, HTTPS/proxy và cấu hình triển khai.

## Các điểm đã sửa

- Thống nhất CSRF `csrf`/`_token` qua `Request::csrfToken()` và các controller cũ; giữ tương thích form cũ, client API dùng tên mới không bị từ chối nhầm.
- Kiểm tra trường phẳng cho POST của mọi role trước khi array bị cast thành ID/số/chuỗi. API tìm kiếm trả 422 với bộ lọc sai dạng.
- Admin chọn trạng thái campaign đang có, thay vì luôn mặc định DRAFT; phân biệt tạm tắt/lưu trữ và chặn khôi phục bản lưu trữ.
- Bộ đọc `.env` nhận đúng các biến ACCESSTRADE/Turnstile. Keys chỉ ở server; lỗi provider không trả raw response/secret; router không ghi exception arguments vào log.
- Hỗ trợ hai envelope ACCESSTRADE (`data[]`, `data.data[]`), date-only, GMT và Mongo extended JSON date trong tài liệu; không đoán số tiền từ quảng cáo.
- Widget Turnstile theo từng form, render khi popup hiện, reset sau gửi, có thông báo lỗi; server kiểm `success`/hostname/action.
- Màn nguồn có tình trạng cấu hình, hàng chờ, cảnh báo nguồn đổi, form review giữ dữ liệu và lỗi gần trường. Workspace admin phục vụ nghiệp vụ; có Swagger local và collection Postman để thử lại.
- Router local chặn `.env`, source/SQL/session/tài liệu nội bộ. Script chạy local dùng thư mục session/rate-limit riêng ngoài webroot.

## Database đang dùng và địa chỉ local

Đã backup riêng ra `%LOCALAPPDATA%\CouponHub\backups\dbcouponshubweb_v2-before-external-offers-20261106-012425.sql` (154590 byte), SHA-256 `28a9e10f67b74cf0a4ae656430ac9d08cdd888142abdb0d7aee99fb0be53db1b`. Manifest cạnh backup ghi checksum trước/sau.

Chỉ chạy migration `20261105_external_offers.sql`. **21 bảng cũ giữ nguyên số dòng và checksum**: 6 account, 14 shop, 40 coupon, 10 campaign. Bảng mới `external_offers` đang trống, chờ khóa thật. Không import lại schema/demo, không trộn fixture nghiệm thu vào DB đang dùng.

Local bảo vệ: `http://localhost:8080/CouponHub_UI/`. Smoke test đã xác nhận private paths trả 403, asset phục vụ đúng, admin login, nguồn trống sau migration và spec 119 operation đúng base URL. Server preview VSCode cũ ở cổng 8000 được quan sát trả trực tiếp `.env.example`/SQL: dừng preview đó trước khi lưu khóa thật. `.vscode/settings.json` đã chọn router khi mở trực tiếp folder CouponHub_UI; script `scripts/serve-local.ps1` dùng được độc lập với cách mở workspace.

## Phần cần khóa/tài khoản thật

Thực hiện theo [PROVIDER_SETUP.md](PROVIDER_SETUP.md): quyền Publisher ACCESSTRADE và sample response của tài khoản; hostname/widget Turnstile; client ID/secret/callback/tester của Google/GitHub/Meta; sender/domain và delivery Resend; kiểm tra Secure cookie/HTTPS trên host thật. Chưa gửi mail hay đăng nhập OAuth thật. Chưa có cron/webhook tự thu hồi coupon theo việc một offer biến mất khỏi feed; hàng chờ báo thay đổi và admin đối chiếu chủ động.

Lệnh tái nghiệm thu đầy đủ trên môi trường riêng đã chuẩn bị:

```powershell
$env:COUPONHUB_RUN_DB_TEST='1'
python tests/run-integrations.py --browser
```

Collection và environment mẫu: `docs/api/couponhub.postman_collection.json`, `docs/api/local.postman_environment.json`. Postman chạy local với `--no-report-events`, không cần đăng nhập để xuất JSON report local. Nút Execute của Swagger dùng phiên hiện tại và có thể ghi dữ liệu thật; dùng DB kiểm thử cho kịch bản phá trạng thái.

## Release verification after merging current main

Fetched `origin/main` at `963139c` and merged its security and asset-policy updates into the Phuc release. Conflict resolution preserves the current workspaces, conditional public assets, external CSP event handlers, current-policy expiry checks, and the ADMIN/MERCHANT 8-hour absolute / 30-minute idle caps even when environment overrides allow longer USER sessions. Added runtime coverage for both privileged roles and included the provider/security regression checks in GitHub Actions.

Re-ran the complete isolated phase 3/4, migration, provider, role/API, Postman and Chrome acceptance after integration; 128 JavaScript tests passed. This run does not reuse prior regression evidence. Local evidence: `.runtime/integrations-acceptance-1793966356676545500/results.json`. Real provider credentials and production HTTPS remain pending as described above.

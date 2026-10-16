# Báo cáo tích hợp thủ công nhánh Quang — 16/10/2026

> SQL đã được gom vào [database/schema.sql](../database/schema.sql). Các thư mục routines, migrations, quang-reference và hướng dẫn import nhiều bước bên dưới thuộc lịch sử, đã bỏ. Khi cài mới chỉ theo [database/README.md](../database/README.md); không thực hiện lại các bước SQL cũ.

> Đây là báo cáo của giai đoạn ghép nhánh. Sau đó dự án đã bỏ class alias, lớp chuyển tiếp controller và adapter SQL thừa. Vị trí class và kiến trúc đang dùng được cập nhật tại [ARCHITECTURE.md](ARCHITECTURE.md); phần mô tả kiến trúc bên dưới giữ làm lịch sử đối chiếu.

## Nguồn và cách bảo toàn công việc

- Đã fetch `origin`, lấy được `origin/Quang` tại `cc8a2d89eea2211ebc65ba383983762bd84954a1` và tạo nhánh local `Quang` tracking nhánh đó.
- Vẫn làm trên working tree `main` của Phúc. Không chạy `git merge`, checkout đè file, reset, stash, commit hoặc push.
- Trước khi chỉnh đã sao lưu file tracked/untracked hiện tại (không gồm `.git`, `vendor`, `node_modules`) ở `../../.integration/quang-20261016-083833/before-integration.zip`.
- Source Quang để đối chiếu nằm cùng thư mục backup trong `quang/`.
- Bản dump `coupon.sql` bạn bổ sung đã được đối chiếu khi ghép nhánh. Sau đợt dọn SQL, bản gốc được sao lưu ngoài repository; source cài đặt hiện tại là `database/schema.sql`, không copy dữ liệu thử/tài khoản trong dump.
- Nhánh Trúc được fetch cùng origin nhưng chưa tích hợp. Ảnh kế hoạch không được dùng để ghi đè logic code; Google + GitHub và mysqli là yêu cầu đã chốt.

## Những khác biệt đã xử lý

| Nội dung | Bản Phúc | Bản Quang | Sau tích hợp |
|---|---|---|---|
| Truy cập DB | mysqli + `ch_*` | PDO + repository/`sp_*` | Duy nhất mysqli; cả hai nhóm dùng một connection |
| Entry point | index/admin/shop/API PHP riêng | public/index + Router | `index.php` + `config/routes.php`; URL cũ là wrapper mỏng |
| Auth | account=email/username, modal, JSON | email-only, trang riêng | Giữ modal, username/phone và validation Phúc; hai URL dùng cùng controller/repository |
| Session | `user_id`, cookie COUPONHUBSESSID | `auth_user`, cookie khác | Một `Session`, cùng cookie/CSRF/timeout/logout |
| Trạng thái user | Khóa/xóa/role | Thêm auth_version | Gộp kiểm tra trạng thái và auth_version; lỗi đọc quyền không giữ quyền cũ |
| Quyền shop | primary owner | membership nhiều–nhiều | Membership ACTIVE + shop APPROVED, độc lập primary owner |
| Schema | `tbl_*` | bảng không prefix | Chọn schema theo dump bạn cung cấp; SQL hướng dẫn chuyển riêng |
| Thời gian | Một số routine/field theo giờ VN | UTC | UTC trong DB; UI/input chuyển múi giờ rõ ràng |
| OAuth | Google/GitHub preparation | Chưa có luồng OAuth tương ứng | Giữ Google/GitHub, không đưa Facebook vào Auth |

## Kiến trúc để nhóm tiếp tục phát triển

```text
Request
  → index.php / bootstrap.php
  → Router (method, route, CSRF, guard)
  → Controller
  → Service khi có nghiệp vụ cần tách
  → Repository interface
  → Repository implementation
  → SqlConnectionInterface / RoutineGateway
  → mysqli prepared statement / stored procedure
  → View hoặc JSON
```

### Lớp nền

- `app/core/Controller.php` là abstract base cho module Quang. `AbstractController.php` hiện tại kế thừa lớp đó để giữ `handle()`/render của các trang Phúc; chúng cùng một cây kế thừa.
- `app/core/Request.php` dùng chung, hỗ trợ cách gọi cũ và route/JSON mới.
- Namespace `App\Core\Database`, `Session`, `Request` là **class alias của chính lớp đang dùng**, không phải bản sao ba lớp đó. Alias giúp ghép module Quang mà không phải đổi toàn bộ code Phúc sang namespace ngay.
- `app/core/Database.php` giữ allowlist `RoutineGateway`, đồng thời cung cấp connection chung cho repository.
- `MysqliConnection.php` thực hiện prepare/query/transaction; `MysqliStatement.php` bind dữ liệu và buffer/xả các result sets của CALL, tránh `Commands out of sync`.
- `app/contracts/SqlConnectionInterface.php`, `SqlStatementInterface.php` tách repository khỏi driver cụ thể. Implementation hiện chỉ là mysqli.

### Repository và model

- `app/repositories/*Repository.php`: truy cập dữ liệu các module từ Quang, đã chuyển bỏ PDO.
- `app/contracts/*DataRepositoryInterface.php`: hợp đồng public của các repository; service phụ thuộc interface thay vì implementation.
- `UserDataRepositoryInterface` mở rộng `UserRepositoryInterface` hiện tại. `UserController` vẫn kiểm tra đăng nhập/đăng ký như trước.
- `app/models/User.php`, `Coupon.php` giữ adapter model/routine đang có để code cũ và ví dụ đã học tiếp tục đọc được. Không có một connection khác cho chúng.
- `UserRepository::findByLogin/create` dùng `ch_find_user_by_login/ch_create_user`; API và Auth route dùng đúng cùng repository này.
- DTO `Category`, `Merchant` của Quang được giữ ở models. Không thay model User/Coupon đang có bằng DTO khác chức năng.

### Auth và guard

- `AuthController` chỉ nhận Request, gọi `UserController` chung, trả View/JSON/redirect; đã bỏ luồng đăng ký yếu hơn và bộ trang login/register trùng của Quang.
- `app/views/auth/page.php` chứa trang Auth trước đây nằm ở root login.php. Bốn partial modal/form và JS/CSS hiện tại giữ nguyên giao diện.
- `Auth` của module Quang chỉ là facade gọi Session hiện tại; không tạo `auth_user` thứ hai, không nuốt lỗi DB rồi tin role session cũ.
- Session mới lưu thêm `auth_version`. Đổi mật khẩu bằng reset token tăng version, phiên đăng nhập cũ bị loại ở request tiếp theo.
- Guest vào Admin/Merchant được guard kiểm tra trước constructor có DB. GET HTML chuyển tới Auth, API trả 401; sai role trả 403.
- Mọi route ghi có CSRF và kiểm tra nguồn request. Field cũ `csrf` và field Quang `_token` cùng một token.
- Membership được kiểm tra ở PHP và ở routine/transaction có liên quan; sửa merchant_id không cấp quyền shop khác.

## Các module đã ghép vào ứng dụng

- Giữ catalog/ước tính/UI Phúc ở `/` và `index.php`; thêm đường vào Coupon cộng đồng.
- Giữ trang shop tạo/theo dõi coupon cũ tại `shop.php`; thêm Merchant Center nhiều shop tại `/merchant`.
- Giữ hàng chờ duyệt cũ tại `admin.php`; các mục dashboard/categories/merchants/campaigns/reports từ link cũ chuyển sang trang chức năng của Quang.
- Ghép các route Coupon/tìm kiếm/chi tiết, saved coupons, tương tác vote/favorite/comment/report, Campaign, Merchant và quản trị liên quan.
- Ghép dashboard tài khoản/điểm, Article, trang thông tin và lịch campaign của Quang.
- Ghép backend/view reset mật khẩu dạng route từ Quang, cùng users/session/SMTP. Quy tắc mật khẩu reset được đồng bộ với đăng ký hiện tại.
- **Modal quên mật khẩu cũ vẫn là bản preview** như trước. Backend dạng trang `/forgot-password` và `/reset-password` đã được đưa vào, nhưng chưa đổi JS preview của modal thành gửi email thật. Không báo đã hoàn tất phần quên mật khẩu trên UI cũ.
- Công cụ OAuth và callback đi qua router chung; vẫn chỉ preparation, không lấy token/profile hay đăng nhập OAuth thật.

Các lớp không dùng/không khớp API từ nhánh Quang như AccountController và luồng AuthService/RegistrationService riêng không được giữ thành một đường Auth khác.

## URL/hợp đồng chung

| Chức năng | URL tiếp tục dùng | Bộ xử lý |
|---|---|---|
| Trang đăng nhập | GET `/login`, `/login.php` | AuthController.showLogin |
| Trang đăng ký | GET `/register` | Cùng trang/modal Auth, mở view register |
| Đăng nhập JSON | POST `/api/login.php` | AuthController.login → UserController.login |
| Đăng ký JSON | POST `/api/register.php` | AuthController.register → UserController.register |
| Form route | POST `/login`, `/register` | Cùng bộ xử lý; JSON khi Accept/API, redirect khi form HTML |
| Logout | POST `/logout`, `/logout.php` | Xóa chung session/cookie, redirect 303 |
| Admin | `/admin`, `/admin/users`, `/admin/merchant-requests`, … | Guard ADMIN + controller quản trị |
| Merchant | `/merchant`, `/merchant/coupons`, `/merchant/campaigns`, … | Guard MERCHANT + quyền shop |

JSON giữ `success`, `message`, thêm `errors` theo name input và `redirect` khi cần. Login thành công 200, register thành công 201, dữ liệu/credentials không hợp lệ 422, thiếu phiên 401, CSRF/sai quyền 403, sai method 405 kèm Allow, không có route 404. API lỗi hạ tầng trả thông báo chung, không đưa chi tiết kết nối DB ra trình duyệt.

URL cũ vẫn hoạt động nhưng logic không còn nằm trong các wrapper. Code xử lý ở controllers; xem `config/routes.php` trước khi thêm endpoint mới.

## SQL và các điểm cần bạn làm

Đợt dọn SQL đã gom toàn bộ cấu trúc và nghiệp vụ vào **`database/schema.sql`**: 21 bảng, 6 view và 21 procedure. Chỉ import file này khi cài database mới, trống. Database `dbcouponshubweb_v2` đang hoạt động không cần import lại.

Xem [hướng dẫn cài](../database/README.md) và [quy ước dành cho Quang/cả nhóm](../database/RULES.md). Các thư mục SQL tham chiếu và migration cũ đã bỏ khỏi repository sau khi sao lưu.

Các file cài đặt không tự chọn tên database của máy Quang bằng USE/ALTER DATABASE hardcode. Cấu hình chỉ có một nơi; local.php/env của bạn không bị ghi đè. Database cũ `tbl_*` chưa được di chuyển dữ liệu tự động.

## Kiểm chứng đã thực hiện

- Kiểm tra cú pháp toàn bộ 159 file PHP và 9 file JavaScript đều đạt.
- Phản chiếu toàn bộ **92 route**: class/action tồn tại và dùng common abstract controller.
- Kiểm tra match tham số route, dot literal, method/CSRF, redirect nội bộ, role và guest guard.
- Test đăng ký/đăng nhập/session cũ và 6 test JS Auth tiếp tục đạt.
- Tạo MariaDB **cô lập ở thư mục tạm, port 13379**, không dùng data directory/port DB của bạn. Import toàn bộ bộ SQL tích hợp thành công.
- Test mysqli thật: bind Unicode/null/int, CALL nhiều result sets, tiếp tục query sau lỗi business, transaction rollback, đăng ký/duplicate/hash, login email/username, chia sẻ phiên giữa hai module.
- Test transaction cấp/thu hồi membership, người quản lý không phải primary owner, tạo coupon từ form cũ, duyệt, tìm kiếm từ cả hai bên, UTC và hotDeals.
- Test favorite/vote/comment/report, chặn manager vote shop mình, saved-coupon pagination với LIMIT/OFFSET bind qua mysqli.
- Test reset token một lần, đổi mật khẩu, auth_version vô hiệu phiên cũ; render các trang Admin với DB thử thật.
- HTTP thật: trang công khai, UI Auth, chi tiết coupon, trang User/Admin/Merchant, guest redirect, sai role, CSRF/method/404, chặn đọc app/config/SQL, OAuth probe/replay và logout dùng chung.

Không gửi email thật, không test OAuth credentials thật, không test trực quan trên trình duyệt/điện thoại và không import/ghi dữ liệu vào database đang dùng của bạn. Không khẳng định đã kiểm thử hết mọi nhánh nghiệp vụ của các module Quang.

Lệnh kiểm tra không cần DB:

```powershell
php scripts/check_routes.php
php tests/router-integration.php
php tests/auth-registration.php
php tests/session-guards.php
node --test tests/auth-ui.test.cjs
```

`tests/mysqli-integration.php` có chốt chỉ cho database thử riêng `couponhub_merge_test`/port 13379 và env `COUPONHUB_RUN_DB_TEST=1`; không chạy bằng cấu hình DB thật. Cần DB trống đã import SQL; test tự tạo dữ liệu thử.

`tests/http-integration.py` chạy bộ HTTP nói trên khi đặt `COUPONHUB_RUN_HTTP_TEST=1`; chỉ trỏ tới server thử `127.0.0.1:8076` đã dùng bộ dữ liệu của test mysqli. Không trỏ server này vào database thật.

## Việc chưa thuộc lần tích hợp này

Nhánh Trúc, di chuyển dữ liệu `tbl_*` thật, đổi giao diện các trang mới của Quang theo design system của Phúc, hoàn thiện OAuth token exchange/linking, cấu hình SMTP thật và nối modal quên mật khẩu vào backend. Ảnh kế hoạch không tự tạo thêm bảng platform hay luật thưởng ngoài code/SQL đã đối chiếu.

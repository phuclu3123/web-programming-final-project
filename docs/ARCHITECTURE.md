# Kiến trúc CouponHub sau khi sắp xếp — 16/10/2026

## 1. Cách tổ chức đã chọn

Ứng dụng PHP OOP theo MVC, chạy trong **một ứng dụng**, dùng **mysqli**. Chỉ tách service khi có nghiệp vụ cần xử lý; repository chịu trách nhiệm đọc/ghi dữ liệu. Không cần container DI, microservice hoặc thêm framework cho quy mô hiện tại.

```text
CouponHub_UI/
├── index.php                  Điểm vào xử lý request
├── router.php                 Router dành cho PHP built-in server
├── .htaccess                  Rewrite và bảo vệ source trên Apache
├── api/                       URL PHP mỏng, chuyển tới index.php
├── app/
│   ├── bootstrap.php          Autoload App, nạp helpers và cấu hình
│   ├── helpers.php            Escape HTML, URL, định dạng tiền/ngày
│   ├── core/                  Router, Request, Response, Controller, Auth, Session, Database
│   ├── contracts/             Các interface repository và RoutineGateway
│   ├── controllers/           Action nhận Request, gọi service/repository, trả View/JSON
│   │   └── admin/             Các controller quản trị
│   ├── services/              Nghiệp vụ Auth, coupon, campaign, merchant, tương tác…
│   ├── repositories/          SQL/prepared statement và lời gọi stored procedure
│   ├── models/                Đối tượng dữ liệu Category, Merchant
│   └── views/                 PHP template, layout và partial giao diện
├── config/                    App, database, mail, OAuth và routes
├── database/                  schema.sql cấu trúc, demo.sql dữ liệu thử, README và RULES
├── assets/                    CSS/JS/icon của giao diện hiện tại
├── public/                    Asset của các module tích hợp
├── storage/                   Dữ liệu runtime của ứng dụng
├── tests/                     Kiểm tra Auth, guard, Router, JS, mysqli và HTTP
├── scripts/                   Công cụ CLI kiểm tra route
└── docs/                      Tài liệu dự án
```

`assets/` và `public/` tiếp tục phục vụ các giao diện đang có để giữ nguyên đường dẫn CSS/JS. `public/index.php` chỉ chuyển về URL gốc; document root hiện tại vẫn là thư mục dự án, theo README. Cả Apache lẫn built-in server phải chặn truy cập trực tiếp vào source/config/SQL.

Trang chủ và các trang trong layout `main.php` dùng chung `partials/public-header.php`, `public-footer.php` và `account-menu.php`. Chỉnh điều hướng/tài khoản tại các partial này để tránh sinh thêm navbar khác nhau. `assets/css/member.css` dùng token của trang chủ cho phần User; `assets/js/member.js` xử lý menu và điều hướng cuộn. Các view module cũ vẫn giữ Bootstrap 4 để tương thích form/modal hiện tại, trang chủ dùng Bootstrap 5; không nạp cả hai phiên bản trong cùng trang. Menu tài khoản dùng native `details`, không phụ thuộc dropdown của Bootstrap.

Trang tài khoản lấy thông tin, điểm và ba coupon đã lưu gần nhất từ repository theo ID của actor đã xác thực. View không truy vấn DB. Thanh `section-nav.php` chỉ điều hướng đến section có trong trang; JS lấy lại section sau khi bộ lọc trang chủ thay vùng kết quả. Trên màn hình nhỏ, các phần tài khoản dùng liên kết cuộn ngang và header có menu điều hướng thu gọn.

## 2. Luồng chạy thực tế

Phần khám phá bổ sung từ ý tưởng nhánh Trúc dùng `CategoryController` và `CampaignController` cho `/categories`, `/campaigns`. Các trang này đọc repository, dùng layout/header/footer chung; CSS bổ sung ở `assets/css/discovery.css`. Nút lưu trên trang chủ đi qua `SavedCouponController::save` → `InteractionService`, cùng quyền và CSRF với tương tác coupon. `assets/js/discovery.js` chỉ cập nhật trạng thái sau khi backend xác nhận và tạo gợi ý từ kết quả tìm kiếm vừa tải; không có kho dữ liệu tài khoản trong localStorage. Chi tiết lựa chọn và kiểm thử nằm ở [INTEGRATION_TRUC.md](INTEGRATION_TRUC.md).

```text
Trình duyệt / fetch
    → index.php → bootstrap.php
    → Router + config/routes.php
      (kiểm tra method, quyền truy cập và CSRF)
    → Controller::action(Request)
    → Service nếu có nghiệp vụ
    → Repository qua interface
    → Database / Statement → mysqli → MySQL
    → Controller trả View, JSON hoặc redirect
```

**API là cách nhận/trả HTTP**, không phải một tầng nghiệp vụ thứ hai. `api/login.php` và `api/register.php` chỉ chuyển request về `index.php`; Router chọn action của `AuthController`. Không viết thêm xử lý đăng nhập trong file API.

Controller đơn giản có thể gọi repository trực tiếp để lấy dữ liệu hiển thị. Với thao tác có nhiều bước kiểm tra, transaction hoặc quy tắc nghiệp vụ, dùng service. Không tạo service chỉ để chuyển tiếp một lời gọi.

## 3. Trách nhiệm của từng phần

| Phần | Làm gì | Không đặt tại đây |
|---|---|---|
| View | Hiển thị HTML, nhúng partial, escape dữ liệu bằng `e()` | SQL, kiểm tra mật khẩu, quyết định quyền truy cập |
| JS | Sự kiện UI, kiểm tra sơ bộ, fetch, hiển thị thông báo | Quyết định user có quyền gì hoặc đã đăng nhập thật chưa |
| Controller | Đọc Request, gọi nghiệp vụ, chọn response/status/view | Kết nối MySQL mới, SQL dài hoặc gọi một controller khác |
| Service | Validation nghiệp vụ và phối hợp repository | HTML/JSON/header/redirect |
| Repository | SELECT/INSERT/UPDATE, prepared statement, gọi procedure | Đọc `$_POST`, render view hoặc xuất JSON |
| Model/DTO | Đại diện dữ liệu khi cần một đối tượng có cấu trúc | Nhúng file giao diện |
| Core | Cơ chế HTTP, phiên, phân quyền và kết nối dùng chung | Quy tắc riêng của từng màn hình |

Trong mô hình MVC, “Model” là phần dữ liệu/nghiệp vụ nói chung, không chỉ riêng thư mục `models/`. Repository và service cũng thuộc phần này. Không tạo lại `models/User.php` để bọc `UserRepository` nếu không có trách nhiệm mới.

## 4. Các file Auth cần nhớ

| File | Vai trò |
|---|---|
| `app/views/auth/*-form.php` | HTML của đăng nhập, đăng ký và quên mật khẩu |
| `app/views/auth/auth-modal.php` | Khung modal và nhúng các form |
| `assets/js/auth/auth.js` | Chuyển form, validation phía trình duyệt, fetch và cập nhật UI |
| `config/routes.php` | Gắn URL/method với action của controller |
| `app/controllers/AuthController.php` | Action login/register/logout và các trang Auth |
| `app/services/AuthService.php` | Nghiệp vụ login/register trước đây nằm trong `UserController.php` |
| `app/contracts/UserRepositoryInterface.php` | Hai thao tác Auth cần: `findByLogin()` và `create()` |
| `app/contracts/UserProfileRepositoryInterface.php` | Mở rộng thêm truy vấn hồ sơ/điểm dùng bởi các module tài khoản |
| `app/repositories/UserRepository.php` | Một implementation chung cho hai hợp đồng trên |
| `app/core/Session.php` | Mở/đổi/hủy phiên, timeout, dữ liệu phiên, flash và CSRF |
| `app/core/Auth.php` | Đọc lại tài khoản từ DB, kiểm tra role/owner, chọn URL quay về an toàn |

Ví dụ đăng nhập:

1. `new FormData(form)` lấy các ô có `name`, gửi POST tới `api/login.php`.
2. Router kiểm tra CSRF và chọn `AuthController::login()`.
3. Controller đọc `account`, `password`, `return_url`, gọi `AuthService::login()`.
4. Service gọi `UserRepositoryInterface::findByLogin()`; implementation dùng procedure `ch_find_user_by_login`.
5. Service kiểm tra `password_verify`, trạng thái và role; thành công thì tạo session và chọn URL nội bộ hợp lệ.
6. Controller trả JSON bằng `Response::json()`. JS nhận kết quả và chuyển trang; request tiếp theo dùng cookie session để xác thực.

Các kiểm tra phía server vẫn bắt buộc dù JS đã kiểm tra dữ liệu.

## 5. Database và repository

- Chỉ `App\Core\Database` sở hữu kết nối mysqli; dùng `Database::connection()` để lấy kết nối chung trong request.
- Collation văn bản thống nhất `utf8mb4_vietnamese_ci` cho database, bảng, view, routine và kết nối. Các mã token/hash/ID provider/request key dùng `VARBINARY`, không áp dụng collation văn bản.
- `Statement` bind tham số và đọc hết các tập kết quả của `CALL`, tránh lỗi kết nối bận khi truy vấn tiếp.
- `RoutineGateway` là hợp đồng hẹp cho phần gọi procedure, cũng giúp kiểm tra guard bằng dữ liệu giả.
- Repository nhận kết nối qua constructor. Service nhận repository qua interface; không tự mở kết nối trong service.
- Các interface có tên `XRepositoryInterface`, implementation là `XRepository`.
- `CatalogRepository` phục vụ danh sách/bộ lọc và form shop/duyệt coupon hiện tại. `CouponRepository` phục vụ module coupon có tìm kiếm phân trang, chi tiết và đóng góp. Hai phần dùng chung bảng/kết nối; không có hai database.
- Transaction giữ tại repository thực hiện một thao tác dữ liệu cần tính nguyên tử. Giữ nguyên procedure và quy tắc transaction đã có trong lần sắp xếp này.

Bản cài đặt cấu trúc duy nhất là `database/schema.sql` (bảng + view + procedure); `database/demo.sql` là dữ liệu thử tùy chọn, import riêng sau schema. Xem [cách cài](../database/README.md) và [quy ước database](../database/RULES.md). Database đang hoạt động đầy đủ không cần import lại.

## 6. Những lớp trung gian đã bỏ

| Trước | Hiện tại |
|---|---|
| Lớp global + `class_alias` sang `App` | Namespace `App\…` trực tiếp, một autoloader |
| `CatalogRouteController → runPage → HomeController::handle` | `CatalogController::index(Request)` |
| `LegacyPageController → runPage → ShopController` | `ShopController::index(Request)` |
| `LegacyPageController → AdminController` | `Admin\ReviewController::index(Request)` cho màn duyệt hiện tại |
| `AuthController → UserController` | `AuthController → AuthService` |
| `AbstractController` + `Controller` + `View` | Một abstract `Controller`, một hàm `view()` |
| `models/User.php`, `models/Coupon.php` adapter | `UserRepository`, `CatalogRepository` |
| `Database → MysqliConnection → MysqliStatement` cùng hai interface SQL | `Database → Statement`, chỉ dùng mysqli |
| `*DataRepositoryInterface` và alias trùng tên implementation | `*RepositoryInterface`, type hint dùng tên interface rõ ràng |

`PortalController` vẫn phục vụ trang `/portal` và dashboard. `CatalogController` phục vụ trang chủ hiện tại. Các URL PHP cũ vẫn có route để không làm hỏng liên kết/form đã viết.

## 7. Quy ước khi thêm chức năng

1. Chốt URL, HTTP method, tên input và kiểu response trước.
2. Thêm phương thức vào interface/repository khi cần thao tác dữ liệu mới.
3. Thêm service nếu có nghiệp vụ cần tách; không tạo một chuỗi lớp chỉ chuyển tiếp.
4. Thêm action vào controller, đăng ký route trong `config/routes.php`.
5. View/JS dùng route đó. Mọi thao tác thay đổi dữ liệu cần CSRF; quyền và owner phải kiểm tra ở server.
6. Kiểm tra cả trường hợp đúng, sai dữ liệu và sai quyền. Không tin `role`, `user_id` hoặc owner từ input.

Không `require` view/model vào nhau. Chỉ view nhúng partial view. Các class `App` được autoload theo namespace; tên thư mục viết thường, tên class/file thống nhất PascalCase.

## 8. Kiểm tra và giới hạn của lần sắp xếp

Đã kiểm tra cú pháp PHP, 92 handler dùng chung abstract Controller, Auth/guard/Router, 6 trường hợp JS Auth, và luồng mysqli/HTTP với MariaDB tạm trên port 13379. Kiểm tra dữ liệu gồm đăng ký/đăng nhập, membership, coupon, transaction, reset token/session và các trang Admin.

Giữ giao diện và hợp đồng request/response hiện tại. Database thật, SQL nguồn và cấu hình OAuth bí mật không bị sửa. OAuth Google/GitHub vẫn ở giai đoạn chuẩn bị; việc sắp xếp này không bổ sung đổi token hoặc đăng nhập OAuth thật. Form quên mật khẩu trong modal vẫn là bản thử như trước; các route reset tích hợp có sẵn không thay đổi.

Tài liệu [INTEGRATION_QUANG.md](INTEGRATION_QUANG.md) ghi lại giai đoạn ghép hai nhánh trước lần sắp xếp này. Dùng tài liệu hiện tại để xác định vị trí class và kiến trúc mới.

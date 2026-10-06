# CouponHub

Đồ án Web Programming viết bằng PHP OOP theo mô hình MVC.

## Chạy dự án

1. Tạo database `dbcouponshubweb` trong phpMyAdmin.
2. Tạo các bảng và routine trực tiếp trong phpMyAdmin theo yêu cầu của giảng viên.
3. Kiểm tra cấu hình kết nối tại `config/database.php`.
4. Đặt dự án trong thư mục web của XAMPP và mở `index.php`.

Các trang chính:

- `index.php`: danh sách coupon công khai.
- `shop.php`: khu vực merchant.
- `admin.php`: khu vực quản trị.

Ứng dụng sử dụng các routine: `ch_actor`, `ch_public_catalog_v2`,
`ch_public_catalog_v3`, `ch_shop_dashboard`, `ch_shop_dashboard_v2`,
`ch_create_coupon`, `ch_admin_dashboard`, `ch_admin_dashboard_v2`,
`ch_approve_coupon` và `ch_reject_coupon`.

## Thành viên

- Từ Trương Thanh Trúc — nhánh `Truc`
- Lữ Võ Hoàng Phúc — nhánh `Phuc`
- Trương Ứng Quang — nhánh `Quang`

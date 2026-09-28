# CouponHub

> **Hệ thống kết nối người dùng và nhà bán lẻ trong việc chia sẻ, tìm kiếm và quản lý mã giảm giá**

CouponHub là đồ án cuối kỳ môn **Web Programming**, được xây dựng theo hướng **PHP OOP + MVC**. Hệ thống đóng vai trò trung gian giữa người dùng, Merchant/Shop và quản trị viên trong việc chia sẻ, tìm kiếm, xác minh và quản lý coupon.

Mục tiêu của CouponHub không chỉ là nơi tổng hợp mã giảm giá, mà còn xây dựng một cộng đồng có cơ chế **xác minh coupon, chấm điểm độ tin cậy và thưởng cho người đóng góp nội dung chất lượng**.

---

## 1. Thành viên

| Họ và tên | MSSV | Nhánh làm việc |
| --- | --- | --- |
| Từ Trương Thanh Trúc | 31241023563 | `Truc` |
| Lữ Võ Hoàng Phúc | 31241025389 | `Phuc` |
| Trương Ứng Quang | 31221020585 | `Quang` |

Ba thành viên cùng xây dựng và hoàn thiện dự án. Mỗi thành viên làm việc trên một nhánh riêng trước khi merge vào nhánh chính.

---

## 2. Mục tiêu hệ thống

CouponHub hướng đến các mục tiêu chính:

- Giúp **người dùng** tìm kiếm, lưu, chia sẻ và kiểm chứng coupon.
- Giúp **Merchant/Shop** công bố các chương trình khuyến mãi và coupon chính thức.
- Cho phép **cộng đồng** xác nhận coupon còn hoạt động hay không.
- Hỗ trợ **Admin** kiểm duyệt và quản lý toàn bộ nội dung.
- Xây dựng hệ thống **CouponPoint, Level và Reputation** để khuyến khích người dùng đóng góp coupon chất lượng.
- Tổ chức coupon theo **Category, Merchant, Campaign, thời gian và hình thức sử dụng**.
- Hạn chế coupon giả, hết hạn, trùng lặp hoặc không còn sử dụng được.

---

## 3. Vai trò người dùng

Hệ thống có 3 role chính:

### User

User có thể:

- Đăng ký, đăng nhập, đăng xuất.
- Cập nhật thông tin cá nhân.
- Tìm kiếm và lọc coupon.
- Xem chi tiết coupon.
- Copy mã giảm giá.
- Lưu coupon yêu thích.
- Vote coupon:
  - Hoạt động.
  - Không hoạt động.
  - Điều kiện đã thay đổi.
- Bình luận coupon.
- Chia sẻ coupon cho cộng đồng.
- Báo cáo coupon không hợp lệ.
- Theo dõi coupon đã đăng.
- Nhận CouponPoint.
- Tăng cấp độ tài khoản.
- Theo dõi lịch sử hoạt động và lịch sử điểm thưởng.

### Merchant / Shop

Merchant đại diện cho doanh nghiệp, cửa hàng hoặc thương hiệu.

Merchant có thể:

- Quản lý hồ sơ cửa hàng.
- Tạo coupon chính thức.
- Sửa, xem hoặc ngừng coupon.
- Tạo Campaign.
- Gắn nhiều coupon vào Campaign.
- Theo dõi coupon đang hoạt động.
- Theo dõi coupon sắp hết hạn.
- Xem các thống kê cơ bản:
  - Lượt xem.
  - Lượt lưu.
  - Lượt copy coupon.
- Cung cấp deal hoặc coupon độc quyền.

Coupon do Merchant đăng có thể được gắn nhãn **Official Deal**.

### Admin

Admin có thể:

- Quản lý User.
- Quản lý Merchant.
- Quản lý Category.
- Kiểm duyệt Coupon.
- Quản lý Campaign.
- Quản lý Report.
- Quản lý Article.
- Theo dõi Dashboard thống kê.
- Khóa / mở khóa User.
- Approve / Reject / Lock Merchant.
- Approve / Reject / Edit / Delete / Expire Coupon.
- Xử lý nội dung bị báo cáo.

---

## 4. Chức năng chính

### 4.1. Quản lý tài khoản và phân quyền

Hệ thống hỗ trợ:

- Đăng ký.
- Đăng nhập.
- Đăng xuất.
- Đổi mật khẩu.
- Cập nhật profile.
- Phân quyền theo:
  - `USER`
  - `MERCHANT`
  - `ADMIN`

Khách chưa đăng nhập vẫn có thể xem và tìm kiếm coupon, nhưng cần đăng nhập để thực hiện các thao tác cộng đồng.

---

### 4.2. Tìm kiếm và lọc coupon

Người dùng có thể tìm theo:

- Tên coupon.
- Coupon code.
- Merchant.
- Từ khóa.
- Danh mục.
- Nội dung mô tả.

Các bộ lọc dự kiến:

- Danh mục:
  - Mua sắm.
  - Ăn uống.
  - Du lịch.
  - Công nghệ.
  - Giải trí.
  - Làm đẹp.
  - Dịch vụ.
  - Thời trang.
- Merchant:
  - Shopee.
  - Lazada.
  - Highlands.
  - KFC.
  - Phúc Long.
  - CGV.
  - ...
- Loại giảm giá:
  - `PERCENTAGE`
  - `FIXED_AMOUNT`
  - `FREE_SHIPPING`
  - `BUY_X_GET_Y`
  - `GIFT`
  - `CASHBACK`
- Hình thức sử dụng:
  - `ONLINE`
  - `OFFLINE`
  - `BOTH`
- Trạng thái:
  - Đang hoạt động.
  - Sắp hết hạn.
  - Mới nhất.
  - Phổ biến nhất.

---

### 4.3. Chia sẻ coupon từ cộng đồng

Sau khi đăng nhập, User có thể gửi coupon mới.

Luồng cơ bản:

```text
Điền form
→ Submit
→ Kiểm tra trùng
→ PENDING
→ Admin kiểm duyệt
→ ACTIVE
```

Thông tin coupon có thể gồm:

- Tên coupon.
- Coupon code.
- Merchant.
- Category.
- Mô tả.
- Điều kiện sử dụng.
- Loại giảm giá.
- Giá trị giảm.
- Giá trị đơn hàng tối thiểu.
- Mức giảm tối đa.
- Ngày bắt đầu.
- Ngày hết hạn.
- Hình thức sử dụng.
- Promotion URL.
- QR Code hoặc hình ảnh liên quan.

---

### 4.4. Coupon Detail

Một coupon có thể hỗ trợ:

- Coupon code.
- QR code.
- Promotion URL.
- Điều kiện sử dụng.
- Ngày hết hạn.
- Merchant.
- Category.
- Trust Score.
- Vote cộng đồng.
- Comment.
- Favorite.
- Report.
- Share.

Các hành động chính:

```text
[SAO CHÉP MÃ]
[LƯU COUPON]
[CHIA SẺ]
[DÙNG ĐƯỢC]
[KHÔNG DÙNG ĐƯỢC]
[BÁO CÁO]
```

---

### 4.5. Community Verification

Sau khi sử dụng coupon, User có thể xác nhận:

- `WORKING` – Dùng được.
- `NOT_WORKING` – Không dùng được.
- `CONDITION_CHANGED` – Điều kiện đã thay đổi.

Ví dụ:

```text
SALE50 - Shopee

87 người xác nhận dùng được
6 người báo không dùng được
3 người báo điều kiện đã thay đổi

Xác nhận gần nhất: 2 giờ trước
```

Dữ liệu này có thể được dùng để cập nhật **Coupon Trust Score**.

---

### 4.6. Coupon Trust Score

Mỗi coupon có thể có một mức độ tin cậy, ví dụ:

```text
92% Verified
```

Trust Score có thể dựa trên:

- Số vote coupon hoạt động.
- Số vote coupon không hoạt động.
- Số report.
- Thời gian từ lần xác nhận gần nhất.
- Reputation của người đăng.
- Coupon có phải Official Deal hay không.

Phiên bản đầu có thể sử dụng công thức đơn giản dựa trên vote cộng đồng; thuật toán nâng cao có thể được phát triển sau.

---

### 4.7. Favorite Coupon

User có thể lưu coupon để sử dụng sau.

Trang cá nhân có thể hiển thị:

```text
Coupon đã lưu
├── Mua sắm
├── Ăn uống
├── Du lịch
└── Giải trí
```

---

### 4.8. Coupon Report

User có thể báo cáo coupon theo các lý do:

- Coupon đã hết hạn.
- Coupon không hoạt động.
- Sai điều kiện sử dụng.
- Coupon bị trùng.
- Spam.
- Thông tin giả.
- Nội dung không phù hợp.
- Khác.

Report được chuyển đến Admin để xử lý.

---

## 5. CouponPoint và Level

CouponPoint được thiết kế để giải quyết câu hỏi:

> **“Tại sao người dùng phải mất công chia sẻ coupon cho người khác?”**

Điểm thưởng dự kiến:

| Hoạt động | Điểm |
| --- | ---: |
| Coupon được Admin duyệt | +5 |
| Coupon nhận đủ 10 vote “Hoạt động” | +10 |
| Coupon đạt 100 lượt lưu | +20 |

### Level

| Điểm | Cấp bậc |
| ---: | --- |
| 0 – 99 | Newbie |
| 100 – 499 | Deal Hunter |
| 500 – 1499 | Deal Expert |
| 1500+ | Coupon Master |

CouponPoint có thể được sử dụng để:

- Nhận badge.
- Tăng level.
- Xuất hiện trên leaderboard.
- Mở khóa coupon đặc biệt.
- Early Access Campaign.
- Đổi Reward.
- Ưu tiên xét duyệt coupon đối với User có uy tín cao.

---

## 6. Contributor Reputation

Ngoài CouponPoint, hệ thống có thể xây dựng Reputation cho người chia sẻ:

```text
New Contributor
→ Active Contributor
→ Trusted Contributor
→ Coupon Expert
```

Reputation được xác định dựa trên chất lượng coupon đã đóng góp thay vì chỉ dựa trên số lượng.

Ví dụ:

```text
50 coupon đã chia sẻ
45 coupon được xác nhận hợp lệ
→ Trusted Contributor
```

---

## 7. Campaign

Một Campaign có thể chứa nhiều coupon của một hoặc nhiều Merchant.

Thuộc tính cơ bản:

```text
name
description
banner
start_date
end_date
status
```

Ví dụ:

- 9.9 Mega Sale.
- 10.10 Super Sale.
- 11.11 Mega Sale.
- 12.12.
- Black Friday.
- Tết.
- Valentine.

Khi Campaign đang diễn ra, Homepage có thể ưu tiên hiển thị:

```text
9.9 MEGA SALE

Còn 02 ngày 08 giờ 15 phút

[Xem deal]
```

---

## 8. Homepage

### Khách chưa đăng nhập

Homepage có thể gồm:

1. Search Coupon.
2. Deal Hot hôm nay.
3. Coupon dành cho bạn.
4. Coupon sắp hết hạn.
5. Campaign sắp diễn ra.
6. Coupon độc quyền.
7. Merchant phổ biến.
8. Danh mục nổi bật.

### Nội dung theo thời điểm

Ngày thường:

```text
HOT DEAL
ENDING SOON
POPULAR MERCHANT
UPCOMING CAMPAIGN
```

Trong thời gian Campaign:

```text
CAMPAIGN BANNER
FLASH DEAL
TRENDING COUPON
EVENT COUPON
```

---

## 9. Merchant Center

```text
Merchant Center
│
├── Dashboard
├── Profile
├── Coupons
├── Campaigns
└── Statistics
```

### Dashboard

Hiển thị:

- Coupon đang hoạt động.
- Coupon sắp hết hạn.
- Tổng lượt xem.
- Tổng lượt lưu.
- Tổng lượt copy mã.

### Coupons

Merchant có thể:

- Thêm.
- Sửa.
- Xem.
- Ngừng coupon.

Coupon có thể vẫn cần Admin duyệt trước khi public.

### Campaigns

Merchant có thể:

- Tạo Campaign.
- Sửa Campaign.
- Gắn nhiều Coupon vào Campaign.

---

## 10. Admin Dashboard

```text
ADMIN
│
├── Dashboard
├── Users
├── Merchants
├── Categories
├── Coupons
├── Campaigns
├── Reports
└── Articles
```

### Coupon Status

Các trạng thái chính:

```text
PENDING
ACTIVE
EXPIRED
REJECTED
DISABLED
```

Hệ thống có thể tự xác định trạng thái `EXPIRING_SOON` dựa trên ngày hết hạn mà không nhất thiết lưu trực tiếp trong database.

---

## 11. Coupon Lifecycle

Luồng vòng đời coupon dự kiến:

```text
PENDING
→ VERIFIED
→ ACTIVE
→ EXPIRING SOON
→ EXPIRED
→ ARCHIVED
```

Một số trạng thái có thể được tính động dựa trên ngày tháng thay vì lưu cố định trong database.

---

## 12. Anti-Duplicate Coupon

Khi User hoặc Merchant đăng coupon mới, hệ thống kiểm tra:

- Merchant.
- Coupon Code.
- Khoảng thời gian hiệu lực.

Ví dụ:

```text
Shopee - SALE50
```

Nếu đã tồn tại coupon tương tự:

> Coupon này có thể đã được đăng. Bạn có muốn xem coupon hiện có không?

Phiên bản đầu có thể thực hiện bằng so sánh chính xác hoặc gần chính xác; thuật toán phát hiện trùng nâng cao được xem là hướng mở rộng.

---

## 13. Trending Coupon

Coupon thịnh hành có thể được xác định dựa trên:

- Lượt xem.
- Lượt copy.
- Lượt lưu.
- Vote.
- Share.
- Thời gian gần đây.

Ví dụ:

```text
Trending Coupons

SALE50  - Shopee
FOOD30  - GrabFood
MOVIE20 - CGV
```

---

## 14. Coupon Recommendation

Khi User xem một coupon, hệ thống có thể gợi ý coupon liên quan dựa trên:

- Cùng Merchant.
- Cùng Category.
- Cùng loại ưu đãi.

Ví dụ:

```text
Đang xem:
SALE50 - Shopee

Coupon liên quan:
SAVE30   - Shopee
FREESHIP - Shopee
TECH20   - Shopee
```

Phiên bản đầu sử dụng rule-based recommendation, không yêu cầu Machine Learning.

---

## 15. Các tính năng mở rộng

Các chức năng dưới đây có giá trị nhưng có thể được triển khai sau khi hoàn thành các chức năng cốt lõi.

### Best Deal Calculator

User nhập giá trị đơn hàng, hệ thống tính số tiền giảm của từng coupon.

Ví dụ:

```text
Giá trị đơn hàng: 650.000đ

15%, tối đa 100.000đ
→ Giảm 97.500đ

Giảm 80.000đ, đơn từ 500.000đ
→ Giảm 80.000đ

20%, tối đa 50.000đ
→ Giảm 50.000đ
```

---

### Price After Coupon Simulator

Mô phỏng số tiền cuối cùng:

```text
Giá sản phẩm: 1.200.000đ
Phí vận chuyển: 30.000đ
Coupon: -100.000đ

Tổng thanh toán: 1.130.000đ
```

---

### Deal Request

User có thể đăng yêu cầu tìm coupon:

> “Ai có mã Highlands hôm nay không?”

Thành viên khác có thể trả lời bằng coupon phù hợp và nhận CouponPoint nếu coupon hợp lệ.

---

### Coupon Stack Checker

Kiểm tra khả năng sử dụng đồng thời:

```text
Shop Voucher
+ Free Shipping
+ Payment Voucher
```

Tính năng này cần mô hình rule tương thích giữa nhiều coupon nên được xem là chức năng mở rộng.

---

### Personalized Deal Feed

Homepage ưu tiên coupon theo:

- Favorite.
- Merchant theo dõi.
- Category quan tâm.
- Lịch sử sử dụng.

Phiên bản đầu có thể sử dụng rule-based personalization; các mô hình recommendation nâng cao có thể phát triển trong tương lai.

---

### Smart Coupon Matching

User nhập nhu cầu như:

> “Tôi muốn mua giày Nike khoảng 2 triệu.”

Hệ thống tìm coupon phù hợp dựa trên:

- Merchant.
- Category.
- Minimum Order.
- Discount.
- Expiration Date.

Tính năng xử lý ngôn ngữ tự nhiên hoặc AI được xem là hướng phát triển trong tương lai.

---

### Reward Exchange

CouponPoint có thể dùng để đổi:

```text
500 CouponPoint
→ Voucher 50.000đ

1.000 CouponPoint
→ Exclusive Deal
```

Phần Reward Marketplace hoàn chỉnh có thể được triển khai ở giai đoạn sau.

---

## 16. Kiến trúc hệ thống

Dự án định hướng theo **OOP + MVC**, kết hợp thêm Service và Repository để tách business logic và truy cập dữ liệu.

```text
couponhub/
│
├── app/
│   │
│   ├── controllers/
│   │   ├── HomeController.php
│   │   ├── AuthController.php
│   │   ├── CouponController.php
│   │   ├── CategoryController.php
│   │   ├── CampaignController.php
│   │   ├── MerchantController.php
│   │   ├── UserController.php
│   │   ├── FavoriteController.php
│   │   ├── VoteController.php
│   │   ├── ReportController.php
│   │   ├── ArticleController.php
│   │   │
│   │   └── admin/
│   │       ├── DashboardController.php
│   │       ├── UserController.php
│   │       ├── MerchantController.php
│   │       ├── CategoryController.php
│   │       ├── CouponController.php
│   │       ├── CampaignController.php
│   │       ├── ReportController.php
│   │       └── ArticleController.php
│   │
│   ├── models/
│   │   ├── User.php
│   │   ├── Merchant.php
│   │   ├── Category.php
│   │   ├── Coupon.php
│   │   ├── Campaign.php
│   │   ├── Favorite.php
│   │   ├── Vote.php
│   │   ├── Report.php
│   │   ├── RewardTransaction.php
│   │   └── Article.php
│   │
│   ├── services/
│   │   ├── AuthService.php
│   │   ├── CouponService.php
│   │   ├── CampaignService.php
│   │   ├── RewardService.php
│   │   ├── MerchantService.php
│   │   └── ExternalApiService.php
│   │
│   ├── repositories/
│   │   ├── UserRepository.php
│   │   ├── MerchantRepository.php
│   │   ├── CouponRepository.php
│   │   ├── CampaignRepository.php
│   │   └── CategoryRepository.php
│   │
│   ├── core/
│   │   ├── App.php
│   │   ├── Router.php
│   │   ├── Controller.php
│   │   ├── Model.php
│   │   ├── Database.php
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Session.php
│   │   └── Validator.php
│   │
│   └── views/
│       │
│       ├── layouts/
│       │   ├── main.php
│       │   ├── admin.php
│       │   └── merchant.php
│       │
│       ├── home/
│       ├── auth/
│       ├── coupons/
│       ├── campaigns/
│       ├── categories/
│       ├── merchants/
│       ├── user/
│       ├── articles/
│       │
│       ├── merchant/
│       │   ├── dashboard/
│       │   ├── coupons/
│       │   └── campaigns/
│       │
│       └── admin/
│           ├── dashboard/
│           ├── users/
│           ├── merchants/
│           ├── categories/
│           ├── coupons/
│           ├── campaigns/
│           ├── reports/
│           └── articles/
│
├── config/
│   ├── app.php
│   ├── database.php
│   └── routes.php
│
├── public/
│   ├── index.php
│   │
│   ├── css/
│   │   ├── style.css
│   │   ├── admin.css
│   │   └── merchant.css
│   │
│   ├── js/
│   │   ├── app.js
│   │   ├── coupon.js
│   │   ├── search.js
│   │   ├── favorite.js
│   │   └── vote.js
│   │
│   ├── images/
│   │
│   └── uploads/
│       ├── merchants/
│       ├── coupons/
│       └── campaigns/
│
├── storage/
│   └── logs/
│
├── .htaccess
└── README.md
```

---

## 17. Vai trò của các lớp chính

### Controller

Nhận request từ User, gọi Service hoặc Model phù hợp và trả về View.

### Model

Biểu diễn các Entity của hệ thống.

### Service

Chứa business logic, ví dụ:

- Tính CouponPoint.
- Kiểm tra Coupon Lifecycle.
- Tính Trust Score.
- Xử lý Campaign.
- Kiểm tra quyền Merchant.

### Repository

Phụ trách truy vấn và truy cập dữ liệu.

### View

Hiển thị giao diện cho người dùng.

### Router

Xác định URL sẽ được xử lý bởi Controller nào.

---

## 18. Entity chính

Dự kiến hệ thống sử dụng các entity:

```text
User
Merchant
Category
Coupon
Campaign
Favorite
Vote
Comment
Report
RewardTransaction
Article
```

Có thể bổ sung thêm:

```text
CouponUsage
UserCollection
Reward
DealRequest
CampaignCoupon
```

khi triển khai các chức năng mở rộng.

---

## 19. Luồng nghiệp vụ chính

### Luồng đóng góp coupon

```text
Đăng Coupon
→ Kiểm tra trùng
→ PENDING
→ Admin kiểm duyệt
→ ACTIVE
→ Community Verification
→ Nhận CouponPoint
→ Tăng Reputation
```

### Luồng tìm và sử dụng coupon

```text
Tìm kiếm
→ Lọc
→ Xem chi tiết
→ Xem Trust Score
→ Copy Coupon
→ Sử dụng
→ Vote / Comment
→ Cập nhật Trust Score
```

### Luồng Report

```text
User Report
→ Admin nhận Report
→ Kiểm tra Coupon
→ Update / Disable / Delete
→ Đóng Report
```

### Luồng Campaign

```text
Merchant/Admin tạo Campaign
→ Gắn Coupon
→ Chờ thời gian bắt đầu
→ Hiển thị Homepage
→ Campaign kết thúc
→ Archive
```

---

## 20. Công nghệ dự kiến

- **Frontend:** HTML5, CSS3, JavaScript.
- **UI:** Bootstrap.
- **Backend:** PHP.
- **Architecture:** OOP + MVC.
- **Database:** MySQL / MariaDB.
- **Web Server:** Apache.
- **Local Environment:** XAMPP hoặc môi trường PHP tương đương.
- **Version Control:** Git + GitHub.

---

## 21. Cách chạy dự án

> Phần này sẽ được cập nhật theo cấu hình thực tế của nhóm.

Hướng triển khai dự kiến:

1. Clone repository.

```bash
git clone <repository-url>
```

2. Đặt project trong thư mục web server, ví dụ với XAMPP:

```text
C:/xampp/htdocs/couponhub
```

3. Khởi động:

```text
Apache
MySQL
```

4. Tạo database cho CouponHub.

5. Cập nhật cấu hình database trong:

```text
config/database.php
```

6. Truy cập:

```text
http://localhost/couponhub/public/
```

---

## 22. Git Workflow

Các nhánh chính của nhóm:

```text
main
├── Truc
├── Phuc
└── Quang
```

Quy trình đề xuất:

```text
Pull code mới nhất
→ Làm việc trên branch cá nhân
→ Commit
→ Push
→ Pull Request
→ Review
→ Merge vào main
```

Không nên code trực tiếp trên `main` nếu chưa thống nhất với nhóm.

---

## 23. Phạm vi phiên bản đầu

Nhóm ưu tiên hoàn thành các chức năng cốt lõi trước:

- Authentication.
- Authorization.
- Coupon CRUD.
- Search / Filter.
- Coupon Detail.
- User Coupon Submission.
- Favorite.
- Vote.
- Report.
- Comment.
- Merchant Management.
- Campaign.
- Admin Dashboard.
- Coupon Moderation.
- CouponPoint cơ bản.
- Coupon Trust Score cơ bản.
- Anti-Duplicate cơ bản.
- Homepage theo Campaign.

Các chức năng nâng cao sẽ được triển khai nếu còn thời gian hoặc đưa vào **Future Development**.

---

## 24. Future Development

Các hướng mở rộng trong tương lai:

- Smart Coupon Matching.
- Personalized Deal Feed nâng cao.
- Coupon Stack Checker.
- Deal Request.
- Best Deal Calculator.
- Price After Coupon Simulator.
- Reward Marketplace hoàn chỉnh.
- Coupon Collection.
- Coupon Usage History.
- Expiration Reminder.
- Flash Deal theo số lượng.
- Recommendation nâng cao.
- Phát hiện coupon trùng bằng similarity matching.
- Notification system.
- Email notification.
- API cho Mobile App.
- AI/ML recommendation khi dữ liệu đủ lớn.

---

## 25. Tầm nhìn

CouponHub hướng đến việc xây dựng một nền tảng coupon có tính cộng đồng, nơi giá trị không chỉ đến từ số lượng mã giảm giá mà còn đến từ:

```text
Coupon chính xác
+ Xác minh từ cộng đồng
+ Merchant chính thức
+ Reward cho người đóng góp
+ Trust Score
+ Campaign
+ Công cụ hỗ trợ lựa chọn deal
```

Từ đó hệ thống giải quyết hai vấn đề chính:

1. **Người tìm coupon không phải mất thời gian thử quá nhiều mã không còn hoạt động.**
2. **Người chia sẻ coupon có động lực đóng góp nội dung chất lượng cho cộng đồng.**

---

## 26. Trạng thái dự án

```text
Status: In Development
Project Type: Web Programming Final Project
Architecture: PHP OOP + MVC
```

---

© 2026 CouponHub Team.

# CouponHub — rà soát cột cho baseline 21 bảng

**Phạm vi:** `database/schema.sql`, mã nguồn `app`, `api`, `scripts`, `tests`, Views, Procedures và demo data từ bản `CouponHub_DB_Migration_Updated.zip` đã cung cấp.

## Quyết định áp dụng

| Thay đổi | Trước | Sau | Kết luận |
|---|---|---|---|
| Bảng bình luận | `comments` (7 cột) + `article_comments` (8 cột) = **15 cột vật lý** | Một bảng `comments` (**9 cột**) | **Bỏ 1 bảng và 6 cột trùng nhau** |
| `comments.coupon_id` | `NOT NULL` | Nullable | Bắt buộc để bình luận bài viết không gắn coupon |
| `comments.article_id` | Chỉ có ở `article_comments` | Chuyển vào `comments` | Bảo toàn quan hệ bài viết |
| `comments.request_id` | Chỉ có ở `article_comments` | Chuyển vào `comments`, nullable với coupon | Bảo toàn chống đăng trùng |
| Các cột khác | Rà soát toàn bộ | Giữ nguyên | Không đủ bằng chứng an toàn để loại bỏ thêm |

Sáu cột được **khử trùng lặp về mặt lưu trữ**, không xóa dữ liệu: `id`, `user_id`, `body`, `status`, `created_at`, `updated_at`. Các bình luận mới dùng cùng một bộ trường. ID bình luận coupon giữ nguyên; ID bình luận bài viết được ánh xạ lại vì có thể va chạm ID.

## Ví dụ các cột ít được đọc trực tiếp trong PHP nhưng phải giữ

| Cột | Lý do giữ |
|---|---|
| `activity_logs.legacy_history_id` | Đối chiếu/định danh 31 lịch sử đã migrate, unique index chống nạp lại |
| `campaign_coupons.added_at` | `CampaignRepository::attachedCoupons()` sắp xếp theo thời gian gắn coupon |
| `campaign_coupons.added_by` | Theo dõi người tạo liên kết và ràng buộc FK |
| `coupon_events.request_key` | Unique chống ghi sự kiện tương tác trùng |
| `coupons.code_normalized` | Cột generated/index hỗ trợ kiểm tra coupon trùng mã |
| `external_offers.imported_at` | Theo dõi thời điểm nhập, được ghi trong `ExternalOfferRepository` |
| `votes.is_eligible_for_trust` | Rule chấm điểm uy tín, được cập nhật khi cấp quyền shop |
| `reports.resolved_by/resolved_at/resolution_note` | Dấu vết quyết định xử lý báo cáo, được test integration |
| `merchants.logo_path`, `users.avatar_path` | Dữ liệu avatar/logo được truy xuất và lưu qua Repository |
| `oauth_accounts.provider_email/email_verified` | Đồng bộ OAuth và điều kiện xác minh email |
| `merchant_memberships.granted_at/revoked_at` | Audit quyền merchant, được ghi khi duyệt/thu hồi |

## Các cột đã bị loại bỏ trong baseline 20-table core trước đó

`articles.category_id`, `articles.cover_path`, `coupons.currency`, `coupons.image_path`, `coupons.qr_image_path`. Chúng không được khôi phục trong baseline 21 bảng.

## Giới hạn

Rà soát mã nguồn tĩnh **không thay thế số liệu production**. Một cột không xuất hiện ở câu lệnh SELECT riêng vẫn có thể được dùng qua `SELECT *`, Stored Procedures, truy vấn migration, hoặc khai thác báo cáo. Trước khi xóa thêm trường của các bảng nghiệp vụ, cần chứng minh được không có dependency và lập chính sách giữ dữ liệu. Do đó lần này **không DROP thêm cột khác ngoài khử trùng lặp tại bảng bình luận**.

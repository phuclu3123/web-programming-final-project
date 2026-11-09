# Đối chiếu ACCESSTRADE với tài liệu Publisher tiếng Việt

**Cập nhật sau lượt đối chiếu này:** người dùng yêu cầu thử lại và lưu dữ liệu. Đã lưu 50 mục vào `external_offers` chờ đối chiếu, giữ nguyên coupon công khai. Xem [kết quả lưu thử](ACCESSTRADE_IMPORT_20261109.md). Các phát biểu chưa nhập dữ liệu bên dưới mô tả thời điểm kiểm tra trước đó.

Ngày đối chiếu: 09/11/2026, theo giờ Việt Nam. Người dùng cung cấp URL từ API Document trên dashboard hiện tại: <https://developers.accesstrade.vn/api-publisher-vietnamese>.

## Tài liệu và code

| Thành phần | Tài liệu Publisher tiếng Việt | Code hiện tại |
| --- | --- | --- |
| Xác thực | `Authorization: Token <access_key>` | Khớp |
| Content-Type | `application/json` | Khớp |
| Danh sách coupon | GET `/v1/offers_informations/coupon` | Khớp |
| Tham số coupon | `is_next_day_coupon`, `merchant`, `limit`, `page` | Code truyền các tham số tương ứng; không bắt buộc bộ lọc merchant |
| Nhà cung cấp coupon | GET `/v1/offers_informations/merchant_list` | Khớp |
| Danh mục coupon | GET `/v1/offers_informations/list_category_coupons` | Khớp |
| Feed offers cũ | GET `/v1/offers_informations` | Code vẫn hỗ trợ, hiện là lựa chọn mặc định |

Nhánh tiếng Anh từng được dẫn trong tài liệu nội bộ có đường dẫn trang khác nhưng cùng hợp đồng xác thực. Chưa thấy bằng chứng cần đổi sang Bearer hoặc gửi key trong query. Không thay đổi cơ chế xác thực khi chưa có tài liệu chứng minh.

Trang tiếng Việt về feed offers cũ có ghi chuyển sang phiên bản mới. Hướng dẫn nhúng hiện tại dùng script `static.accesstrade.vn/coupon/v2/js/main.js` và pubID; đây không phải thông tin đủ để thay URL JSON feed bằng URL script hoặc dùng pubID thay API access key. Cần phân biệt hai loại tích hợp.

## Kiểm tra thực tế, chỉ đọc

Không ghi database, tạo tracking link hoặc thực hiện giao dịch. Không lưu key, mã coupon hay payload nguồn vào báo cáo.

1. API campaign mới `/v1/cashback/campaigns?page=1&page_size=1`: HTTP 200 nhưng nội dung là lỗi `PX00401` / `Invalid token`. Không tính là xác thực thành công.
2. API campaign `/v1/campaigns?limit=1&page=1`: có lần trả HTTP 200 và một bản ghi; bỏ Authorization thì trả HTTP 403 vì thiếu header.
3. API merchant coupon: có lần HTTP 200 với hai bản ghi; request không Authorization trả 403.
4. API coupon: lần đầu 403, lần sau HTTP 200 với `data=[]`; danh sách rỗng không chứng minh có coupon sử dụng được.
5. Chính `AccesstradeService` có lần đọc được 50 offers có mã coupon, chuẩn hóa đủ 50, không bỏ qua mục nào. Chưa kiểm chứng chất lượng điều kiện/hạn dùng hoặc khả năng áp dụng của từng mã.
6. Lần kiểm tra chất lượng offers tiếp theo bị lỗi provider. Ba GET offers giống nhau sau đó, dùng cùng key cố định trong một tiến trình, đều trả HTTP 403 / `Invalid token`.
7. API danh mục coupon có lần lỗi trong service nhưng lần gọi chẩn đoán sau đó HTTP 200.

Người dùng xác nhận không thay key hoặc `.env` trong thời gian kiểm tra. Quan sát cho thấy phản hồi chưa nhất quán trong đợt kiểm tra; chưa đủ bằng chứng quy nguyên nhân cho cache, máy chủ, hạn mức hoặc tài khoản. Không quy toàn bộ sự cố cho việc người dùng chép sai key.

## Kết luận

- Đường dẫn trang tài liệu khác không tự chứng minh endpoint hoặc header sai.
- Đã sửa tài liệu nội bộ để ưu tiên nhánh Publisher tiếng Việt và ghi rõ cảnh báo endpoint cũ.
- Chưa sửa endpoint hoặc cơ chế xác thực trong production code, chưa nhập dữ liệu. Chưa nghiệm thu ACCESSTRADE là nguồn ổn định.
- Cần ACCESSTRADE giải thích việc cùng tài khoản/khóa nhận lỗi xác thực không nhất quán giữa các request và `PX00401` ở API campaign mới.

## Làm rõ sau phản hồi hỗ trợ do người dùng cung cấp

Người dùng chuyển tiếp phản hồi: Get New Campaign chỉ dành cho danh sách Publisher được duyệt; endpoint Postback chuẩn là public; Datafeed cũ đang được nâng cấp nên có thể lỗi tạm thời. Đây là thông tin hỗ trợ do người dùng cung cấp, không phải kết quả xác minh quyền tài khoản qua API.

- Không coi lỗi Get New Campaign là điều kiện chặn nghiệm thu chức năng lấy coupon. Endpoint này chỉ được gọi trong script chẩn đoán thủ công, không được app gọi trong `AccesstradeService` hay luồng đồng bộ hiện tại.
- Postback thông báo/ghi nhận conversion, không cung cấp danh sách coupon. Không dùng endpoint tạo conversion làm nguồn dữ liệu coupon, không tạo đơn thử để thay thế một GET feed.
- Datafeed `/v1/datafeeds` là dữ liệu sản phẩm/giá; app đang gọi offers/coupon trong `/v1/offers_informations`, không gọi Datafeed. Thông tin nâng cấp Datafeed chưa chứng minh là nguyên nhân lỗi offers/coupon.
- Cần hỏi hỗ trợ riêng quyền dùng `/v1/offers_informations` và `/v1/offers_informations/coupon` của tài khoản hiện tại. Phân biệt duyệt tài khoản Publisher, duyệt API hạn chế và duyệt tham gia campaign; chưa xác định chúng là cùng một điều kiện.

Tham khảo thêm [chức năng Postback](https://support.accesstrade.global/system-integration/what-is-postback-and-how-to-use.html) và [dữ liệu sản phẩm Datafeed](https://developers.accesstrade.vn/api-publisher-vietnamese/lay-thong-tin-datafeeds). Chưa triển khai Postback vì nó không thay thế nhu cầu nguồn coupon đang kiểm định.

## Nguồn đối chiếu

- [Authentication tiếng Việt](https://developers.accesstrade.vn/api-publisher-vietnamese/authentication)
- [API coupon, merchant và category](https://developers.accesstrade.vn/api-publisher-vietnamese/lay-thong-tin-vouchers-coupons-deals/tim-kiem-danh-sach-thong-tin-khuyen-mai)
- [API offers cũ và lưu ý chuyển phiên bản](https://developers.accesstrade.vn/api-publisher-vietnamese/lay-thong-tin-cac-khuyen-mai-dang-hoat-dong)
- [Hướng dẫn công cụ nhúng](https://developers.accesstrade.vn/api-publisher-vietnamese/lay-thong-tin-vouchers-coupons-deals/huong-dan-lay-ma-nhung-danh-sach-khuyen-mai-len-website)
- [API Get campaign NEW](https://developers.accesstrade.vn/api-publisher-vietnamese/api-get-campaign-new)

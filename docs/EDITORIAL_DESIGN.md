# CouponHub — The Daily Edit

> Cập nhật mới nhất: [Nghiệm thu UI/UX 17/10/2026](UI_RELEASE_REVIEW.md). Bản hoàn thiện bỏ nút dừng, dải thống kê lớn và các hộp phụ không cần thiết; các mô tả tương tác cũ bên dưới được thay thế bởi báo cáo này.

## Định hướng đã được người dùng chọn

Người trẻ thành thị, thích mua sắm và trải nghiệm. Biên tập táo bạo, ảnh lớn và tương phản rõ, kết hợp độ thoáng và sự tiết chế của Apple. Chuyển động nhẹ; không dùng hình AI. Khung vé, kính mờ và banner hai cột lặp lại không còn là cấu trúc mở đầu cho mọi trang.

Tham khảo nhịp hình ảnh và chữ tại [Nike](https://www.nike.com/vn), [Framer](https://www.framer.com), cách phân tầng tại [Apple](https://www.apple.com), cách khám phá nhu cầu tại [Klook](https://www.klook.com), giọng trực tiếp tại [Partiful](https://partiful.com), và tuyển chọn theo sở thích tại [Depop](https://www.depop.com). Mượn nguyên tắc trình bày; không chép banner, nội dung, số liệu hay nhận diện của các trang này.

## Năm điểm đến, năm cấu trúc

- **Khám phá:** giữ bố cục một ảnh lớn được người dùng chọn. Vào trang thấy ngay ảnh, bỏ phần slogan/mô tả phía trên; tìm kiếm chuyển xuống danh sách. Chiều rộng cùng lề các trang công khai. Hai mũi tên kính nổi ngoài ảnh, bộ chọn vị trí và nút dừng nằm trên ảnh. Tối đa mười coupon còn hiệu lực, cùng toàn bộ ảnh/điều kiện/link/sao chép riêng cho từng trang. Mặt nạ vé và các thẻ quảng cáo phụ đã bỏ. Sáu coupon đầu, xem thêm, ước tính khoản giảm và dải thương hiệu tiếp tục dùng dữ liệu hiện có.
- **Danh mục:** mục lục dạng chữ lớn và số thứ tự, số coupon thật, ảnh xem trước đổi theo rê chuột hoặc focus. Link vẫn dùng được khi không có JS. Phần xem trước ba mã dùng GET `/categories?category=ID#category-picks`.
- **Cộng đồng:** mở bằng banner ảnh bàn ăn, thông điệp “Của bạn. Cho cả hội.” và hành động chia sẻ. Tìm kiếm một hàng không lồng hộp trắng; bộ lọc bên dưới. Bảng hai cột bỏ toàn bộ ảnh cửa hàng lặp; cột phụ dẫn đến thương hiệu thật và hướng dẫn kiểm chứng. Thẻ hiển thị nguồn cửa hàng hay tên người đóng góp, ngày chia sẻ, điều kiện, hạn dùng và tín hiệu xác minh thật. SSR và API dùng chung partial; không dựng thành viên hoặc lượt tương tác giả.
- **Góc đọc:** masthead như ấn phẩm, ảnh mở đầu lớn và phần nội dung chồng có chủ đích. Đường ngang là mặt kệ: ba gáy cẩm nang là link thật, ảnh tai nghe chụp thật tách nền nằm trên mép. Có ba cẩm nang sản phẩm đầy đủ tại `/guides/...`, bộ lọc chủ đề, mục lục và hướng dẫn thực hành. Bài trong database vẫn chỉ hiện khi đã xuất bản; không tạo tác giả, ngày xuất bản hoặc lượt xem giả.
- **Chiến dịch:** giữ cam Shopee theo phản hồi; ô công nghệ cobalt dùng ảnh tai nghe tách nền; lịch dùng màu giấy vàng xanh. Mốc ngày và đồng hồ ngày/giờ/phút/giây lấy từ DB. Thời gian lưu UTC, hiển thị giờ Việt Nam. Bộ lọc, lịch, trang chi tiết và phân trang vẫn hoạt động. Chỉ có hai chiến dịch hiện tại; ô lịch bổ sung chức năng thay vì tạo chiến dịch giả.

Phần dưới Chiến dịch hiện là bộ chọn chiến dịch và các coupon thực sự được gắn: mức giảm, đơn tối thiểu, trần giảm, kênh dùng, hạn riêng, link lấy mã. Thay cho danh sách lặp ngày tháng. Query `preview` được kiểm tra cùng phạm vi giai đoạn/trang hiện tại, đầu vào sai trả 422/404.

Phông của các trang công khai đã chuyển về Arial/Helvetica Neue/sans-serif; bỏ serif/nghiêng và giảm độ siết chữ để dấu tiếng Việt hiển thị ổn định.

## Hình ảnh

Ảnh chính thức được ghim và lưu cục bộ từ thư viện báo chí Grab, Coolmate, Be. Danh sách trang nguồn, URL ảnh và bối cảnh nằm tại [brands/sources.json](../assets/images/brands/sources.json). Script `scripts/collect_brand_assets.py` chỉ tải các URL đã chọn. Có mười asset mới; ảnh cụ thể được gán đúng thương hiệu tương ứng. Không dùng asset có tên generative fill đã xuất hiện trong trang nguồn.

Ảnh bối cảnh còn lại và ảnh tai nghe thuộc bộ ảnh chụp Unsplash đã ghi nguồn tại [editorial/sources.json](../assets/images/editorial/sources.json), theo [Unsplash License](https://unsplash.com/license). Tách nền tai nghe bằng alpha mask vẽ thủ công và Pillow, không dùng mô hình AI. Ảnh bối cảnh không phải ảnh quảng cáo chính thức của từng khuyến mãi. Logo nhỏ lấy từ website thương hiệu, gồm biểu tượng Lazada cắt từ wordmark chính thức và favicon ShopeeFood.

Ảnh từ trang thương hiệu không tự mang theo giấy phép quảng cáo hoặc phân phối lại. Hồ sơ nguồn giữ thông tin để bên bàn giao đối chiếu quyền sử dụng. Không dùng hình người dùng tải lên làm nguồn ảnh mặc định. Đợt này không tải ảnh thú bông IKEA; bản dựng kệ dùng ảnh tai nghe đã có trong project.

## Tương tác và khả năng truy cập

Theo yêu cầu bổ sung, hero chuyển sau 7 giây với fade 200ms, không dịch chuyển bố cục. Có mũi tên, phím trái/phải, bộ chọn trang và nút dừng/chạy. Dừng khi rê chuột hoặc tab bị ẩn; điều hướng bằng chuột tiếp tục tự chuyển sau khi rời banner; bàn phím/focus hoặc nút dừng sẽ dừng cho đến khi người xem bật lại. Reduced-motion khởi đầu tĩnh. Tải và decode ảnh trước khi đổi; lần bấm mới nhất thắng khi ảnh cũ tải chậm. Sau AJAX, hủy timer/listener cũ và khởi tạo trên dữ liệu mới. Không JS vẫn thấy coupon đầu và dùng được link/sao chép dự phòng. Một hoặc không có coupon thì không có điều khiển thừa.

Ảnh hero dùng bản gốc tải lại, resize xuống, không phóng lớn: Coolmate 2138×1440, Grab 1920×1280, bối cảnh 1800–2560 px. Mười coupon dùng mười ảnh khác nhau; đảo san hô thay ảnh biển nhạt. Logo Lazada lấy biểu tượng từ wordmark chính thức, ShopeeFood từ favicon chính thức; lưu cục bộ và có cache bust. `srcset/sizes` tách với ảnh thẻ nhỏ; preload trang kế tiếp. Nguồn/kích thước nằm trong `assets/images/hero/sources.json`; chạy `python scripts/collect_brand_assets.py --hero` để tái tạo.

Dải thương hiệu chạy chậm, có nút dừng, dừng khi hover/focus, bản lặp ẩn khỏi trình đọc màn hình và Tab. `prefers-reduced-motion` chuyển thành cuộn tay. Khi ít thương hiệu thì hiển thị tĩnh.

Mục lục danh mục phản hồi cả pointer và keyboard; ba gáy sách là anchor thông thường. Đồng hồ không đọc lại mỗi giây với screen reader và không phụ thuộc giờ máy khách. Đã có breakpoint cho bố cục nhỏ; đây chưa phải bằng chứng xác nhận trực quan trên thiết bị.

## Kiểm tra bản ngày 17/10/2026

- `python scripts/check_public_ui.py`: trang/trạng thái, liên kết chi tiết công khai, API với kết quả/rỗng, tên đóng góp, ID HTML duy nhất, asset cục bộ. Bổ sung kiểm tra chọn chiến dịch, carousel có mười coupon active và mười ảnh khác nhau, mã sao chép/link đúng coupon, ảnh 1800px+, trạng thái một/rỗng. Chỉ GET, không ghi DB.
- `node --test tests/hero-carousel.test.cjs`: điều hướng, tự chuyển/dừng, focus/reduced-motion, ảnh tải chậm, dọn timer khi AJAX.
- `node --test tests/campaign-clock.test.cjs tests/auth-ui.test.cjs`: 10 kiểm tra đạt.
- PHP lint các template/controller/repository được sửa và JS syntax đạt.
- Không import SQL, đổi schema, seed nội dung hay thay thông tin tài khoản.

Kiểm tra trực quan bản cuối còn giới hạn: IAB không trả browser trong inventory; Computer Use báo native pipe không có. Đã đối chiếu screenshot người dùng và sửa cụ thể mặt nạ cắt chữ, vòng tròn cũ, cam Shopee và khoảng trống phía trên đường kẻ. Cần duyệt lại bản cuối trên trình duyệt khi có kết nối; không coi HTTP 200 là bằng chứng UI đạt yêu cầu thẩm mỹ.

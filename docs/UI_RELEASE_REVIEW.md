# Nghiệm thu UI/UX — 17/10/2026

## Phạm vi bản bàn giao

Giữ hướng biên tập ảnh thật, phông Arial hỗ trợ tiếng Việt và banner Khám phá đã được duyệt. Hoàn thiện các trang công khai, chi tiết, tài khoản, biểu mẫu đối tác và quản trị trong project PHP hiện tại. Không dùng ảnh AI, không tạo số liệu hoặc thành viên giả, không import SQL hay thay dữ liệu tài khoản.

| Khu vực | Thay đổi và lý do |
| --- | --- |
| Khám phá | Vào ngay banner ảnh lớn; giữ 10 coupon và ảnh riêng. Bỏ dải thống kê bốn cột để nội dung ưu đãi đến sớm hơn. Nút kính ở ngoài ảnh; bấm chuyển không làm mất chế độ tự chạy. |
| Dải thương hiệu | Bỏ nút dừng và CSS chuyển sang cuộn khi focus. Không hiện thanh ngang khi bấm logo; chuyển động chạy liên tục, vẫn tôn trọng thiết lập giảm chuyển động của thiết bị. |
| Cộng đồng | Tìm kiếm một hàng không lồng hộp. Cột thương hiệu dùng danh sách gọn, bỏ slogan và khối nền dư. Thẻ mã ưu tiên mức giảm, điều kiện và người chia sẻ. |
| Coupon chi tiết | Logo, tiêu đề sạch, mức ưu đãi và bốn điều kiện chính. Mã có vùng sao chép rõ ràng trước phần mô tả. Thời hạn/nguồn và tín hiệu cộng đồng gọn; thống kê phụ mở khi cần. Giữ nguyên lưu, bình chọn, bình luận, báo cáo và CSRF. |
| Chiến dịch chi tiết | Ảnh và màu theo cùng nhận diện với poster danh sách; giữ cam Shopee. Đồng hồ dùng dữ liệu UTC và hiển thị giờ Việt Nam. Thẻ coupon có logo, đơn tối thiểu, trần giảm, kênh dùng, hạn riêng và link lấy mã. |
| Lịch ưu đãi | Hiển thị chiến dịch thật đang diễn ra/sắp tới. Ngày lễ bên ngoài chỉ là phần tham khảo khi có dữ liệu; bỏ thông báo kỹ thuật về PHP/API khỏi nội dung chính. |
| Bài viết và hướng dẫn | Căn lại ảnh, kệ và phần giới thiệu vào hai cột rõ ràng. Thu khoảng trống giữa mục lục và bài; giới hạn chiều dài dòng, thống nhất cỡ chữ và nhịp đọc. Ảnh mở đầu dùng bản lớn có sẵn. |
| Tài khoản và quản lý | Đồng bộ chữ, bo góc, nút, ô nhập, bảng và trạng thái rỗng. Bổ sung nhãn truy cập cho các biểu mẫu tạo/sửa coupon, chiến dịch, hồ sơ và quản trị. |
| Trang thông tin/lỗi | Bố cục đọc gọn cho giới thiệu/chính sách. 404/403/422/503 có nhận diện và đường quay lại; không cần truy vấn DB để dựng trang lỗi. Footer bỏ slogan lớn lặp lại. |

## Kiểm tra

- `python scripts/check_ui_release.py`: 87 màn hình/trạng thái đạt. Dựng trang bằng dữ liệu hiện có cho khách, USER, MERCHANT, ADMIN; bao gồm mọi coupon công khai, chiến dịch và các trang chi tiết/chỉnh sửa phát hiện từ liên kết thật. Kiểm tra status, một H1 đang hiển thị, ID duy nhất, tài nguyên cục bộ và nhãn biểu mẫu. Mỗi tiến trình có session thử riêng và transaction DB READ ONLY.
- `python scripts/check_public_ui.py`: điều hướng công khai, link chi tiết, lọc và trạng thái rỗng, API dùng chung thẻ, 10 coupon/10 ảnh không trùng, mã sao chép khớp dữ liệu, logo và tài nguyên trả thành công.
- `node --test tests/hero-carousel.test.cjs tests/campaign-clock.test.cjs tests/auth-ui.test.cjs`: 15 ca cho tự chuyển vòng, điều hướng bằng chuột/bàn phím, ảnh decode chậm, thay AJAX, thời gian chiến dịch và xác thực.
- `php scripts/check_routes.php`: 96 route handler hợp lệ. Lint PHP, kiểm tra cú pháp JavaScript và parse stylesheet mới đều đạt.
- Không có thao tác POST nghiệp vụ trong lượt nghiệm thu; không tạo, xóa hoặc sửa coupon/tài khoản trong DB.

## Đánh giá và giới hạn

Đã xử lý các phần dư, scrollbar thương hiệu, khoảng trống đọc bài, trang chi tiết cũ và tính nhất quán của control. Cơ sở nghiệm thu là phản hồi/screenshot của người dùng, kiểm tra cấu trúc, mã nguồn và luồng thật.

Chưa thể xác nhận bằng screenshot trình duyệt ở các kích thước desktop/mobile: inventory IAB rỗng và Computer Use báo native pipe không khả dụng. Các breakpoint đã được triển khai và kiểm tra cú pháp; điều đó không thay cho nghiệm thu trực quan. Bản này là bản UI để xem trước; nội dung minh họa của dự án vẫn được ghi rõ.

## Cập nhật Git

Người dùng đã yêu cầu cập nhật `Phuc` và `main`. Bản bàn giao gồm project tích hợp hiện có cùng UI hoàn thiện; không ghi đè nhánh Quang/Truc. Cấu hình local, OAuth local, session thử, log, cache và upload riêng được loại bằng `.gitignore`. Cập nhật hai nhánh bằng fast-forward cùng một commit; không force-push.

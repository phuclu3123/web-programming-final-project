<?php
declare(strict_types=1);

return [
    'doc-dieu-kien-ma' => [
        'title'=>'Một mã giảm giá tốt bắt đầu từ điều kiện rõ ràng.',
        'topic'=>'Dùng mã đúng cách','photo'=>'shopping','minutes'=>4,
        'summary'=>'Đọc đúng bốn thông tin trước khi sao chép: đơn tối thiểu, trần giảm, kênh áp dụng và hạn dùng.',
        'sections'=>[
            ['title'=>'01. Bắt đầu từ đơn hàng của bạn','body'=>'Hãy chọn những món bạn thực sự cần trước khi tìm mã. Trên CouponHub, mức giảm lớn được đặt cạnh đơn tối thiểu để bạn biết ngay ưu đãi có phù hợp. Nếu đơn chưa đạt điều kiện, việc thêm món chỉ để dùng mã có thể khiến tổng tiền phải trả tăng lên.'],
            ['title'=>'02. Phần trăm không phải số tiền cuối cùng','body'=>'Ví dụ minh họa: mã giảm 20%, tối đa 30.000đ, đơn từ 100.000đ. Với đơn 200.000đ, phép tính phần trăm cho 40.000đ nhưng số tiền giảm bị giới hạn còn 30.000đ. Hãy mở “Xem điều kiện chi tiết” hoặc trang coupon để đọc đầy đủ giới hạn.'],
            ['title'=>'03. Đúng kênh, đúng thời điểm','body'=>'Một mã dùng tại cửa hàng có thể không áp dụng khi đặt online. Kiểm tra nhãn kênh sử dụng, thời gian bắt đầu và kết thúc. Thời gian trên giao diện CouponHub hiển thị theo giờ Việt Nam. Ưu đãi còn hạn vẫn có thể có giới hạn về khu vực, sản phẩm hoặc số lượt sử dụng do cửa hàng đặt ra.'],
            ['title'=>'04. Xác nhận tại bước thanh toán','body'=>'Sao chép mã, mở nền tảng của cửa hàng rồi nhập mã ở bước thanh toán. Kiểm tra tổng tiền sau giảm trước khi xác nhận mua. CouponHub giúp khám phá và kiểm chứng thông tin; cửa hàng xác định việc áp dụng cuối cùng. Nếu gặp điều kiện khác với mô tả, hãy dùng chức năng báo cáo ở trang chi tiết để gửi thông tin cụ thể.'],
        ],
        'action'=>'Tìm mã phù hợp','href'=>'/coupons',
    ],
    'so-sanh-muc-tiet-kiem' => [
        'title'=>'Giảm 20% hay giảm 40.000đ: mã nào hợp đơn hơn?',
        'topic'=>'Tính trước khi mua','photo'=>'technology','minutes'=>3,
        'summary'=>'Một ví dụ cụ thể để so sánh phần trăm, số tiền cố định và giới hạn giảm tối đa.',
        'sections'=>[
            ['title'=>'01. So trên cùng một giá trị đơn','body'=>'Giả sử đơn của bạn là 250.000đ và cả hai mã đều đáp ứng điều kiện. Mã A giảm 20% nhưng tối đa 30.000đ. Mã B giảm 40.000đ. Mã A giảm thực tế 30.000đ, mã B giảm 40.000đ. Trong ví dụ này, mã B cho khoản giảm lớn hơn 10.000đ. Đây là ví dụ tính toán, không phải một chương trình khuyến mãi đang được cam kết.'],
            ['title'=>'02. Tính cả giới hạn và đơn tối thiểu','body'=>'Công thức cho mã phần trăm là giá trị đơn nhân tỷ lệ giảm, sau đó lấy số nhỏ hơn giữa kết quả và trần giảm. Với mã giảm tiền, khoản giảm không vượt giá trị đơn. Nếu đơn chưa đạt tối thiểu thì chưa đủ điều kiện áp dụng. Công cụ ước tính tại trang Khám phá thực hiện các bước này trên thông tin của mã bạn chọn.'],
            ['title'=>'03. Những khoản công cụ chưa tính','body'=>'Giá sản phẩm sau các khuyến mãi khác, phí vận chuyển, phụ phí, giới hạn khu vực và khả năng cộng dồn có thể ảnh hưởng tổng tiền. Công cụ chỉ ước tính mức giảm từ thông tin coupon. Hãy so tổng tiền cần thanh toán trên nền tảng bán hàng để có kết quả cuối cùng.'],
        ],
        'action'=>'Thử công cụ ước tính','href'=>'/index.php#estimate',
    ],
    'kiem-chung-cung-cong-dong' => [
        'title'=>'Một lần xác minh của bạn giúp người đến sau.',
        'topic'=>'Cộng đồng có trách nhiệm','photo'=>'food','minutes'=>4,
        'summary'=>'Cách chia sẻ trải nghiệm sử dụng mã và đọc tín hiệu xác minh mà không nhầm với cam kết của cửa hàng.',
        'sections'=>[
            ['title'=>'01. Chia sẻ thông tin có thể kiểm tra','body'=>'Khi gửi coupon, hãy ghi nguồn, thời hạn và điều kiện bạn đọc được. Mô tả rõ nền tảng, sản phẩm hoặc khu vực áp dụng. Tránh lời hứa tuyệt đối như “mọi đơn đều dùng được” nếu nguồn không nói như vậy. Coupon được gửi qua luồng kiểm duyệt trước khi xuất hiện công khai.'],
            ['title'=>'02. Xác minh sau khi thử dùng','body'=>'Trang chi tiết cho phép người dùng đủ điều kiện ghi nhận mã dùng được, không dùng được hoặc điều kiện đã thay đổi. Hãy phản ánh lần sử dụng thực tế của mình. Tác giả và người quản lý cửa hàng không được tự bỏ phiếu trên mã của chính mình; hệ thống áp dụng kiểm tra này ở server.'],
            ['title'=>'03. Đọc tỷ lệ cùng số lượt đánh giá','body'=>'Tỷ lệ xác nhận cho biết tín hiệu từ các phiếu hợp lệ, không bảo đảm lần mua tiếp theo sẽ thành công. Khi chưa có đủ thông tin, giao diện hiển thị trạng thái chưa có đánh giá. Số lượt lưu thể hiện việc giữ lại coupon, không đồng nghĩa người dùng đã thanh toán thành công.'],
            ['title'=>'04. Báo cáo đúng vấn đề','body'=>'Nếu mã hết hiệu lực, mô tả sai hoặc có nội dung bất thường, hãy mở chức năng báo cáo tại trang chi tiết. Nêu điều kiện đã thử và thông báo lỗi, tránh đưa thông tin thanh toán hoặc dữ liệu cá nhân vào bình luận công khai. Báo cáo được xử lý qua luồng quản trị của CouponHub.'],
        ],
        'action'=>'Khám phá cộng đồng','href'=>'/coupons',
    ],
];

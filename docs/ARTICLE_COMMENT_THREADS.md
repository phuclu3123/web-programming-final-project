# Trả lời và thích bình luận bài viết

USER, MERCHANT và ADMIN có thể bình luận, trả lời bình luận hoặc phản hồi, thích và bỏ thích. Khách chỉ đọc; hành động tương tác dẫn tới đăng nhập. Nhãn vai trò được đọc từ database.

Phản hồi giữ `parent_comment_id` là nội dung đang trả lời và `root_comment_id` là bình luận gốc. Giao diện gom phản hồi trong cùng luồng, ghi rõ người được trả lời, không thụt lề vô hạn. Mỗi lượt tải tối đa 20 gốc và 20 phản hồi mỗi luồng; có tải thêm phản hồi riêng.

Phản hồi mặc định thu gọn thành “Xem N câu trả lời”, có thể mở/ẩn bằng chuột hoặc bàn phím, hoạt động cả khi tắt JavaScript. Gửi phản hồi mới hoặc mở permalink tới phản hồi sẽ mở đúng luồng; thao tác tải thêm không tự thu gọn luồng đang xem.

Permalink `/articles/{slug}?comment={id}#article-comment-{id}` tải đúng luồng và vị trí, kể cả phản hồi nằm ngoài trang đầu. Admin có thể đi từ kiểm duyệt tới cùng permalink.

Ẩn/xóa không xóa các phản hồi khác. Gốc không còn hiển thị chỉ để lại thông báo giữ chỗ nếu còn phản hồi công khai. Nội dung và tên của bình luận đã ẩn/xóa không được trả ra public; không thể trả lời hoặc thích nó. Người dùng chỉ được xóa nội dung của mình; admin ẩn/hiện qua kiểm duyệt.

API ghi vẫn kiểm tra đăng nhập, CSRF, cùng origin và giới hạn gửi. `request_id` chống tạo trùng cả nội dung lẫn đích trả lời. Thích gửi trạng thái `liked=1/0`; khóa chính `(comment_id,user_id)` tránh trùng lượt thích khi retry. Server xác minh đích trả lời thuộc đúng bài viết, không nhận root hay role từ client.

## Cập nhật database

Backup và áp dụng `database/migrations/20261110_comment_threads_reactions.sql` trước khi đưa source mới vào hoạt động. Migration không xóa hoặc đổi bình luận có sẵn, có thể chạy lại. Tổng sau nâng cấp là 23 bảng nếu đã có `external_campaigns`.

Sau đó áp dụng `database/migrations/20261110_comment_notifications.sql`. Tổng baseline mới nhất là 24 bảng. Thông báo reply/thả tim và biên nhận đã đọc nằm trong `notifications`, không dùng session/localStorage. Thông báo tạo cùng transaction với tương tác, gửi cho chủ bình luận được trả lời/thích; không tự báo bản thân. Bỏ thích đánh dấu sự kiện không còn hoạt động, thích lại giữ cùng sự kiện và trạng thái đã đọc để tránh spam. Nội dung ẩn/xóa không xuất hiện trong feed.

Chuông tải lại khi mở menu, khi quay lại tab và mỗi 30 giây khi trang đang hiển thị. Bấm thông báo đánh dấu đã đọc trước khi tới đúng bình luận; trạng thái giữ nguyên sau đăng xuất/đăng nhập và ở thiết bị khác. Đây là thông báo trong ứng dụng, không gửi email hoặc push bên ngoài.

Các endpoint:

- `GET/POST /api/articles/{id}/comments` — đọc/gửi; POST nhận thêm `parent_comment_id` tùy chọn.
- `GET /api/articles/{id}/comments/{rootId}/replies?after={id}` — tải thêm phản hồi.
- `POST /api/article-comments/{id}/react` — `liked=1/0` và CSRF.
- `GET /api/notifications` — feed của tài khoản trong phiên xác thực.
- `POST /notifications/read` — đánh dấu tất cả hoặc `event_key` thuộc feed tài khoản hiện tại; yêu cầu CSRF.

Reply và thích dùng form HTML khi tắt JavaScript; khi bật JavaScript, thao tác được gửi bằng AJAX và nội dung lỗi vẫn được giữ để thử lại.

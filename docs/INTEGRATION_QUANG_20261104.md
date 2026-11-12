# Tích hợp cập nhật main/Quang — 04/11/2026

## Nguồn và phạm vi

- Fetch trực tiếp `origin main Quang` từ GitHub.
- `origin/main`: `668e1609b45a4b9a2828e051f36171b3c077cb0c`, không có commit mới so với nền Phúc đợt 4.
- `origin/Quang`: `285c8f6750d2868f0d4c00f0a1678236cfb4d319`, có hai commit mới: `a01373c` và `285c8f6`.
- Lưu toàn bộ phần Phúc đợt 4 đã nghiệm thu thành commit local `df279f3` trước khi merge. Không đưa `.env`, thư mục runtime hoặc tài liệu/ZIP bài giảng vào commit.

## Nội dung đã ghép và review

1. Query danh mục của kết quả tìm coupon chỉ lấy các ID xuất hiện trên trang, loại ID lặp, bind tham số; giữ fallback “Khác” và giao diện category badge. Không đổi điều kiện hiệu lực, ownership, trạng thái hoặc logic count/list.
2. Query chi tiết public bỏ email người đóng góp; vẫn giữ tên hiển thị. Query hàng kiểm duyệt Admin giữ email vì thuộc màn nội bộ được bảo vệ.
3. Hai layout dùng chung partial/CSS/JS cho nút Liên hệ và Về đầu trang, bỏ handler cũ và nút trùng ở footer. Panel đóng có inert/aria-hidden, nút lên đầu có tabindex theo trạng thái hiển thị; Escape và reduced-motion được xử lý.
4. Sửa thêm lúc chọn link hỗ trợ: trả focus về trigger trước khi panel bị inert, không để focus trong vùng đã đóng. Bổ sung kiểm tra hành vi tương ứng.
5. CI giữ các kiểm tra mới của Quang cùng các kiểm tra Phúc đợt 4. Runner đợt 4 có thêm kiểm tra repository của Quang; kiểm tra SQL thực tế được bổ sung trong mysqli integration.

Không thêm migration/schema từ hai commit Quang này. Migration Phúc đợt 4 vẫn cần áp theo tài liệu nếu máy đang dùng chưa nâng cấp; không import lại schema/demo lên DB có dữ liệu.

## Kiểm thử

Đã chạy thành công `COUPONHUB_RUN_DB_TEST=1 python tests/run-phase4.py` trên database thử riêng `couponhub_merge_test`, port `13379`. Log local: `.runtime/phase4-review-1793750702606664100/`.

- PHP syntax và **56/56 JavaScript tests** qua.
- Count/list với 12 trường hợp filter/sort, category enrichment, coupon chờ duyệt bị ẩn và public detail không trả email người đóng góp: qua trên MariaDB thực.
- Toàn bộ hồi quy đợt 3 và đợt 4 qua: quyền Admin/Merchant/User, session/CSRF/Origin, các form/route ở root và thư mục con, OAuth/OTP giả lập, migration lặp không đổi dữ liệu.
- Rate limit đồng thời: quota 7 cho 20 process, đúng 7 qua/13 bị chặn.
- Fixture database thử đã khôi phục; không sửa database đang dùng và không gửi email thật. Không khẳng định đã nghiệm thu UI trực quan trên mọi trình duyệt/thiết bị từ các kiểm tra này.

Không dùng nhận xét/nhãn “phase7/phase8” trong nhánh thay cho bằng chứng kiểm thử. Không gọi các commit này là module nghiệp vụ mới: chủ yếu cải thiện query, quyền riêng tư và thành phần giao diện dùng chung. OAuth/email thật vẫn chờ `.env` và nghiệm thu với app của người dùng.

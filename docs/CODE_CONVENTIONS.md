# Quy ước code và comment

## Comment

- PHP/JavaScript dùng `//` cho giải thích một dòng. Không ghép `//` và `#` thành một marker.
- `#` dùng trong định dạng hỗ trợ như shell, PowerShell và env; JavaScript không dùng `#` làm comment.
- `/* ... */` phù hợp cho CSS hoặc giải thích ngắn nhiều dòng. Không tạo khung dấu sao/gạch trang trí.
- Giữ PHPDoc khi cần `@param`, `@return` hoặc kiểu dữ liệu cho IDE; không lặp lại tên hàm bằng lời.
- Giải thích lý do, ràng buộc, dữ liệu vào/ra khó thấy từ code. Getter, gán biến và câu lệnh hiển nhiên không cần comment riêng.
- Chỉ mô tả flow khi có state, transaction, chống tranh chấp, retry hoặc kiểm chứng danh tính.
- Comment bằng ngôn ngữ tự nhiên, không chèn mật khẩu, API key, dữ liệu cá nhân hay chỉ dẫn gắn cứng máy một thành viên.

```php
// Giữ quyết định bỏ qua khi nguồn được đồng bộ lại.

/* Khóa bản ghi trước khi đối chiếu fingerprint, tạo coupon và lưu liên kết
   trong cùng transaction; lỗi ở bất kỳ bước nào phải rollback. */
```

## Vị trí đặt code

| Phần | Trách nhiệm |
|---|---|
| `config/routes.php` | Khai báo URL/method/action; không xử lý nghiệp vụ tại wrapper API. |
| Controller | Đọc Request, kiểm tra quyền, gọi service/repository và chọn View/JSON/redirect. |
| Service | Validation và điều phối nghiệp vụ; không xuất HTML hoặc tự mở kết nối MySQL. |
| Repository | Truy vấn, khóa và transaction; không đọc `$_POST` hoặc render view. |
| View/partial | Trình bày dữ liệu đã chuẩn bị, escape bằng helper; không tự truy vấn SQL. |
| JS | Progressive enhancement, AJAX và trạng thái giao diện; không quyết định quyền. |
| `scripts/` | Công cụ CLI lặp lại được, có guard nếu ghi dữ liệu. |
| `CouponHub_DevTools` ngoài repo | Giữ test, công cụ kiểm chứng và benchmark; dữ liệu ghi chỉ trong DB test riêng. |

Health endpoint có truy vấn kỹ thuật `SELECT 1`/collation để chẩn đoán kết nối. Đây không phải truy vấn nghiệp vụ chuyển vào view.

## File và thư mục

- `api/` và wrapper PHP ở gốc tiếp tục được giữ vì URL cũ còn dùng.
- `assets/` và `public/` cùng chứa asset đang được các layout tham chiếu. Không gộp/đổi đường dẫn nếu chưa cập nhật caller và kiểm thử.
- `app/content/` là nội dung biên tập cho công cụ xuất bản, không phải database giả của ứng dụng.
- Source LaTeX/draw.io giữ riêng local. Bộ test và script không thuộc runtime được lưu ngoài repo, không trộn lại vào source web.
- Runtime của developer ở `.runtime/`; cache/log/session của ứng dụng ở nơi lưu riêng được bảo vệ.
- Không sửa code thư viện trong `assets/vendor/`; giữ LICENSE và đường dẫn webfont đi cùng CSS.

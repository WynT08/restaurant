# API Endpoints

Tất cả endpoint yêu cầu session đăng nhập hợp lệ (sử dụng cùng phiên của ứng dụng web). Phản hồi ở dạng JSON.

## Menu
- `GET /api/menu.php`
  - Query: `category_id` (tùy chọn)
  - Trả về danh mục và món ăn còn bán.

## Orders
- `GET /api/orders.php?limit=20` lấy danh sách đơn gần nhất kèm món.
- `POST /api/orders.php`
  - Body JSON tối thiểu:
    ```json
    {
      "order_type": "dine_in",
      "table_id": 1,
      "items": [{"item_id": 2, "quantity": 1}],
      "customer_name": "Nguyen Van A",
      "customer_phone": "0909",
      "notes": "ít cay",
      "payment_method": "cash"
    }
    ```
  - Tự tính thuế dựa trên hằng số `TAX_RATE` nếu không gửi.
  - Khi có `payment_method`, đơn được đánh dấu `paid` và tạo bản ghi payment.

## Reservations
- `GET /api/reservations.php` lấy các đặt bàn từ hôm nay.
- `POST /api/reservations.php`
  - Body JSON: `customer_name`, `customer_phone`, `reservation_date`, `reservation_time`, `number_of_guests`, tùy chọn `table_id`, `customer_email`, `special_requests`.

## Tables
- `GET /api/tables.php` danh sách bàn và trạng thái.
- `POST /api/tables.php`
  - Body JSON: `table_id`, `status` (`available|occupied|reserved|maintenance`).

## Lưu ý
- Các endpoint dùng PDO và trả mã lỗi HTTP phù hợp (401, 400, 405, 500).
- Đảm bảo PHP bật session và đường dẫn `config/` đúng như repo.

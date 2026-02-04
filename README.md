# Restaurant Management System

Quản lý nhà hàng với POS, đặt bàn, đơn hàng, kho nguyên liệu và báo cáo. Tài liệu này giúp bạn cài đặt nhanh trên môi trường cục bộ.

## Yêu cầu
- PHP 8.0+ (bật PDO MySQL, fileinfo)
- MySQL 5.7+/MariaDB 10.4+
- Composer (nếu dùng dependencies PHP)
- Web server (Apache/Nginx) hoặc PHP built-in server

## Cài đặt nhanh
1. Sao chép mã nguồn vào webroot.
2. Cập nhật thông tin database trong `config/database.php` (host, db, user, password).
3. Tạo cấu trúc thư mục uploads (đã có sẵn):
   - `uploads/menu_images/`, `uploads/avatars/`, `uploads/receipts/`, `uploads/reports/`, `uploads/categories/`
4. Khởi tạo database:
   - Cách 1: import file `restaurant_database.sql` vào MySQL.
   - Cách 2: chạy trình cài đặt `install.php` trong trình duyệt (tự tạo schema và dữ liệu mẫu).
5. (Tùy chọn) Cài dependencies PHP: `composer install`.
6. Truy cập trang chủ: `http://localhost/restaurant-management/`.

### Tài khoản mẫu
- Admin: admin@example.com / password
- Nhân viên: staff@example.com / password

## Cấu trúc chính
- `config/` cấu hình hệ thống và kết nối DB.
- `modules/` các màn hình ứng dụng (auth, dashboard, orders, inventory, reports...).
- `api/` các endpoint JSON (menu, orders, reservations, tables).
- `uploads/` lưu trữ file người dùng (bị ignore trong git).
- `vendor/` thư viện Composer (bị ignore trong git).

## API ngắn gọn
- `GET /api/menu.php` danh mục + món ăn.
- `GET /api/orders.php` đơn hàng mới nhất, `POST /api/orders.php` tạo đơn hàng.
- `GET /api/reservations.php` danh sách đặt bàn, `POST /api/reservations.php` tạo đặt bàn.
- `GET /api/tables.php` trạng thái bàn, `POST /api/tables.php` đổi trạng thái bàn.

## Ghi chú bảo mật
- Không commit thư mục `uploads/`, file `.env`, `restaurant_database.sql`, `composer.lock`.
- Đảm bảo cấu hình quyền ghi cho `uploads/` trên server.

## Hỗ trợ
Nếu gặp lỗi cài đặt, kiểm tra log PHP và quyền ghi thư mục. Vấn đề dữ liệu: import lại `restaurant_database.sql` hoặc chạy `install.php` để khởi tạo.

-- Database schema for Restaurant Management System (Clean & Complete Version)
-- Tương thích MariaDB 10.4.32 (XAMPP)

-- Tạo database
DROP DATABASE IF EXISTS `restaurant_db`;
CREATE DATABASE `restaurant_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `restaurant_db`;

-- 1. SETTINGS (cơ bản nhất)
CREATE TABLE `settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('restaurant_name', 'Nhà hàng QT'),
('address', '19 Nguyen Huu Tho Str., Tan Hung Ward, Ho Chi Minh City, Vietnam'),
('phone', '0792385669'),
('email', 'quynhthu04012005@gmail.com'),
('currency', 'VND'),
('tax_rate', '10'),
('booking_advance_days', '3'),
('working_hours', '07:00 - 22:00');

-- 2. USERS
CREATE TABLE `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `user_role` ENUM('admin','manager','waiter','chef','cashier','staff') NOT NULL DEFAULT 'staff',
  `phone` VARCHAR(30) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `idx_user_role_status` (`user_role`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`full_name`, `email`, `password_hash`, `user_role`, `phone`, `status`) VALUES
('Administrator', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0123456789', 'active'),
('Restaurant Staff', 'staff@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'waiter', '0987654321', 'active'),
('Chef Su', 'chef@example.com', '$2y$10$rHza8YF5jT8mBwNh.nL0ruMaH6D246QWSwMONLWM.LAv4gaqqLGBe', 'chef', '23465747', 'active'),
('Manager QT', 'manager@example.com', '$2y$10$H/UA/v7J7H32xu/HHz6GPeFyxkPcH/tCbKbDCTagjch9ph8bZbeJC', 'manager', '0792385669', 'active');

-- 3. ACTIVITY LOGS
CREATE TABLE `activity_logs` (
  `log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_logs_user` (`user_id`),
  KEY `idx_logs_action` (`action`),
  KEY `idx_logs_date` (`created_at`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. CATEGORIES
CREATE TABLE `categories` (
  `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(120) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `uq_category_name` (`category_name`),
  KEY `idx_category_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`category_name`, `description`, `display_order`, `is_active`) VALUES
('Khai vị', 'Các món khai vị hấp dẫn', 1, 1),
('Món chính', 'Món ăn chính đa dạng', 2, 1),
('Tráng miệng', 'Món tráng miệng ngọt ngào', 3, 1),
('Đồ uống', 'Thức uống giải khát', 4, 1);

-- 5. MENU ITEMS
CREATE TABLE `menu_items` (
  `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `cost_price` DECIMAL(12,2) DEFAULT 0.00,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_vegetarian` TINYINT(1) NOT NULL DEFAULT 0,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `idx_menu_category` (`category_id`),
  KEY `idx_menu_available` (`is_available`),
  CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `menu_items` (`category_id`, `item_name`, `description`, `price`, `is_vegetarian`, `is_available`, `display_order`) VALUES
(1, 'Gỏi cuốn tôm thịt', 'Khai vị tươi mát với tôm thịt', 35000.00, 0, 1, 1),
(2, 'Bò lúc lắc', 'Thịt bò xào rau củ thơm ngon', 85000.00, 0, 1, 1),
(2, 'Cơm gà xối mỡ', 'Gà chiên giòn với cơm trắng', 70000.00, 0, 1, 2),
(3, 'Chè khúc bạch', 'Tráng miệng mát lạnh từ thạch', 30000.00, 1, 1, 1),
(4, 'Trà đào cam sả', 'Thức uống giải khát thơm ngon', 40000.00, 1, 1, 1);

-- 6. INGREDIENTS (với các trường mở rộng)
CREATE TABLE `ingredients` (
  `ingredient_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingredient_name` VARCHAR(150) NOT NULL,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'g',
  `current_stock` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `reorder_level` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `supplier_name` VARCHAR(150) DEFAULT NULL,
  `supplier_phone` VARCHAR(20) DEFAULT NULL,
  `last_restocked` DATETIME DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ingredient_id`),
  KEY `idx_ing_stock` (`current_stock`),
  KEY `idx_ing_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ingredients` (`ingredient_name`, `unit`, `current_stock`, `reorder_level`, `cost_price`, `status`, `supplier_name`, `supplier_phone`, `last_restocked`) VALUES
('Thịt bò', 'kg', 10.000, 2.000, 250000.00, 'active', 'Thịt bò ABC', '0901234567', '2025-12-23 00:00:00'),
('Thịt gà', 'kg', 15.000, 3.000, 150000.00, 'active', 'Gà ta XYZ', '0902345678', NULL),
('Tôm sú', 'kg', 8.000, 2.000, 200000.00, 'active', 'Hải sản tươi', '0903456789', NULL),
('Rau củ hỗn hợp', 'kg', 12.000, 3.000, 50000.00, 'active', 'Rau Đà Lạt', '12345678', NULL),
('Trà đào', 'lít', 5.000, 1.000, 80000.00, 'active', 'Đào tươi', '0904567890', NULL);

-- 7. RECIPES
CREATE TABLE `recipes` (
  `recipe_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT UNSIGNED NOT NULL,
  `ingredient_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL DEFAULT 0.000,
  `notes` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`recipe_id`),
  UNIQUE KEY `uq_recipe_item_ing` (`item_id`, `ingredient_id`),
  KEY `idx_recipe_item` (`item_id`),
  KEY `idx_recipe_ingredient` (`ingredient_id`),
  CONSTRAINT `fk_recipe_item` FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`item_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recipe_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`ingredient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `recipes` (`item_id`, `ingredient_id`, `quantity`, `notes`) VALUES
(1, 3, 0.120, 'Tôm tươi 120g/phần'),
(1, 4, 0.060, 'Rau củ tươi'),
(2, 1, 0.200, 'Thịt bò 200g/phần'),
(2, 4, 0.100, 'Rau củ xào'),
(3, 2, 0.180, 'Thịt gà 180g/phần'),
(3, 4, 0.080, 'Rau ăn kèm'),
(4, 4, 0.080, 'Rau củ trang trí'),
(5, 5, 0.250, 'Trà đào 250ml/phần');

-- 8. RESTAURANT TABLES
CREATE TABLE `restaurant_tables` (
  `table_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_number` VARCHAR(20) NOT NULL,
  `table_name` VARCHAR(100) DEFAULT NULL,
  `capacity` INT NOT NULL DEFAULT 4,
  `location` ENUM('indoor','outdoor','vip','balcony') NOT NULL DEFAULT 'indoor',
  `status` ENUM('available','occupied','reserved','maintenance','cleaning') NOT NULL DEFAULT 'available',
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`table_id`),
  UNIQUE KEY `uq_table_number` (`table_number`),
  KEY `idx_table_status` (`status`),
  KEY `idx_table_location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `restaurant_tables` (`table_number`, `table_name`, `capacity`, `location`, `status`) VALUES
('T01', 'Bàn cửa sổ', 4, 'indoor', 'available'),
('T02', 'Bàn gia đình', 6, 'indoor', 'available'),
('B01', 'Bàn sân vườn', 4, 'outdoor', 'available'),
('V01', 'Bàn VIP 1', 8, 'vip', 'available'),
('V02', 'Bàn VIP 2', 10, 'vip', 'available');

-- 9. RESERVATIONS
CREATE TABLE `reservations` (
  `reservation_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(20) NOT NULL,
  `customer_email` VARCHAR(150) DEFAULT NULL,
  `table_id` INT UNSIGNED NULL,
  `reservation_date` DATE NOT NULL,
  `reservation_time` TIME NOT NULL,
  `number_of_guests` INT NOT NULL DEFAULT 1 CHECK (`number_of_guests` >= 1),
  `special_requests` TEXT DEFAULT NULL,
  `status` ENUM('pending','confirmed','seated','cancelled','completed','no_show') NOT NULL DEFAULT 'pending',
  `created_by` INT UNSIGNED NULL,
  `cancelled_by` INT UNSIGNED NULL,
  `cancel_reason` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`reservation_id`),
  KEY `idx_res_table` (`table_id`),
  KEY `idx_res_date_time` (`reservation_date`, `reservation_time`),
  KEY `idx_res_status` (`status`),
  CONSTRAINT `fk_res_table` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables`(`table_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_res_created_by` FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_res_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `reservations` (`customer_name`, `customer_phone`, `customer_email`, `table_id`, `reservation_date`, `reservation_time`, `number_of_guests`, `special_requests`, `status`, `created_by`) VALUES
('Nguyen Van A', '0909000000', 'customer@example.com', 2, '2025-12-24', '18:30:00', 4, 'Khu vực trong nhà, không gian yên tĩnh', 'confirmed', 1);

-- 10. ORDERS
CREATE TABLE `orders` (
  `order_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(30) NOT NULL,
  `table_id` INT UNSIGNED NULL,
  `order_type` ENUM('dine_in','takeaway','delivery') NOT NULL DEFAULT 'dine_in',
  `waiter_id` INT UNSIGNED NULL,
  `customer_name` VARCHAR(150) DEFAULT NULL,
  `customer_phone` VARCHAR(20) DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `order_status` ENUM('pending','preparing','ready','served','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('unpaid','partial','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `uq_order_number` (`order_number`),
  KEY `idx_order_table` (`table_id`),
  KEY `idx_order_status` (`order_status`),
  KEY `idx_order_date` (`created_at`),
  CONSTRAINT `fk_order_table` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables`(`table_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_order_waiter` FOREIGN KEY (`waiter_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. ORDER ITEMS
CREATE TABLE `order_items` (
  `order_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1 CHECK (`quantity` > 0),
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `special_instructions` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_item_id`),
  KEY `idx_order_item_order` (`order_id`),
  KEY `idx_order_item_item` (`item_id`),
  CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_item_menu` FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`item_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. PAYMENTS
CREATE TABLE `payments` (
  `payment_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('cash','card','qr','momo','zalo_pay','bank_transfer','other') NOT NULL DEFAULT 'cash',
  `cashier_id` INT UNSIGNED NULL,
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `paid_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `idx_pay_order` (`order_id`),
  KEY `idx_pay_method` (`payment_method`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_cashier` FOREIGN KEY (`cashier_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. INVENTORY TRANSACTIONS
CREATE TABLE `inventory_transactions` (
  `transaction_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingredient_id` INT UNSIGNED NOT NULL,
  `transaction_type` ENUM('in','out','adjust') NOT NULL,
  `quantity` DECIMAL(12,3) NOT NULL,
  `unit_price` DECIMAL(12,2) DEFAULT 0.00,
  `total_cost` DECIMAL(12,2) DEFAULT 0.00,
  `reference_type` ENUM('order','purchase','adjustment','waste','transfer') DEFAULT NULL,
  `reference_id` BIGINT DEFAULT NULL,
  `performed_by` INT UNSIGNED NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_inv_ing` (`ingredient_id`),
  KEY `idx_inv_type` (`transaction_type`),
  KEY `idx_inv_date` (`created_at`),
  CONSTRAINT `fk_inv_ing` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`ingredient_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inv_user` FOREIGN KEY (`performed_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL,
  INDEX `idx_inv_ref` (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. EXPENSES
CREATE TABLE `expenses` (
  `expense_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` ENUM('utilities','supplies','salary','maintenance','marketing','other') NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `created_by` INT UNSIGNED NULL,
  `receipt_number` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`expense_id`),
  KEY `idx_exp_category` (`category`),
  KEY `idx_exp_date` (`expense_date`),
  CONSTRAINT `fk_exp_user` FOREIGN KEY (`created_by`) REFERENCES `users`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DỮ LIỆU DEMO SAMPLE ORDER
INSERT INTO `orders` (`order_number`, `table_id`, `order_type`, `waiter_id`, `customer_name`, `subtotal`, `tax`, `discount`, `total_amount`, `order_status`, `payment_status`) VALUES
('ORD20251223QT001', 1, 'dine_in', 2, 'Khách demo QT', 155000.00, 15500.00, 0.00, 170500.00, 'served', 'paid');

SET @last_order_id = LAST_INSERT_ID();

INSERT INTO `order_items` (`order_id`, `item_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(@last_order_id, 2, 1, 85000.00, 85000.00),
(@last_order_id, 3, 1, 70000.00, 70000.00);

INSERT INTO `payments` (`order_id`, `amount`, `payment_method`, `cashier_id`) VALUES
(@last_order_id, 170500.00, 'cash', 1);

-- SAMPLE INVENTORY TRANSACTIONS
INSERT INTO `inventory_transactions` (`ingredient_id`, `transaction_type`, `quantity`, `unit_price`, `total_cost`, `reference_type`, `performed_by`, `notes`) VALUES
(1, 'in', 2.000, 250000.00, 500000.00, 'purchase', 1, 'Nhập thịt bò tươi'),
(5, 'in', 3.000, 100000.00, 300000.00, 'purchase', 1, 'Nhập cá lóc');

-- TẠO INDEX SUMMARY CHO REPORTING
CREATE VIEW `v_order_summary` AS
SELECT 
    DATE(o.created_at) as order_date,
    o.order_type,
    COUNT(o.order_id) as total_orders,
    SUM(o.total_amount) as revenue,
    AVG(o.total_amount) as avg_order_value
FROM `orders` o 
WHERE o.payment_status = 'paid'
GROUP BY DATE(o.created_at), o.order_type;

-- Trigger tự động cập nhật stock khi có order mới (optional)
DELIMITER $$
CREATE TRIGGER `tr_order_item_after_insert` AFTER INSERT ON `order_items`
FOR EACH ROW
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE ing_id INT UNSIGNED;
    DECLARE ing_qty DECIMAL(12,3);
    DECLARE cur CURSOR FOR 
        SELECT ingredient_id, quantity * NEW.quantity as needed_qty 
        FROM `recipes` WHERE item_id = NEW.item_id;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO ing_id, ing_qty;
        IF done THEN LEAVE read_loop; END IF;
        
        INSERT INTO `inventory_transactions` 
        (`ingredient_id`, `transaction_type`, `quantity`, `reference_type`, `reference_id`, `performed_by`, `notes`)
        VALUES (ing_id, 'out', ing_qty, 'order', NEW.order_id, NULL, 'Auto deduct from order');
        
        UPDATE `ingredients` SET current_stock = current_stock - ing_qty WHERE ingredient_id = ing_id;
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;


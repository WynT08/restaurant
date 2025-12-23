-- Database schema for Restaurant Management System
CREATE DATABASE IF NOT EXISTS `restaurant_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `restaurant_db`;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `user_role` ENUM('admin','manager','waiter','chef','cashier','staff') NOT NULL DEFAULT 'staff',
  `phone` VARCHAR(30) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`full_name`, `email`, `password_hash`, `user_role`, `phone`) VALUES
('Administrator', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '0123456789'),
('Restaurant Staff', 'staff@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'waiter', '0987654321');

-- Activity logs
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_logs_user` (`user_id`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(120) NOT NULL,
  `description` TEXT,
  `display_order` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`category_name`, `display_order`) VALUES
('Khai vị', 1),
('Món chính', 2),
('Tráng miệng', 3),
('Đồ uống', 4);

-- Menu items
CREATE TABLE IF NOT EXISTS `menu_items` (
  `item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED DEFAULT NULL,
  `item_name` VARCHAR(150) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `cost_price` DECIMAL(12,2) DEFAULT 0,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_vegetarian` TINYINT(1) NOT NULL DEFAULT 0,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `idx_menu_category` (`category_id`),
  CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `menu_items` (`category_id`,`item_name`,`description`,`price`,`is_vegetarian`,`display_order`) VALUES
(1,'Gỏi cuốn tôm thịt','Khai vị tươi mát',35000,0,1),
(2,'Bò lúc lắc','Thịt bò xào rau củ',85000,0,2),
(2,'Cơm gà xối mỡ','Gà chiên giòn với cơm',70000,0,3),
(3,'Chè khúc bạch','Tráng miệng mát lạnh',30000,0,1),
(4,'Trà đào cam sả','Thức uống giải khát',40000,0,1);

-- Ingredients
CREATE TABLE IF NOT EXISTS `ingredients` (
  `ingredient_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingredient_name` VARCHAR(150) NOT NULL,
  `unit` VARCHAR(30) NOT NULL DEFAULT 'g',
  `current_stock` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `reorder_level` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ingredient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `ingredients` (`ingredient_name`,`unit`,`current_stock`,`reorder_level`,`cost_price`) VALUES
('Thịt bò', 'g', 10000, 2000, 250000),
('Thịt gà', 'g', 15000, 3000, 150000),
('Tôm', 'g', 8000, 2000, 200000),
('Rau củ', 'g', 12000, 3000, 50000),
('Trà đào', 'ml', 5000, 1000, 80000);

-- Recipes
CREATE TABLE IF NOT EXISTS `recipes` (
  `recipe_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT UNSIGNED NOT NULL,
  `ingredient_id` INT UNSIGNED NOT NULL,
  `quantity` DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`recipe_id`),
  KEY `idx_recipe_item` (`item_id`),
  KEY `idx_recipe_ingredient` (`ingredient_id`),
  CONSTRAINT `fk_recipe_item` FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`item_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_recipe_ingredient` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`ingredient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inventory transactions
CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `transaction_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ingredient_id` INT UNSIGNED NOT NULL,
  `transaction_type` ENUM('in','out','adjust') NOT NULL,
  `quantity` DECIMAL(12,2) NOT NULL,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id` BIGINT DEFAULT NULL,
  `performed_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`transaction_id`),
  KEY `idx_inv_ing` (`ingredient_id`),
  CONSTRAINT `fk_inv_ing` FOREIGN KEY (`ingredient_id`) REFERENCES `ingredients`(`ingredient_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tables
CREATE TABLE IF NOT EXISTS `restaurant_tables` (
  `table_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `table_number` VARCHAR(20) NOT NULL,
  `table_name` VARCHAR(100) DEFAULT NULL,
  `capacity` INT NOT NULL DEFAULT 4,
  `location` ENUM('indoor','outdoor','vip','balcony') NOT NULL DEFAULT 'indoor',
  `status` ENUM('available','occupied','reserved','maintenance') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`table_id`),
  UNIQUE KEY `uq_table_number` (`table_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `restaurant_tables` (`table_number`,`table_name`,`capacity`,`location`,`status`) VALUES
('T01','Bàn cửa sổ',4,'indoor','available'),
('T02','Bàn gia đình',6,'indoor','available'),
('B01','Bàn sân vườn',4,'outdoor','available'),
('V01','Bàn VIP 1',8,'vip','available');

-- Reservations
CREATE TABLE IF NOT EXISTS `reservations` (
  `reservation_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) NOT NULL,
  `customer_email` VARCHAR(150) DEFAULT NULL,
  `table_id` INT UNSIGNED DEFAULT NULL,
  `reservation_date` DATE NOT NULL,
  `reservation_time` TIME NOT NULL,
  `number_of_guests` INT NOT NULL DEFAULT 1,
  `special_requests` TEXT,
  `status` ENUM('pending','confirmed','seated','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`reservation_id`),
  KEY `idx_res_table` (`table_id`),
  CONSTRAINT `fk_res_table` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables`(`table_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_number` VARCHAR(30) NOT NULL,
  `table_id` INT UNSIGNED DEFAULT NULL,
  `order_type` ENUM('dine_in','takeaway','delivery') NOT NULL DEFAULT 'dine_in',
  `waiter_id` INT UNSIGNED DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `tax` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `discount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `customer_name` VARCHAR(150) DEFAULT NULL,
  `customer_phone` VARCHAR(30) DEFAULT NULL,
  `notes` TEXT,
  `order_status` ENUM('pending','preparing','served','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  UNIQUE KEY `uq_order_number` (`order_number`),
  KEY `idx_order_table` (`table_id`),
  CONSTRAINT `fk_order_table` FOREIGN KEY (`table_id`) REFERENCES `restaurant_tables`(`table_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `special_instructions` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_item_id`),
  KEY `idx_order_item_order` (`order_id`),
  KEY `idx_order_item_item` (`item_id`),
  CONSTRAINT `fk_order_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_item_menu` FOREIGN KEY (`item_id`) REFERENCES `menu_items`(`item_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `payment_method` ENUM('cash','card','qr','other') NOT NULL DEFAULT 'cash',
  `cashier_id` INT UNSIGNED DEFAULT NULL,
  `paid_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `idx_pay_order` (`order_id`),
  CONSTRAINT `fk_pay_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Expenses
CREATE TABLE IF NOT EXISTS `expenses` (
  `expense_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `amount` DECIMAL(12,2) NOT NULL,
  `expense_date` DATE NOT NULL,
  `created_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`expense_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed recipes (sample mapping)
INSERT INTO `recipes` (`item_id`,`ingredient_id`,`quantity`) VALUES
(2,1,200), -- Bo luc lac uses beef
(2,4,100),
(3,2,180), -- Com ga uses chicken
(3,4,80),
(1,3,120), -- Goi cuon uses shrimp
(1,4,60),
(4,4,80),
(5,5,250);

-- Sample order for demo
INSERT INTO `orders` (`order_number`,`table_id`,`order_type`,`waiter_id`,`subtotal`,`tax`,`discount`,`total_amount`,`customer_name`,`order_status`,`payment_status`) VALUES
('ORD20250101ABC123',1,'dine_in',1,150000,15000,0,165000,'Khach demo','served','paid');

SET @last_order_id = LAST_INSERT_ID();
INSERT INTO `order_items` (`order_id`,`item_id`,`quantity`,`unit_price`,`subtotal`) VALUES
(@last_order_id,2,1,85000,85000),
(@last_order_id,3,1,70000,70000);
INSERT INTO `payments` (`order_id`,`amount`,`payment_method`,`cashier_id`) VALUES
(@last_order_id,165000,'cash',1);

-- Keep at least one reservation example
INSERT INTO `reservations` (`customer_name`,`customer_phone`,`customer_email`,`table_id`,`reservation_date`,`reservation_time`,`number_of_guests`,`special_requests`,`status`,`created_by`) VALUES
('Nguyen Van A','0909000000','customer@example.com',2,DATE_ADD(CURDATE(), INTERVAL 1 DAY),'18:30',4,'Khu vực trong nhà','confirmed',1);

-- =====================================================
-- Wangari Farm - Full Database Export
-- Complete schema and data for import via phpMyAdmin
-- =====================================================

-- Use the production database
USE wangari_db;

-- Disable foreign key checks for clean import
SET FOREIGN_KEY_CHECKS=0;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS `mpesa_transactions`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `product_variants`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `vaccinations`;
DROP TABLE IF EXISTS `production_records`;
DROP TABLE IF EXISTS `flocks`;
DROP TABLE IF EXISTS `financial_records`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `recipes`;
DROP TABLE IF EXISTS `users`;

-- =====================================================
-- TABLE: users
-- =====================================================
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('super_admin', 'farm_manager', 'customer') DEFAULT 'customer',
    `first_name` VARCHAR(50),
    `last_name` VARCHAR(50),
    `phone_number` VARCHAR(15),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert users
INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `first_name`, `last_name`) VALUES
('admin', 'admin@wangari.farm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Admin', 'User'),
('demo', 'demo@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Demo', 'User'),
('manager', 'manager@wangari.farm', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'farm_manager', 'Farm', 'Manager');

-- =====================================================
-- TABLE: categories
-- =====================================================
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `category_type` ENUM('chicken', 'feed') DEFAULT 'chicken',
    `icon_class` VARCHAR(50),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert categories
INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `category_type`) VALUES
(1, 'Broilers', 'broilers', 'Fast-growing broiler chickens for meat production', 'chicken'),
(2, 'Layers', 'layers', 'High-productivity layer chickens for egg production', 'chicken'),
(3, 'Day-Old Chicks', 'day-old-chicks', 'Vaccinated day-old chicks ready for rearing', 'chicken'),
(4, 'Feeds', 'feeds', 'Specialized animal feeds for optimal poultry nutrition', 'feed');

-- =====================================================
-- TABLE: products
-- =====================================================
CREATE TABLE `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `description` TEXT,
    `product_type` ENUM('live_chicken', 'meat', 'eggs', 'chicks', 'feed') NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `stock_quantity` INT DEFAULT 0,
    `weight_kg` DECIMAL(5, 2),
    `image_url` VARCHAR(255),
    `sku` VARCHAR(50) UNIQUE,
    `manufacturer` VARCHAR(100),
    `is_active` TINYINT(1) DEFAULT 1,
    `is_featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT,
    INDEX `idx_product_type` (`product_type`),
    INDEX `idx_category` (`category_id`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert products
INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `product_type`, `price`, `stock_quantity`, `is_active`, `is_featured`) VALUES
-- Broilers
(1, 1, 'Ross 308 Broilers', 'ross-308-broilers', 'Premium fast-growing broiler breed. Excellent feed efficiency and meat quality. Ready for market in 6-7 weeks.', 'live_chicken', 450.00, 50, 1, 1),
(2, 1, 'Cobb 500 Broilers', 'cobb-500-broilers', 'High-performance broilers with excellent feed conversion. Superior meat yield and quality.', 'live_chicken', 480.00, 40, 1, 1),
(3, 1, 'Hubbard Broilers', 'hubbard-broilers', 'Reliable broiler breed with consistent meat quality. Great for commercial farming.', 'live_chicken', 420.00, 60, 1, 0),

-- Layers
(4, 2, 'ISA Brown Layers', 'isa-brown-layers', 'Premium brown egg layer producing 300+ eggs/year. Excellent feed efficiency.', 'live_chicken', 350.00, 45, 1, 1),
(5, 2, 'Fresh Farm Eggs (Trays)', 'fresh-farm-eggs', 'Premium quality eggs from our free-range layer flock. 30-egg trays. Freshly collected daily.', 'eggs', 420.00, 100, 1, 1),
(6, 2, 'Lohmann Layers', 'lohmann-layers', 'White egg layers with exceptional livability and performance. Long laying cycle.', 'live_chicken', 340.00, 55, 1, 0),
(7, 2, 'Bovans Brown Layers', 'bovans-brown-layers', 'Robust brown egg layers. Excellent feed conversion and egg quality.', 'live_chicken', 360.00, 35, 1, 0),

-- Day-Old Chicks
(8, 3, 'Day-Old Broiler Chicks', 'day-old-broiler-chicks', 'Vaccinated broiler chicks from quality parent stock. 95%+ hatch rate guarantee.', 'chicks', 80.00, 1000, 1, 1),
(9, 3, 'Day-Old Layer Chicks', 'day-old-layer-chicks', 'Premium layer chicks vaccinated against Mareks and Newcastle disease. Ready to grow.', 'chicks', 70.00, 800, 1, 1),
(10, 3, 'Mixed Day-Old Chicks', 'mixed-day-old-chicks', 'Combination of broiler and layer chicks. Great for mixed farming operations.', 'chicks', 75.00, 500, 1, 0),
(11, 3, 'Kienyeji/Indigenous Chicks', 'kienyeji-chicks', 'Hardy indigenous breed chicks. Disease resistant and suitable for free-range farming.', 'chicks', 60.00, 300, 1, 1),

-- Feeds
(12, 4, 'Starter Feed (0-4 weeks)', 'starter-feed', 'High-protein formula for day-old chicks. 24% crude protein with vitamins and probiotics. 50kg bags.', 'feed', 3200.00, 100, 1, 1),
(13, 4, 'Grower Feed (4-8 weeks)', 'grower-feed', 'Balanced formula for growing chicks. 20% crude protein with essential amino acids. 50kg bags.', 'feed', 2800.00, 120, 1, 1),
(14, 4, 'Layer Mash (16 weeks+)', 'layer-mash', 'Premium feed for laying hens. 18% crude protein with calcium for strong eggshells. 50kg bags.', 'feed', 2500.00, 150, 1, 1),
(15, 4, 'Broiler Finisher (6-8 weeks)', 'broiler-finisher', 'Final stage feed for broilers. High energy formula for rapid weight gain. 50kg bags.', 'feed', 2900.00, 110, 1, 0),
(16, 4, 'Wangari Premium Mix', 'wangari-premium-mix', 'Our signature blend. Multi-purpose feed suitable for all poultry types. 50kg bags.', 'feed', 3100.00, 200, 1, 1),
(17, 4, 'Vitamin & Mineral Supplements', 'vitamin-mineral-supplements', 'Complete vitamin complex and mineral pack for all poultry. Boosts immunity and productivity. 5kg bags.', 'feed', 1200.00, 80, 1, 0),
(18, 4, 'Chick Mash (0-4 weeks)', 'chick-mash', 'Fine mash feed for young chicks. Easy to digest with high energy content. 25kg bags.', 'feed', 1800.00, 90, 1, 1);

-- =====================================================
-- TABLE: product_variants
-- =====================================================
CREATE TABLE `product_variants` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT NOT NULL,
    `variant_name` VARCHAR(100) NOT NULL,
    `variant_value` VARCHAR(100) NOT NULL,
    `variant_price` DECIMAL(10, 2) NOT NULL,
    `variant_stock` INT DEFAULT 0,
    `sku` VARCHAR(50) UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert product variants (different bag sizes for feeds)
INSERT INTO `product_variants` (`product_id`, `variant_name`, `variant_value`, `variant_price`, `variant_stock`) VALUES
(12, 'Bag Size', '25kg', 1700.00, 50),
(12, 'Bag Size', '50kg', 3200.00, 100),
(13, 'Bag Size', '25kg', 1500.00, 60),
(13, 'Bag Size', '50kg', 2800.00, 120),
(14, 'Bag Size', '25kg', 1350.00, 80),
(14, 'Bag Size', '50kg', 2500.00, 150);

-- =====================================================
-- TABLE: orders
-- =====================================================
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `order_number` VARCHAR(20) NOT NULL UNIQUE,
    `status` ENUM('pending', 'paid', 'processing', 'shipped', 'completed', 'cancelled') DEFAULT 'pending',
    `total_amount` DECIMAL(10, 2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'mpesa',
    `shipping_address` TEXT NOT NULL,
    `phone_contact` VARCHAR(15) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: order_items
-- =====================================================
CREATE TABLE `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price_at_purchase` DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: mpesa_transactions
-- =====================================================
CREATE TABLE `mpesa_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `merchant_request_id` VARCHAR(50) NOT NULL,
    `checkout_request_id` VARCHAR(50) NOT NULL UNIQUE,
    `result_code` INT,
    `result_desc` VARCHAR(255),
    `amount` DECIMAL(10, 2),
    `mpesa_receipt_number` VARCHAR(50),
    `transaction_date` TIMESTAMP NULL,
    `phone_number` VARCHAR(15),
    `status` ENUM('initiated', 'completed', 'failed') DEFAULT 'initiated',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: flocks
-- =====================================================
CREATE TABLE `flocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `flock_name` VARCHAR(100) NOT NULL,
    `breed` VARCHAR(100) NOT NULL,
    `initial_count` INT NOT NULL,
    `current_count` INT NOT NULL,
    `hatch_date` DATE NOT NULL,
    `status` ENUM('active', 'sold', 'archived') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample flocks
INSERT INTO `flocks` (`flock_name`, `breed`, `initial_count`, `current_count`, `hatch_date`, `status`) VALUES
('Flock A - Broilers', 'Ross 308', 500, 480, '2026-06-01', 'active'),
('Flock B - Layers', 'ISA Brown', 300, 295, '2026-04-15', 'active'),
('Flock C - Kienyeji', 'Indigenous Mixed', 200, 198, '2026-05-20', 'active');

-- =====================================================
-- TABLE: production_records
-- =====================================================
CREATE TABLE `production_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `flock_id` INT NOT NULL,
    `record_date` DATE NOT NULL,
    `eggs_collected` INT DEFAULT 0,
    `cracked_eggs` INT DEFAULT 0,
    `meat_weight_kg` DECIMAL(8, 2) DEFAULT 0.00,
    `mortality` INT DEFAULT 0,
    `feed_consumed_kg` DECIMAL(8, 2) DEFAULT 0.00,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`flock_id`) REFERENCES `flocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: vaccinations
-- =====================================================
CREATE TABLE `vaccinations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `flock_id` INT NOT NULL,
    `vaccine_name` VARCHAR(100) NOT NULL,
    `scheduled_date` DATE NOT NULL,
    `administered_date` DATE,
    `status` ENUM('scheduled', 'completed', 'missed') DEFAULT 'scheduled',
    `administered_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`flock_id`) REFERENCES `flocks`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`administered_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: financial_records
-- =====================================================
CREATE TABLE `financial_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `type` ENUM('income', 'expense') NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `amount` DECIMAL(10, 2) NOT NULL,
    `transaction_date` DATE NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: recipes
-- =====================================================
CREATE TABLE `recipes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `content` TEXT NOT NULL,
    `image_url` VARCHAR(255),
    `is_published` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: testimonials
-- =====================================================
CREATE TABLE `testimonials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `customer_name` VARCHAR(100) NOT NULL,
    `customer_role` VARCHAR(100),
    `rating` INT,
    `content` TEXT NOT NULL,
    `is_approved` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample testimonials
INSERT INTO `testimonials` (`customer_name`, `customer_role`, `rating`, `content`, `is_approved`) VALUES
('John Kamau', 'Commercial Farmer, Nairobi', 5, 'Wangari Farm has been my go-to supplier for the past 3 years. Their day-old chicks have excellent survival rates and their feeds produce outstanding results.', 1),
('Mary Akinyi', 'Small-Scale Farmer, Kisumu', 5, 'The quality of their layers is exceptional. My hens are producing 290+ eggs per year. The support team is also very helpful with advice.', 1),
('Peter Ochieng', 'Farm Manager, Bungoma', 4, 'Great products and reliable delivery. Their broilers reach market weight faster than other breeds I have tried. Highly recommended!', 1);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS=1;

-- =====================================================
-- END OF SQL EXPORT
-- =====================================================

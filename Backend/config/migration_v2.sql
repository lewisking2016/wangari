-- Migration V2: Feed Manufacturing Enhancements
-- Adds: expanded order pipeline, recurring schedules, production batch notes

USE wangari_db;

-- 1. Expand order status pipeline with fulfillment stages
ALTER TABLE orders 
    MODIFY COLUMN status ENUM('pending','paid','picking','packing','production','dispatch','shipped','delivered','completed','cancelled') DEFAULT 'pending';

-- 2. Recurring feed delivery schedules (customer groups)
CREATE TABLE IF NOT EXISTS recurring_feed_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    frequency_days INT NOT NULL DEFAULT 7,
    next_delivery_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Add batch_number column to production_history for batch tracking
ALTER TABLE production_history
    ADD COLUMN batch_number VARCHAR(30) DEFAULT NULL AFTER id;

ALTER TABLE production_history
    ADD COLUMN notes TEXT AFTER total_cost;

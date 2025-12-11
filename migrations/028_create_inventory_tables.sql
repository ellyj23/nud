-- Migration: Create Inventory Tables
-- Manages product inventory, stock levels, and movements

CREATE TABLE IF NOT EXISTS `inventory_products` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_code` VARCHAR(50) NOT NULL UNIQUE,
  `product_name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(100),
  `unit_of_measure` VARCHAR(50) DEFAULT 'pcs',
  `unit_cost` DECIMAL(15,2) DEFAULT 0.00,
  `selling_price` DECIMAL(15,2) DEFAULT 0.00,
  `current_stock` DECIMAL(10,2) DEFAULT 0.00,
  `reorder_level` DECIMAL(10,2) DEFAULT 0.00,
  `reorder_quantity` DECIMAL(10,2) DEFAULT 0.00,
  `location` VARCHAR(100),
  `status` ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_code` (`product_code`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_product_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `inventory_movements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `movement_type` ENUM('in', 'out', 'adjustment', 'transfer') NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `reference_type` VARCHAR(50), -- 'purchase_order', 'sale', 'adjustment', etc.
  `reference_id` INT(11),
  `unit_cost` DECIMAL(15,2),
  `total_value` DECIMAL(15,2),
  `from_location` VARCHAR(100),
  `to_location` VARCHAR(100),
  `notes` TEXT,
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_movement_type` (`movement_type`),
  KEY `idx_reference` (`reference_type`, `reference_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_movement_product` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movement_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `inventory_stock_alerts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) NOT NULL,
  `alert_type` ENUM('low_stock', 'out_of_stock', 'overstock') NOT NULL,
  `current_level` DECIMAL(10,2),
  `threshold_level` DECIMAL(10,2),
  `is_resolved` BOOLEAN DEFAULT FALSE,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_is_resolved` (`is_resolved`),
  CONSTRAINT `fk_alert_product` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

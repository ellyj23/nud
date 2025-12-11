-- Migration: Create Budget Tables
-- Manages budgets, tracking, and variance analysis

CREATE TABLE IF NOT EXISTS `budgets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `budget_name` VARCHAR(255) NOT NULL,
  `budget_period` ENUM('monthly', 'quarterly', 'yearly', 'custom') DEFAULT 'monthly',
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'RWF',
  `status` ENUM('draft', 'active', 'closed') DEFAULT 'draft',
  `notes` TEXT,
  `created_by` INT(11),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_budget_period` (`budget_period`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`, `end_date`),
  CONSTRAINT `fk_budget_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `budget_lines` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `budget_id` INT(11) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `subcategory` VARCHAR(100),
  `budgeted_amount` DECIMAL(15,2) NOT NULL,
  `actual_amount` DECIMAL(15,2) DEFAULT 0.00,
  `variance` DECIMAL(15,2) DEFAULT 0.00,
  `variance_percentage` DECIMAL(5,2) DEFAULT 0.00,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  KEY `idx_budget_id` (`budget_id`),
  KEY `idx_category` (`category`),
  CONSTRAINT `fk_budget_line` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `budget_alerts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `budget_line_id` INT(11) NOT NULL,
  `alert_type` ENUM('approaching_limit', 'exceeded', 'custom') NOT NULL,
  `threshold_percentage` DECIMAL(5,2) NOT NULL,
  `is_triggered` BOOLEAN DEFAULT FALSE,
  `triggered_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_budget_line_id` (`budget_line_id`),
  KEY `idx_is_triggered` (`is_triggered`),
  CONSTRAINT `fk_budget_alert_line` FOREIGN KEY (`budget_line_id`) REFERENCES `budget_lines` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

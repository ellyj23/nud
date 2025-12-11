-- Migration: Create Client Portal Users Table
-- Allows clients to log in and view their invoices, make payments, etc.

CREATE TABLE IF NOT EXISTS `client_portal_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `client_id` INT(11) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100),
  `last_name` VARCHAR(100),
  `phone` VARCHAR(50),
  `is_active` BOOLEAN DEFAULT TRUE,
  `email_verified` BOOLEAN DEFAULT FALSE,
  `email_verification_token` VARCHAR(255),
  `password_reset_token` VARCHAR(255),
  `password_reset_expires` TIMESTAMP NULL,
  `last_login_at` TIMESTAMP NULL,
  `login_attempts` INT DEFAULT 0,
  `locked_until` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `idx_client_id` (`client_id`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_portal_user_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `client_portal_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `portal_user_id` INT(11) NOT NULL,
  `session_token` VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_portal_user_id` (`portal_user_id`),
  KEY `idx_session_token` (`session_token`),
  KEY `idx_expires_at` (`expires_at`),
  CONSTRAINT `fk_portal_session_user` FOREIGN KEY (`portal_user_id`) REFERENCES `client_portal_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add credit limit to clients table if it doesn't exist
ALTER TABLE `clients` 
ADD COLUMN IF NOT EXISTS `credit_limit` DECIMAL(15,2) DEFAULT 0.00 AFTER `tin`,
ADD COLUMN IF NOT EXISTS `current_credit` DECIMAL(15,2) DEFAULT 0.00 AFTER `credit_limit`,
ADD COLUMN IF NOT EXISTS `credit_limit_currency` VARCHAR(3) DEFAULT 'RWF' AFTER `current_credit`;
